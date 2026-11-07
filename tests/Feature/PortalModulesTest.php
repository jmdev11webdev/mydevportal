<?php

use App\Models\CareerEntry;
use App\Models\LearningResource;
use App\Models\Note;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\DemoSeeder;

test('developers can create, update, and delete projects', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('projects.store'), [
            'name' => 'Orbit',
            'description' => 'Privacy-first analytics',
            'status' => 'active',
            'color' => 'indigo',
            'tech_stack' => 'Laravel, Vue 3, TypeScript',
            'repository_url' => 'https://github.com/example/orbit',
            'live_url' => 'https://orbit.example.com',
            'started_at' => '2026-11-01',
            'due_at' => '2026-12-01',
        ])
        ->assertRedirect();

    $project = Project::where('user_id', $user->id)->first();

    expect($project)->not->toBeNull()
        ->and($project->name)->toBe('Orbit')
        ->and($project->tech_stack)->toBe(['Laravel', 'Vue 3', 'TypeScript']);

    $this->actingAs($user)
        ->patch(route('projects.update', $project), [
            'name' => 'Orbit v2',
            'description' => 'Updated description',
            'status' => 'completed',
            'color' => 'emerald',
            'tech_stack' => 'Laravel, SQLite',
        ])
        ->assertRedirect();

    expect($project->fresh()->name)->toBe('Orbit v2')
        ->and($project->fresh()->status)->toBe('completed');

    $this->actingAs($user)
        ->delete(route('projects.destroy', $project))
        ->assertRedirect();

    expect(Project::find($project->id))->toBeNull();
});

test('developers can create, update, toggle, and delete tasks', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $project = $user->projects()->create([
        'name' => 'My Project',
        'status' => 'active',
        'color' => 'indigo',
    ]);

    $otherProject = $otherUser->projects()->create([
        'name' => 'Other Project',
        'status' => 'active',
        'color' => 'rose',
    ]);

    $this->actingAs($user)
        ->post(route('tasks.store'), [
            'title' => 'Ship feature tests',
            'description' => 'Ensure user_id is persisted',
            'project_id' => $project->id,
            'status' => 'todo',
            'priority' => 'high',
            'due_date' => '2026-11-10',
        ])
        ->assertRedirect();

    $task = Task::where('user_id', $user->id)->first();

    expect($task)->not->toBeNull()
        ->and($task->title)->toBe('Ship feature tests')
        ->and($task->project_id)->toBe($project->id)
        ->and($task->completed_at)->toBeNull();

    $this->actingAs($user)
        ->post(route('tasks.store'), [
            'title' => 'Task on another user project',
            'project_id' => $otherProject->id,
            'status' => 'todo',
            'priority' => 'medium',
        ])
        ->assertSessionHasErrors('project_id');

    $this->actingAs($user)
        ->patch(route('tasks.toggle', $task))
        ->assertRedirect();

    expect($task->fresh()->status)->toBe('done')
        ->and($task->fresh()->completed_at)->not->toBeNull();

    $this->actingAs($user)
        ->patch(route('tasks.update', $task), [
            'title' => 'Ship feature tests (updated)',
            'description' => 'Updated notes',
            'project_id' => null,
            'status' => 'in_progress',
            'priority' => 'medium',
            'due_date' => null,
        ])
        ->assertRedirect();

    expect($task->fresh()->title)->toBe('Ship feature tests (updated)')
        ->and($task->fresh()->status)->toBe('in_progress')
        ->and($task->fresh()->completed_at)->toBeNull();

    $this->actingAs($user)
        ->delete(route('tasks.destroy', $task))
        ->assertRedirect();

    expect(Task::find($task->id))->toBeNull();
});

test('developers can create, update, pin, and delete notes', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('notes.store'), [
            'title' => 'SQLite WAL mode',
            'body' => 'PRAGMA journal_mode = WAL;',
            'tags' => 'sqlite, database, sqlite',
            'color' => 'amber',
            'is_pinned' => false,
        ])
        ->assertRedirect();

    $note = Note::where('user_id', $user->id)->first();

    expect($note)->not->toBeNull()
        ->and($note->title)->toBe('SQLite WAL mode')
        ->and($note->tags)->toBe(['sqlite', 'database'])
        ->and($note->is_pinned)->toBeFalse();

    $this->actingAs($user)
        ->patch(route('notes.pin', $note))
        ->assertRedirect();

    expect($note->fresh()->is_pinned)->toBeTrue();

    $this->actingAs($user)
        ->patch(route('notes.update', $note), [
            'title' => 'SQLite Pragmas',
            'body' => 'Updated note body',
            'tags' => 'sqlite',
            'color' => 'emerald',
            'is_pinned' => true,
        ])
        ->assertRedirect();

    expect($note->fresh()->title)->toBe('SQLite Pragmas')
        ->and($note->fresh()->color)->toBe('emerald');

    $this->actingAs($user)
        ->delete(route('notes.destroy', $note))
        ->assertRedirect();

    expect(Note::find($note->id))->toBeNull();
});

