<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot access the eye care timer page', function () {
    $this->get(route('eye-care.index'))->assertRedirect(route('login'));
});

test('authenticated developers can access the 20-20-20 eye care timer page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('eye-care.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('EyeCare/Index')
        );
});
