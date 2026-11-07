<?php

namespace App\Http\Controllers;

use App\Models\LearningResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LearningResourceController extends Controller
{
    public function index(Request $request): Response
    {
        $resources = LearningResource::where('user_id', $request->user()->id)
            ->orderByRaw("CASE status WHEN 'in_progress' THEN 1 WHEN 'planned' THEN 2 ELSE 3 END")
            ->orderByDesc('updated_at')
            ->get();

        $summary = [
            'total' => $resources->count(),
            'in_progress' => $resources->where('status', 'in_progress')->count(),
            'completed' => $resources->where('status', 'completed')->count(),
            'average_progress' => (int) round($resources->avg('progress') ?? 0),
        ];

        return Inertia::render('Learning/Index', [
            'resources' => $resources,
            'summary' => $summary,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        if ($data['progress'] >= 100) {
            $data['status'] = 'completed';
        } elseif ($data['status'] === 'completed') {
            $data['progress'] = 100;
        }

        $data['completed_at'] = $data['status'] === 'completed' ? now() : null;

        $request->user()->learningResources()->create($data);

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Learning resource added.',
        ]);
    }

    public function update(Request $request, LearningResource $learningResource): RedirectResponse
    {
        $this->authorizeOwner($request, $learningResource);

        $data = $this->validated($request);

        if ($data['progress'] >= 100) {
            $data['status'] = 'completed';
        } elseif ($data['status'] === 'completed') {
            $data['progress'] = 100;
        }

        $data['completed_at'] = $data['status'] === 'completed'
            ? ($learningResource->completed_at ?? now())
            : null;

        $learningResource->update($data);

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Learning progress saved.',
        ]);
    }

    public function progress(Request $request, LearningResource $learningResource): RedirectResponse
    {
        $this->authorizeOwner($request, $learningResource);

        $validated = $request->validate([
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $progress = $validated['progress'];

        $learningResource->update([
            'progress' => $progress,
            'status' => match (true) {
                $progress >= 100 => 'completed',
                $progress > 0 => 'in_progress',
                default => 'planned',
            },
            'completed_at' => $progress >= 100 ? ($learningResource->completed_at ?? now()) : null,
        ]);

        return back()->with('toast', [
            'type' => 'success',
            'message' => $progress >= 100 ? 'Resource completed!' : 'Progress updated.',
        ]);
    }

    public function destroy(Request $request, LearningResource $learningResource): RedirectResponse
    {
        $this->authorizeOwner($request, $learningResource);

        $learningResource->delete();

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Learning resource removed.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $url = trim((string) $request->input('url', ''));

        if ($url !== '' && ! preg_match('#^https?://#i', $url)) {
            $request->merge(['url' => 'https://'.$url]);
        }

        return $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'provider' => ['nullable', 'string', 'max:120'],
            'url' => ['nullable', 'url', 'max:255'],
            'category' => ['required', 'string', 'max:60'],
            'format' => ['required', 'in:course,book,article,video,docs'],
            'status' => ['required', 'in:planned,in_progress,completed'],
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function authorizeOwner(Request $request, LearningResource $learningResource): void
    {
        abort_unless($learningResource->user_id === $request->user()->id, 403);
    }
}
