<?php

namespace App\Support;

use App\Models\CalendarEvent;
use App\Models\CareerEntry;
use App\Models\LearningResource;
use App\Models\Note;
use App\Models\PipelineLead;
use App\Models\Project;
use App\Models\StackItem;
use App\Models\Task;
use App\Models\User;

class PortalStatistics
{
    /**
     * Build comprehensive statistics across all modules for a user.
     *
     * @return array<string, mixed>
     */
    public static function forUser(User $user): array
    {
        $today = now()->startOfDay();
        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();
        $monthStart = now()->startOfMonth();

        $projects = Project::where('user_id', $user->id)->get(['id', 'status']);
        $tasks = Task::where('user_id', $user->id)->get(['id', 'status', 'priority', 'due_date', 'completed_at', 'subtasks']);
        $leads = PipelineLead::where('user_id', $user->id)->get(['id', 'stage', 'value']);
        $career = CareerEntry::where('user_id', $user->id)->get(['id', 'type', 'status']);
        $learning = LearningResource::where('user_id', $user->id)->get(['id', 'status', 'progress']);
        $stack = StackItem::where('user_id', $user->id)->get(['id', 'status', 'category']);
        $notes = Note::where('user_id', $user->id)->get(['id', 'is_pinned']);
        $eventsCount = CalendarEvent::where('user_id', $user->id)->count();
        $upcomingEventsCount = CalendarEvent::where('user_id', $user->id)
            ->where(function ($query) use ($today) {
                $query->whereDate('starts_at', '>=', $today)
                    ->orWhereDate('ends_at', '>=', $today);
            })
            ->count();

        $tasksTotal = $tasks->count();
        $tasksDone = $tasks->where('status', 'done')->count();
        $tasksOpen = $tasksTotal - $tasksDone;
        $tasksOverdue = $tasks->filter(
            fn (Task $task): bool => $task->status !== 'done'
                && $task->due_date !== null
                && $task->due_date->lt($today),
        )->count();

        $subtasksTotal = 0;
        $subtasksDone = 0;
        foreach ($tasks as $task) {
            $list = is_array($task->subtasks) ? $task->subtasks : [];
            $subtasksTotal += count($list);
            $subtasksDone += collect($list)->where('completed', true)->count();
        }

        $completedDays = $tasks
            ->where('status', 'done')
            ->filter(fn (Task $task): bool => $task->completed_at !== null)
            ->map(fn (Task $task): string => $task->completed_at->toDateString())
            ->unique();

        $streak = 0;
        $cursor = $completedDays->contains($today->toDateString()) ? $today->copy() : $today->copy()->subDay();
        while ($completedDays->contains($cursor->toDateString())) {
            $streak++;
            $cursor = $cursor->subDay();
        }

        $wonLeads = $leads->where('stage', 'won');
        $lostLeads = $leads->where('stage', 'lost');
        $activeLeads = $leads->whereIn('stage', ['new', 'contacted', 'proposal']);
        $closedCount = $wonLeads->count() + $lostLeads->count();

        $learningAvgProgress = $learning->count() > 0
            ? (int) round($learning->avg('progress'))
            : 0;

        $weeklyTrend = collect(range(6, 0))->map(function (int $daysAgo) use ($tasks) {
            $date = now()->subDays($daysAgo);

            return [
                'label' => $date->format('D'),
                'date' => $date->toDateString(),
                'count' => $tasks->filter(
                    fn (Task $task): bool => $task->status === 'done'
                        && ($task->completed_at?->isSameDay($date) ?? false),
                )->count(),
            ];
        })->values()->all();

        return [
            'projects' => [
                'total' => $projects->count(),
                'active' => $projects->where('status', 'active')->count(),
                'planning' => $projects->where('status', 'planning')->count(),
                'paused' => $projects->where('status', 'paused')->count(),
                'completed' => $projects->where('status', 'completed')->count(),
                'completion_rate' => $projects->count() > 0
                    ? (int) round(($projects->where('status', 'completed')->count() / $projects->count()) * 100)
                    : 0,
            ],
            'tasks' => [
                'total' => $tasksTotal,
                'done' => $tasksDone,
                'open' => $tasksOpen,
                'overdue' => $tasksOverdue,
                'high_priority_open' => $tasks->where('status', '!=', 'done')->where('priority', 'high')->count(),
                'completion_rate' => $tasksTotal > 0 ? (int) round(($tasksDone / $tasksTotal) * 100) : 0,
                'subtasks_total' => $subtasksTotal,
                'subtasks_done' => $subtasksDone,
            ],
            'pipeline' => [
                'total' => $leads->count(),
                'active' => $activeLeads->count(),
                'won' => $wonLeads->count(),
                'lost' => $lostLeads->count(),
                'win_rate' => $closedCount > 0 ? (int) round(($wonLeads->count() / $closedCount) * 100) : 0,
                'active_value' => (int) $activeLeads->sum('value'),
                'won_value' => (int) $wonLeads->sum('value'),
                'total_value' => (int) $leads->sum('value'),
                'by_stage' => collect(PipelineLead::STAGES)->map(function (string $stage) use ($leads) {
                    $stageLeads = $leads->where('stage', $stage);

                    return [
                        'stage' => $stage,
                        'count' => $stageLeads->count(),
                        'value' => (int) $stageLeads->sum('value'),
                    ];
                })->values()->all(),
            ],
            'career' => [
                'total' => $career->count(),
                'planned' => $career->where('status', 'planned')->count(),
                'in_progress' => $career->where('status', 'in_progress')->count(),
                'achieved' => $career->where('status', 'achieved')->count(),
                'archived' => $career->where('status', 'archived')->count(),
                'goals' => $career->where('type', 'goal')->count(),
                'applications' => $career->where('type', 'application')->count(),
                'milestones' => $career->where('type', 'milestone')->count(),
            ],
            'learning' => [
                'total' => $learning->count(),
                'planned' => $learning->where('status', 'planned')->count(),
                'in_progress' => $learning->where('status', 'in_progress')->count(),
                'completed' => $learning->where('status', 'completed')->count(),
                'avg_progress' => $learningAvgProgress,
            ],
            'stack' => [
                'total' => $stack->count(),
                'core' => $stack->where('status', 'core')->count(),
                'proficient' => $stack->where('status', 'proficient')->count(),
                'learning' => $stack->where('status', 'learning')->count(),
                'evaluating' => $stack->where('status', 'evaluating')->count(),
                'retired' => $stack->where('status', 'retired')->count(),
            ],
            'workspace' => [
                'notes' => $notes->count(),
                'pinned_notes' => $notes->where('is_pinned', true)->count(),
                'events' => $eventsCount,
                'upcoming_events' => $upcomingEventsCount,
            ],
            'activity' => [
                'streak' => $streak,
                'done_this_week' => $tasks->filter(
                    fn (Task $task): bool => $task->status === 'done'
                        && $task->completed_at !== null
                        && $task->completed_at->between($weekStart, $weekEnd),
                )->count(),
                'done_this_month' => $tasks->filter(
                    fn (Task $task): bool => $task->status === 'done'
                        && $task->completed_at !== null
                        && $task->completed_at->gte($monthStart),
                )->count(),
                'weekly_trend' => $weeklyTrend,
            ],
        ];
    }
}
