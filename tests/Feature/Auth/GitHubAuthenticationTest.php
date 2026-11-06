<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

test('github oauth routes are unavailable when not configured', function () {
    config([
        'services.github.client_id' => null,
        'services.github.client_secret' => null,
    ]);

    $this->get(route('auth.github.redirect'))->assertNotFound();
    $this->get(route('auth.github.callback'))->assertNotFound();
});

test('login and register views reflect github oauth configuration state', function () {
    config([
        'services.github.client_id' => null,
        'services.github.client_secret' => null,
    ]);

    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('githubAuthEnabled', false));

    config([
        'services.github.client_id' => 'test-client-id',
        'services.github.client_secret' => 'test-client-secret',
    ]);

    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('githubAuthEnabled', true));

    $this->get(route('register'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('githubAuthEnabled', true));
});

test('github redirect starts the oauth flow', function () {
    config([
        'services.github.client_id' => 'test-client-id',
        'services.github.client_secret' => 'test-client-secret',
    ]);

    Socialite::fake('github');

    $this->get(route('auth.github.redirect'))
        ->assertRedirect('https://socialite.fake/github/authorize');
});

test('developers can create an account with github', function () {
    config([
        'services.github.client_id' => 'test-client-id',
        'services.github.client_secret' => 'test-client-secret',
    ]);

    Socialite::fake('github', SocialiteUser::fake([
        'id' => 'github-123',
        'nickname' => 'octocat',
        'name' => 'The Octocat',
        'email' => 'octocat@github.test',
        'avatar' => 'https://avatars.githubusercontent.com/u/1',
    ]));

    $response = $this->get(route('auth.github.callback'));

    $response->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticated();

    $user = User::query()->where('email', 'octocat@github.test')->first();

    expect($user)->not->toBeNull()
        ->and($user->github_id)->toBe('github-123')
        ->and($user->github_username)->toBe('octocat')
        ->and($user->email_verified_at)->not->toBeNull();
});

test('existing accounts are linked by email', function () {
    config([
        'services.github.client_id' => 'test-client-id',
        'services.github.client_secret' => 'test-client-secret',
    ]);

    $user = User::factory()->unverified()->create([
        'email' => 'developer@example.test',
    ]);

    Socialite::fake('github', SocialiteUser::fake([
        'id' => 'github-456',
        'nickname' => 'developer',
        'name' => 'Developer',
        'email' => 'developer@example.test',
        'avatar' => 'https://avatars.githubusercontent.com/u/2',
    ]));

    $this->get(route('auth.github.callback'))
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user->fresh());

    $user->refresh();

    expect($user->github_id)->toBe('github-456')
        ->and($user->github_username)->toBe('developer')
        ->and($user->email_verified_at)->not->toBeNull();
});

test('developers with two factor enabled are sent to the challenge', function () {
    config([
        'services.github.client_id' => 'test-client-id',
        'services.github.client_secret' => 'test-client-secret',
    ]);

    $user = User::factory()->withTwoFactor()->create([
        'email' => 'secure@example.test',
    ]);

    Socialite::fake('github', SocialiteUser::fake([
        'id' => 'github-789',
        'nickname' => 'secure',
        'name' => 'Secure Developer',
        'email' => 'secure@example.test',
    ]));

    $response = $this->get(route('auth.github.callback'));

    $response->assertRedirect(route('two-factor.login'));
    $response->assertSessionHas('login.id', $user->id);
    $this->assertGuest();
});

test('github accounts without an email are sent back to login', function () {
    config([
        'services.github.client_id' => 'test-client-id',
        'services.github.client_secret' => 'test-client-secret',
    ]);

    Socialite::fake('github', SocialiteUser::fake([
        'id' => 'github-no-email',
        'nickname' => 'private',
        'name' => 'Private Developer',
        'email' => null,
    ]));

    $this->get(route('auth.github.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('github');

    $this->assertGuest();
});
