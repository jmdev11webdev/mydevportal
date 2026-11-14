<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use App\Models\CareerEntry;
use App\Models\Project;
use App\Models\Task;
use Database\Seeders\DemoSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        if ($user->email === DemoSeeder::EMAIL && ! $user->calendarEvents()->exists()) {
            app(DemoSeeder::class)->backfillCalendarForExistingUser($user);
        }

        $events = CalendarEvent::where('user_id', $user->id)
            ->with([
                'project:id,name,color',
                'tasks' => fn ($query) => $query
                    ->with('project:id,name,color')
                    ->orderByRaw("CASE status WHEN 'todo' THEN 1 WHEN 'in_progress' THEN 2 ELSE 3 END")
                    ->orderByRaw("CASE priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END")
                    ->orderBy('due_date'),
            ])
            ->orderBy('starts_at')
            ->orderBy('start_time')
            ->get();

        $tasks = Task::where('user_id', $user->id)
            ->with([
                'project:id,name,color',
                'event:id,title,color,starts_at',
            ])
            ->orderByRaw("CASE status WHEN 'todo' THEN 1 WHEN 'in_progress' THEN 2 ELSE 3 END")
            ->orderByRaw("CASE priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END")
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->get();

        $projects = Project::where('user_id', $user->id)
            ->orderBy('name')
            ->get(['id', 'name', 'color', 'status', 'started_at', 'due_at']);

        $career = CareerEntry::where('user_id', $user->id)
            ->whereNotNull('target_date')
            ->orderBy('target_date')
            ->get(['id', 'title', 'type', 'organization', 'status', 'target_date']);

        return Inertia::render('Calendar/Index', [
            'events' => $events,
            'tasks' => $tasks,
            'projects' => $projects,
            'career' => $career,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        /** @var CalendarEvent $event */
        $event = $request->user()->calendarEvents()->create($data['event']);

        $this->syncEventTasks($request, $event, $data['task_ids'], $data['new_tasks']);

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Event scheduled on calendar.',
        ]);
    }

    public function update(Request $request, CalendarEvent $calendarEvent): RedirectResponse
    {
        $this->authorizeOwner($request, $calendarEvent);

        $data = $this->validated($request);

        $calendarEvent->update($data['event']);

        $this->syncEventTasks(
            $request,
            $calendarEvent,
            $data['task_ids'],
            $data['new_tasks'],
            $request->has('task_ids')
        );

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Calendar event updated.',
        ]);
    }

    public function storeTask(Request $request, CalendarEvent $calendarEvent): RedirectResponse
    {
        $this->authorizeOwner($request, $calendarEvent);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'priority' => ['nullable', 'in:low,medium,high'],
            'due_date' => ['nullable', 'date'],
            'subtasks' => ['nullable', 'array', 'max:30'],
            'subtasks.*.title' => ['required', 'string', 'max:160'],
        ]);

        $subtasks = [];
        $rawSubtasks = is_array($validated['subtasks'] ?? null) ? $validated['subtasks'] : [];
        foreach ($rawSubtasks as $item) {
            if (! is_array($item)) {
                continue;
            }
            $subTitle = trim((string) ($item['title'] ?? ''));
            if ($subTitle !== '') {
                $subtasks[] = [
                    'id' => (string) Str::uuid(),
                    'title' => $subTitle,
                    'completed' => false,
                ];
            }
        }

        $request->user()->tasks()->create([
            'title' => trim($validated['title']),
            'project_id' => $calendarEvent->project_id,
            'calendar_event_id' => $calendarEvent->id,
            'status' => 'todo',
            'priority' => $validated['priority'] ?? 'medium',
            'due_date' => $validated['due_date'] ?? $calendarEvent->starts_at->toDateString(),
            'subtasks' => $subtasks,
        ]);

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Task added to event.',
        ]);
    }

    public function destroy(Request $request, CalendarEvent $calendarEvent): RedirectResponse
    {
        $this->authorizeOwner($request, $calendarEvent);

        $calendarEvent->delete();

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Calendar event removed.',
        ]);
    }

    /**
     * @return array{
     *     event: array<string, mixed>,
     *     task_ids: array<int, int>,
     *     new_tasks: array<int, array<string, mixed>>
     * }
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'project_id' => [
                'nullable',
                'integer',
                Rule::exists('projects', 'id')->where('user_id', $request->user()->id),
            ],
            'type' => ['required', 'in:sprint,meeting,release,deep_work,deadline,personal'],
            'color' => ['required', 'in:indigo,emerald,amber,rose,sky,violet'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'start_time' => ['nullable', 'string', 'max:10'],
            'end_time' => ['nullable', 'string', 'max:10'],
            'location' => ['nullable', 'string', 'max:255'],
            'is_all_day' => ['nullable', 'boolean'],
            'task_ids' => ['nullable', 'array'],
            'task_ids.*' => [
                'integer',
                Rule::exists('tasks', 'id')->where('user_id', $request->user()->id),
            ],
            'new_tasks' => ['nullable', 'array', 'max:25'],
            'new_tasks.*.title' => ['required', 'string', 'max:160'],
            'new_tasks.*.priority' => ['nullable', 'in:low,medium,high'],
            'new_tasks.*.subtasks' => ['nullable', 'array', 'max:20'],
            'new_tasks.*.subtasks.*.title' => ['required', 'string', 'max:160'],
        ]);

        return [
            'event' => [
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'project_id' => $validated['project_id'] ?? null,
                'type' => $validated['type'],
                'color' => $validated['color'],
                'starts_at' => $validated['starts_at'],
                'ends_at' => $validated['ends_at'] ?? null,
                'start_time' => ($validated['is_all_day'] ?? false) ? null : ($validated['start_time'] ?? null),
                'end_time' => ($validated['is_all_day'] ?? false) ? null : ($validated['end_time'] ?? null),
                'location' => $validated['location'] ?? null,
                'is_all_day' => (bool) ($validated['is_all_day'] ?? false),
            ],
            'task_ids' => array_values(array_unique(array_map('intval', $validated['task_ids'] ?? []))),
            'new_tasks' => $validated['new_tasks'] ?? [],
        ];
    }

    /**
     * @param  array<int, int>  $taskIds
     * @param  array<int, array<string, mixed>>  $newTasks
     */
    private function syncEventTasks(
        Request $request,
        CalendarEvent $event,
        array $taskIds,
        array $newTasks,
        bool $shouldDetachUnlisted = false,
    ): void {
        $userId = $request->user()->id;

        if ($shouldDetachUnlisted) {
            Task::where('user_id', $userId)
                ->where('calendar_event_id', $event->id)
                ->when(
                    count($taskIds) > 0,
                    fn ($query) => $query->whereNotIn('id', $taskIds)
                )
                ->update(['calendar_event_id' => null]);
        }

        if (count($taskIds) > 0) {
            Task::where('user_id', $userId)
                ->whereIn('id', $taskIds)
                ->update(['calendar_event_id' => $event->id]);
        }

        foreach ($newTasks as $newTask) {
            $title = trim((string) ($newTask['title'] ?? ''));
            if ($title === '') {
                continue;
            }

            $subtasks = [];
            $rawSubtasks = is_array($newTask['subtasks'] ?? null) ? $newTask['subtasks'] : [];
            foreach ($rawSubtasks as $sub) {
                if (! is_array($sub)) {
                    continue;
                }
                $subTitle = trim((string) ($sub['title'] ?? ''));
                if ($subTitle !== '') {
                    $subtasks[] = [
                        'id' => (string) Str::uuid(),
                        'title' => $subTitle,
                        'completed' => false,
                    ];
                }
            }

            $request->user()->tasks()->create([
                'title' => $title,
                'project_id' => $event->project_id,
                'calendar_event_id' => $event->id,
                'status' => 'todo',
                'priority' => $newTask['priority'] ?? 'medium',
                'due_date' => $event->starts_at->toDateString(),
                'subtasks' => $subtasks,
            ]);
        }
    }

    private function authorizeOwner(Request $request, CalendarEvent $calendarEvent): void
    {
        abort_unless($calendarEvent->user_id === $request->user()->id, 403);
    }
}
