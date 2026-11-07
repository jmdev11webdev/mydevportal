<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(Request $request): Response
    {
        $projects = Project::where('user_id', $request->user()->id)
            ->withCount([
                'tasks',
                'tasks as completed_tasks_count' => fn ($query) => $query->where('status', 'done'),
            ])
            ->orderByRaw("CASE status WHEN 'active' THEN 1 WHEN 'planning' THEN 2 WHEN 'paused' THEN 3 ELSE 4 END")
            ->orderByDesc('updated_at')
            ->get();

        return Inertia::render('Projects/Index', [
            'projects' => $projects,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->user()->projects()->create($this->validated($request));

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Project created.',
        ]);
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeOwner($request, $project);

        $project->update($this->validated($request));

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Project updated.',
        ]);
    }

    public function destroy(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeOwner($request, $project);

        $project->delete();

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Project removed.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        foreach (['repository_url', 'live_url'] as $field) {
            $url = trim((string) $request->input($field, ''));

            if ($url !== '' && ! preg_match('#^https?://#i', $url)) {
                $request->merge([$field => 'https://'.$url]);
            }
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'in:planning,active,paused,completed'],
            'color' => ['required', 'in:indigo,emerald,amber,rose,sky,violet'],
            'tech_stack' => ['nullable', 'string', 'max:500'],
            'repository_url' => ['nullable', 'url', 'max:255'],
            'live_url' => ['nullable', 'url', 'max:255'],
            'started_at' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date'],
        ]);

        $data['tech_stack'] = $this->parseTags($data['tech_stack'] ?? '');

        return $data;
    }

    /**
     * @return array<int, string>
     */
    private function parseTags(string $value): array
    {
        return Collection::make(explode(',', $value))
            ->map(fn (string $tag) => trim($tag))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function authorizeOwner(Request $request, Project $project): void
    {
        abort_unless($project->user_id === $request->user()->id, 403);
    }
}
