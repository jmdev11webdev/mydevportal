<?php

namespace App\Http\Controllers;

use App\Models\CareerEntry;
use App\Models\LearningResource;
use App\Models\Note;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $today = now()->startOfDay();
        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();

        $taskQuery = fn () => Task::where('user_id', $user->id);

        $openTasks = $taskQuery()->where('status', '!=', 'done');

        $stats = [
            'open_tasks' => (clone $openTasks)->count(),
            'done_this_week' => $taskQuery()
                ->where('status', 'done')
                ->whereBetween('completed_at', [$weekStart, $weekEnd])
                ->count(),
            'active_projects' => Project::where('user_id', $user->id)->where('status', 'active')->count(),
            'learning_in_progress' => LearningResource::where('user_id', $user->id)->where('status', 'in_progress')->count(),
            'overdue_tasks' => (clone $openTasks)->whereDate('due_date', '<', $today)->count(),
            'notes' => Note::where('user_id', $user->id)->count(),
            'career_active' => CareerEntry::where('user_id', $user->id)->whereIn('status', ['planned', 'in_progress'])->count(),
        ];

        $completedDays = $taskQuery()
            ->where('status', 'done')
            ->whereNotNull('completed_at')
            ->pluck('completed_at')
            ->map(fn ($completedAt) => $completedAt->toDateString());

        $streak = 0;
        $cursor = $completedDays->contains($today->toDateString()) ? $today->copy() : $today->copy()->subDay();

        while ($completedDays->contains($cursor->toDateString())) {
            $streak++;
            $cursor = $cursor->subDay();
        }

        $stats['streak'] = $streak;

        $todayTasks = $taskQuery()
            ->with('project:id,name,color')
            ->where('status', '!=', 'done')
            ->whereDate('due_date', '<=', $today)
            ->orderByRaw("CASE priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END")
            ->orderBy('due_date')
            ->limit(6)
            ->get();

        $upcomingTasks = $taskQuery()
            ->with('project:id,name,color')
            ->where('status', '!=', 'done')
            ->whereDate('due_date', '>', $today)
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        $projects = Project::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['active', 'planning'])
            ->withCount([
                'tasks',
                'tasks as completed_tasks_count' => fn ($query) => $query->where('status', 'done'),
            ])
            ->orderByRaw("CASE status WHEN 'active' THEN 1 ELSE 2 END")
            ->orderByDesc('updated_at')
            ->limit(4)
            ->get();

        $learning = LearningResource::where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->orderByDesc('updated_at')
            ->limit(3)
            ->get();

        $notes = Note::where('user_id', $user->id)
            ->orderByDesc('is_pinned')
            ->orderByDesc('updated_at')
            ->limit(4)
            ->get();

        $career = CareerEntry::where('user_id', $user->id)
            ->whereIn('status', ['planned', 'in_progress'])
            ->orderByRaw('target_date IS NULL')
            ->orderBy('target_date')
            ->limit(3)
            ->get();

        $weekActivity = $taskQuery()
            ->where('status', 'done')
            ->where('completed_at', '>=', now()->subDays(6)->startOfDay())
            ->get(['id', 'title', 'completed_at']);

        $week = collect(range(6, 0))->map(function (int $daysAgo) use ($weekActivity) {
            $date = now()->subDays($daysAgo);

            return [
                'label' => $date->format('D'),
                'date' => $date->toDateString(),
                'count' => $weekActivity->filter(
                    fn (Task $task, int $key): bool => $task->completed_at?->isSameDay($date) ?? false
                )->count(),
            ];
        })->values();

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'todayTasks' => $todayTasks,
            'upcomingTasks' => $upcomingTasks,
            'projects' => $projects,
            'learning' => $learning,
            'notes' => $notes,
            'career' => $career,
            'week' => $week,
        ]);
    }
}
