<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function index(Request $request): Response
    {
        $tasks = Task::where('user_id', $request->user()->id)
            ->with('project:id,name,color')
            ->orderByRaw("CASE status WHEN 'todo' THEN 1 WHEN 'in_progress' THEN 2 ELSE 3 END")
            ->orderByRaw("CASE priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END")
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->get();

        $projects = Project::where('user_id', $request->user()->id)
            ->orderBy('name')
            ->get(['id', 'name', 'color']);

        return Inertia::render('Tasks/Index', [
            'tasks' => $tasks,
            'projects' => $projects,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['user_id'] = $request->user()->id;
        $data['completed_at'] = $data['status'] === 'done' ? now() : null;

        Task::create($data);

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Task added.',
        ]);
    }

    public function update(Request $request, Task $task): RedirectResponse
    {
        $this->authorizeOwner($request, $task);

        $data = $this->validated($request);

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
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'status' => ['required', 'in:todo,in_progress,done'],
            'priority' => ['required', 'in:low,medium,high'],
            'due_date' => ['nullable', 'date'],
        ]);
    }

    private function authorizeOwner(Request $request, Task $task): void
    {
        abort_unless($task->user_id === $request->user()->id, 403);
    }
}
