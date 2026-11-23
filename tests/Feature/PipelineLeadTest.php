<?php

use App\Models\PipelineLead;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot access the pipeline page', function () {
    $this->get(route('pipeline.index'))->assertRedirect(route('login'));
});

test('authenticated users can view their pipeline leads and stage summary', function () {
    $user = User::factory()->create();

    PipelineLead::create([
        'user_id' => $user->id,
        'name' => 'Jane Doe',
        'company' => 'Acme Labs',
        'stage' => 'new',
        'value' => 2500,
    ]);

    PipelineLead::create([
        'user_id' => $user->id,
        'name' => 'Sam Lee',
        'company' => 'Orbit Studio',
        'stage' => 'lost',
        'value' => 1200,
        'lost_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('pipeline.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Pipeline/Index')
            ->has('leads', 2)
            ->where('summary.total_count', 2)
            ->where('summary.active_value', 2500)
            ->where('summary.stages.new.count', 1)
            ->where('summary.stages.lost.count', 1)
        );
});

test('users can create update and delete pipeline leads including lost stage', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('pipeline.store'), [
            'name' => 'Alex Rivera',
            'company' => 'Northstar',
            'contact' => 'alex@northstar.dev',
            'stage' => 'contacted',
            'source' => 'LinkedIn',
            'value' => 4000,
            'follow_up_date' => '2026-11-25',
            'notes' => 'Sent intro deck',
        ])
        ->assertRedirect();

    $lead = PipelineLead::where('user_id', $user->id)->firstOrFail();
    expect($lead->stage)->toBe('contacted')
        ->and($lead->won_at)->toBeNull()
        ->and($lead->lost_at)->toBeNull();

    // Inline partial stage update to won
    $this->actingAs($user)
        ->patch(route('pipeline.update', $lead), [
            'stage' => 'won',
        ])
        ->assertRedirect();

    $lead->refresh();
    expect($lead->stage)->toBe('won')
        ->and($lead->won_at)->not->toBeNull()
        ->and($lead->lost_at)->toBeNull();

    // Inline partial stage update to lost
    $this->actingAs($user)
        ->patch(route('pipeline.update', $lead), [
            'stage' => 'lost',
            'notes' => 'Budget frozen',
        ])
        ->assertRedirect();

    $lead->refresh();
    expect($lead->stage)->toBe('lost')
        ->and($lead->won_at)->toBeNull()
        ->and($lead->lost_at)->not->toBeNull()
        ->and($lead->notes)->toBe('Budget frozen');

    $this->actingAs($user)
        ->delete(route('pipeline.destroy', $lead))
        ->assertRedirect();

    expect(PipelineLead::find($lead->id))->toBeNull();
});

test('users cannot update or delete another users pipeline lead', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $lead = PipelineLead::create([
        'user_id' => $owner->id,
        'name' => 'Private Lead',
        'stage' => 'proposal',
    ]);

    $this->actingAs($otherUser)
        ->patch(route('pipeline.update', $lead), ['stage' => 'lost'])
        ->assertForbidden();

    $this->actingAs($otherUser)
        ->delete(route('pipeline.destroy', $lead))
        ->assertForbidden();
});
