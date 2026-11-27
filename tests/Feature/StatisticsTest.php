<?php

use App\Models\PipelineLead;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

test('guests are redirected from statistics page', function () {
    $this->get('/statistics')->assertRedirect('/login');
});

test('authenticated user can view overall statistics and dashboard overall card', function () {
    $user = User::factory()->create();

    $project = Project::create([
        'user_id' => $user->id,
        'name' => 'Orbit',
        'status' => 'completed',
        'color' => 'indigo',
    ]);

    Task::create([
        'user_id' => $user->id,
        'project_id' => $project->id,
        'title' => 'Ship release',
        'status' => 'done',
        'priority' => 'high',
        'completed_at' => now(),
    ]);

    PipelineLead::create([
        'user_id' => $user->id,
        'name' => 'Acme Corp',
        'stage' => 'won',
        'value' => 2500,
        'won_at' => now(),
    ]);

    $this->actingAs($user)
        ->get('/statistics')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Statistics/Index')
            ->where('statistics.projects.completed', 1)
            ->where('statistics.tasks.done', 1)
            ->where('statistics.pipeline.won_value', 2500)
        );

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('overall.tasks.done', 1)
            ->where('overall.pipeline.won_value', 2500)
        );
});