test('developers can create, update, log progress, and delete learning resources', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('learning.store'), [
            'title' => 'Designing Data-Intensive Applications',
            'provider' => "O'Reilly",
            'url' => 'https://example.com/ddia',
            'category' => 'Architecture',
            'format' => 'book',
            'status' => 'in_progress',
            'progress' => 40,
            'notes' => 'Chapter 5 on replication',
        ])
        ->assertRedirect();

    $resource = LearningResource::where('user_id', $user->id)->first();

    expect($resource)->not->toBeNull()
        ->and($resource->title)->toBe('Designing Data-Intensive Applications')
        ->and($resource->progress)->toBe(40)
        ->and($resource->completed_at)->toBeNull();

    $this->actingAs($user)
        ->patch(route('learning.progress', $resource), [
            'progress' => 100,
        ])
        ->assertRedirect();

    expect($resource->fresh()->progress)->toBe(100)
        ->and($resource->fresh()->status)->toBe('completed')
        ->and($resource->fresh()->completed_at)->not->toBeNull();

    $this->actingAs($user)
        ->patch(route('learning.update', $resource), [
            'title' => 'DDIA (2nd Read)',
            'provider' => "O'Reilly",
            'url' => 'https://example.com/ddia',
            'category' => 'Architecture',
            'format' => 'book',
            'status' => 'in_progress',
            'progress' => 50,
            'notes' => 'Re-reading consensus',
        ])
        ->assertRedirect();

    expect($resource->fresh()->title)->toBe('DDIA (2nd Read)')
        ->and($resource->fresh()->status)->toBe('in_progress')
        ->and($resource->fresh()->completed_at)->toBeNull();

    $this->actingAs($user)
        ->delete(route('learning.destroy', $resource))
        ->assertRedirect();

    expect(LearningResource::find($resource->id))->toBeNull();
});

test('developers can create, update, and delete career entries', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('career.store'), [
            'title' => 'Senior Product Engineer',
            'type' => 'goal',
            'organization' => 'Acme Corp',
            'description' => 'Lead end-to-end product architecture',
            'status' => 'in_progress',
            'target_date' => '2026-12-31',
        ])
        ->assertRedirect();

    $entry = CareerEntry::where('user_id', $user->id)->first();

    expect($entry)->not->toBeNull()
        ->and($entry->title)->toBe('Senior Product Engineer')
        ->and($entry->completed_at)->toBeNull();

    $this->actingAs($user)
        ->patch(route('career.update', $entry), [
            'title' => 'Senior Product Engineer',
            'type' => 'achievement',
            'organization' => 'Acme Corp',
            'description' => 'Offer accepted',
            'status' => 'achieved',
            'target_date' => '2026-12-31',
        ])
        ->assertRedirect();

    expect($entry->fresh()->status)->toBe('achieved')
        ->and($entry->fresh()->completed_at)->not->toBeNull();

    $this->actingAs($user)
        ->delete(route('career.destroy', $entry))
        ->assertRedirect();

    expect(CareerEntry::find($entry->id))->toBeNull();
});

test('demo login seeds the demo account with all workspace data and signs in', function () {
    $this->get(route('demo.login'))
        ->assertRedirect(route('dashboard'));

    $demoUser = User::where('email', DemoSeeder::EMAIL)->first();

    expect($demoUser)->not->toBeNull();
    $this->assertAuthenticatedAs($demoUser);

    expect($demoUser->projects()->count())->toBeGreaterThan(0)
        ->and($demoUser->tasks()->count())->toBeGreaterThan(0)
        ->and($demoUser->notes()->count())->toBeGreaterThan(0)
        ->and($demoUser->learningResources()->count())->toBeGreaterThan(0)
        ->and($demoUser->careerEntries()->count())->toBeGreaterThan(0);
});
