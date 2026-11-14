<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function index(Request $request): Response
    {
        $tasks = Task::where('user_id', $request->user()->id)
            ->with([
                'project:id,name,color',
                'event:id,title,color,starts_at',
            ])
            ->orderByRaw("CASE status WHEN 'todo' THEN 1 WHEN 'in_progress' THEN 2 ELSE 3 END")
            ->orderByRaw("CASE priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END")
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->get();

        $projects = Project::where('user_id', $request->user()->id)
            ->orderBy('name')
            ->get(['id', 'name', 'color']);

        $events = CalendarEvent::where('user_id', $request->user()->id)
            ->orderByDesc('starts_at')
            ->get(['id', 'title', 'color', 'starts_at']);

        return Inertia::render('Tasks/Index', [
            'tasks' => $tasks,
            'projects' => $projects,
            'events' => $events,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['completed_at'] = $data['status'] === 'done' ? now() : null;

        $request->user()->tasks()->create($data);

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Task added.',
        ]);
    }

    public function update(Request $request, Task $task): RedirectResponse
    {
        $this->authorizeOwner($request, $task);

        $data = $this->validated($request, $task);

        $data['completed_at'] = match (true) {
            $data['status'] === 'done' && $task->completed_at === null => now(),
            $data['status'] !== 'done' => null,
            default => $task->completed_at,
        };

        $task->update($data);

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Task updated.',
        ]);
    }

    public function toggle(Request $request, Task $task): RedirectResponse
    {
        $this->authorizeOwner($request, $task);

        $isDone = $task->status === 'done';

        $task->update([
            'status' => $isDone ? 'todo' : 'done',
            'completed_at' => $isDone ? null : now(),
        ]);

        return back()->with('toast', [
            'type' => 'success',
            'message' => $isDone ? 'Task reopened.' : 'Nice — task completed!',
        ]);
    }

    public function storeSubtask(Request $request, Task $task): RedirectResponse
    {
        $this->authorizeOwner($request, $task);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
        ]);

        $subtasks = is_array($task->subtasks) ? $task->subtasks : [];
        $subtasks[] = [
            'id' => (string) Str::uuid(),
            'title' => trim($validated['title']),
            'completed' => false,
        ];

        $task->update(['subtasks' => array_values($subtasks)]);

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Subtask added.',
        ]);
    }

    public function toggleSubtask(Request $request, Task $task, string $subtaskId): RedirectResponse
    {
        $this->authorizeOwner($request, $task);

        $subtasks = collect(is_array($task->subtasks) ? $task->subtasks : [])
            ->map(function (array $subtask) use ($subtaskId): array {
                if ($subtask['id'] === $subtaskId) {
                    $subtask['completed'] = ! $subtask['completed'];
                }

                return $subtask;
            })
            ->values()
            ->all();

        $task->update(['subtasks' => $subtasks]);

        return back();
    }

    public function destroySubtask(Request $request, Task $task, string $subtaskId): RedirectResponse
    {
        $this->authorizeOwner($request, $task);

        $subtasks = collect(is_array($task->subtasks) ? $task->subtasks : [])
            ->reject(fn (array $subtask): bool => $subtask['id'] === $subtaskId)
            ->values()
            ->all();

        $task->update(['subtasks' => $subtasks]);

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Subtask removed.',
        ]);
    }

    public function destroy(Request $request, Task $task): RedirectResponse
    {
        $this->authorizeOwner($request, $task);

        $task->delete();

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Task removed.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Task $task = null): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'project_id' => [
                'nullable',
                'integer',
                Rule::exists('projects', 'id')->where('user_id', $request->user()->id),
            ],
            'calendar_event_id' => [
                'nullable',
                'integer',
                Rule::exists('calendar_events', 'id')->where('user_id', $request->user()->id),
            ],
            'status' => ['required', 'in:todo,in_progress,done'],
            'priority' => ['required', 'in:low,medium,high'],
            'due_date' => ['nullable', 'date'],
            'subtasks' => ['nullable', 'array', 'max:50'],
            'subtasks.*.id' => ['nullable', 'string', 'max:64'],
            'subtasks.*.title' => ['required', 'string', 'max:160'],
            'subtasks.*.completed' => ['nullable', 'boolean'],
        ]);

        if ($request->has('subtasks')) {
            $normalizedSubtasks = [];
            $rawSubtasks = is_array($validated['subtasks'] ?? null) ? $validated['subtasks'] : [];
            foreach ($rawSubtasks as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $subTitle = trim((string) ($item['title'] ?? ''));
                if ($subTitle !== '') {
                    $normalizedSubtasks[] = [
                        'id' => ! empty($item['id']) ? (string) $item['id'] : (string) Str::uuid(),
                        'title' => $subTitle,
                        'completed' => (bool) ($item['completed'] ?? false),
                    ];
                }
            }
            $validated['subtasks'] = $normalizedSubtasks;
        } elseif ($task !== null) {
            $validated['subtasks'] = $task->subtasks ?? [];
        } else {
            $validated['subtasks'] = [];
        }

        if (! $request->has('calendar_event_id') && $task !== null) {
            $validated['calendar_event_id'] = $task->calendar_event_id;
        }

        return $validated;
    }

    private function authorizeOwner(Request $request, Task $task): void
    {
        abort_unless($task->user_id === $request->user()->id, 403);
    }
}
