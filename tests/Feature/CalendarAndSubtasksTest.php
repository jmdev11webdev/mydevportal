<?php

use App\Models\CalendarEvent;
use App\Models\Task;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot access the calendar page', function () {
    $this->get(route('calendar.index'))->assertRedirect(route('login'));
});

test('authenticated developers can view the interactive calendar with connected streams', function () {
    $user = User::factory()->create();

    $project = $user->projects()->create([
        'name' => 'Orbit',
        'status' => 'active',
        'color' => 'indigo',
        'started_at' => '2026-11-10',
        'due_at' => '2026-11-25',
    ]);

    $event = $user->calendarEvents()->create([
        'project_id' => $project->id,
        'title' => 'Orbit Architecture Sprint',
        'type' => 'sprint',
        'color' => 'indigo',
        'starts_at' => '2026-11-14',
        'is_all_day' => false,
    ]);

    $user->tasks()->create([
        'project_id' => $project->id,
        'calendar_event_id' => $event->id,
        'title' => 'Ship event task',
        'status' => 'todo',
        'priority' => 'high',
        'due_date' => '2026-11-14',
        'subtasks' => [
            ['id' => 'sub-1', 'title' => 'Write migration', 'completed' => true],
            ['id' => 'sub-2', 'title' => 'Write controller', 'completed' => false],
        ],
    ]);

    $this->actingAs($user)
        ->get(route('calendar.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Calendar/Index')
            ->has('events', 1)
            ->has('tasks', 1)
            ->has('projects', 1)
            ->where('events.0.title', 'Orbit Architecture Sprint')
            ->where('tasks.0.subtasks.0.title', 'Write migration')
        );
});

test('developers can schedule an event with tasks and subtasks bundled inside', function () {
    $user = User::factory()->create();

    $existingTask = $user->tasks()->create([
        'title' => 'Existing task to attach',
        'status' => 'todo',
        'priority' => 'medium',
    ]);

    $this->actingAs($user)
        ->post(route('calendar.store'), [
            'title' => 'Release v2.0 Sprint',
            'description' => 'Prepare release artifacts',
            'type' => 'release',
            'color' => 'emerald',
            'starts_at' => '2026-11-15',
            'ends_at' => '2026-11-16',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'location' => 'Prod Pipeline',
            'is_all_day' => false,
            'task_ids' => [$existingTask->id],
            'new_tasks' => [
                [
                    'title' => 'Tag release v2.0',
                    'priority' => 'high',
                    'subtasks' => [
                        ['title' => 'Run test suite'],
                        ['title' => 'Draft release notes'],
                    ],
                ],
            ],
        ])
        ->assertRedirect();

    $event = CalendarEvent::where('user_id', $user->id)->first();

    expect($event)->not->toBeNull()
        ->and($event->title)->toBe('Release v2.0 Sprint')
        ->and($existingTask->fresh()->calendar_event_id)->toBe($event->id);

    $bundledNewTask = Task::where('calendar_event_id', $event->id)
        ->where('title', 'Tag release v2.0')
        ->first();

    expect($bundledNewTask)->not->toBeNull()
        ->and($bundledNewTask->subtasks)->toHaveCount(2)
        ->and($bundledNewTask->subtasks[0]['title'])->toBe('Run test suite');
});

test('developers can add tasks directly inside an event and manage subtasks on tasks', function () {
    $user = User::factory()->create();

    $event = $user->calendarEvents()->create([
        'title' => 'Deep Work Block',
        'type' => 'deep_work',
        'color' => 'violet',
        'starts_at' => '2026-11-14',
        'is_all_day' => true,
    ]);

    $this->actingAs($user)
        ->post(route('calendar.tasks.store', $event), [
            'title' => 'Optimize query indexes',
            'priority' => 'high',
            'due_date' => '2026-11-14',
        ])
        ->assertRedirect();

    $task = Task::where('calendar_event_id', $event->id)->first();
    expect($task)->not->toBeNull()
        ->and($task->title)->toBe('Optimize query indexes');

    // Add a subtask to the task
    $this->actingAs($user)
        ->post(route('tasks.subtasks.store', $task), [
            'title' => 'Add composite index on tasks',
        ])
        ->assertRedirect();

    $task->refresh();
    expect($task->subtasks)->toHaveCount(1)
        ->and($task->subtasks[0]['title'])->toBe('Add composite index on tasks')
        ->and($task->subtasks[0]['completed'])->toBeFalse();

    $subtaskId = $task->subtasks[0]['id'];

    // Toggle the subtask
    $this->actingAs($user)
        ->patch(route('tasks.subtasks.toggle', [$task, $subtaskId]))
        ->assertRedirect();

    expect($task->fresh()->subtasks[0]['completed'])->toBeTrue();

    // Remove the subtask
    $this->actingAs($user)
        ->delete(route('tasks.subtasks.destroy', [$task, $subtaskId]))
        ->assertRedirect();

    expect($task->fresh()->subtasks)->toBeEmpty();
});
