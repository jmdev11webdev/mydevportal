<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot access the readme generator', function () {
    $this->get(route('readme.index'))->assertRedirect(route('login'));
});

test('authenticated developers can access the readme generator', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('readme.index'))
        ->assertOk();
});

test('readme generator lists only the authenticated developer projects ordered by name', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $user->projects()->create([
        'name' => 'Zeta Tool',
        'description' => 'Second alphabetically',
        'status' => 'active',
        'color' => 'indigo',
        'tech_stack' => ['Laravel', 'Vue'],
    ]);

    $user->projects()->create([
        'name' => 'Alpha CLI',
        'description' => 'First alphabetically',
        'status' => 'planning',
        'color' => 'emerald',
        'tech_stack' => ['PHP'],
    ]);

    $otherUser->projects()->create([
        'name' => 'Other User Project',
        'status' => 'active',
        'color' => 'rose',
    ]);

    $this->actingAs($user)
        ->get(route('readme.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Readme/Index')
            ->has('projects', 2)
            ->where('projects.0.name', 'Alpha CLI')
            ->where('projects.1.name', 'Zeta Tool')
        );
});
