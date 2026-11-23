<?php

use App\Models\StackItem;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot access the stack page', function () {
    $this->get(route('stack.index'))->assertRedirect(route('login'));
});

test('authenticated users can view their stack items and summary', function () {
    $user = User::factory()->create();

    StackItem::create([
        'user_id' => $user->id,
        'name' => 'Laravel',
        'category' => 'backend',
        'status' => 'core',
        'version' => '13.x',
        'is_pinned' => true,
    ]);

    StackItem::create([
        'user_id' => $user->id,
        'name' => 'Rust',
        'category' => 'backend',
        'status' => 'learning',
    ]);

    $this->actingAs($user)
        ->get(route('stack.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Stack/Index')
            ->has('items', 2)
            ->where('summary.total', 2)
            ->where('summary.core', 1)
            ->where('summary.learning', 1)
            ->where('summary.evaluating', 0)
        );
});

test('users can create update pin and delete stack items', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('stack.store'), [
            'name' => 'Vue 3',
            'category' => 'frontend',
            'status' => 'core',
            'version' => '3.5',
            'url' => 'https://vuejs.org',
            'notes' => 'Composition API + TypeScript',
            'is_pinned' => false,
        ])
        ->assertRedirect();

    $item = StackItem::where('user_id', $user->id)->firstOrFail();
    expect($item->name)->toBe('Vue 3')
        ->and($item->category)->toBe('frontend')
        ->and($item->is_pinned)->toBeFalse();

    $this->actingAs($user)
        ->patch(route('stack.pin', $item))
        ->assertRedirect();

    expect($item->refresh()->is_pinned)->toBeTrue();

    $this->actingAs($user)
        ->patch(route('stack.update', $item), [
            'name' => 'Vue 3',
            'category' => 'frontend',
            'status' => 'proficient',
            'version' => '3.5.13',
            'url' => 'https://vuejs.org',
            'notes' => 'Updated notes',
            'is_pinned' => true,
        ])
        ->assertRedirect();

    $item->refresh();
    expect($item->status)->toBe('proficient')
        ->and($item->version)->toBe('3.5.13')
        ->and($item->notes)->toBe('Updated notes');

    $this->actingAs($user)
        ->delete(route('stack.destroy', $item))
        ->assertRedirect();

    expect(StackItem::find($item->id))->toBeNull();
});

test('users cannot modify another users stack item', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $item = StackItem::create([
        'user_id' => $owner->id,
        'name' => 'PostgreSQL',
        'category' => 'database',
        'status' => 'core',
    ]);

    $this->actingAs($otherUser)
        ->patch(route('stack.update', $item), [
            'name' => 'MySQL',
            'category' => 'database',
            'status' => 'core',
        ])
        ->assertForbidden();

    $this->actingAs($otherUser)
        ->patch(route('stack.pin', $item))
        ->assertForbidden();

    $this->actingAs($otherUser)
        ->delete(route('stack.destroy', $item))
        ->assertForbidden();
});
