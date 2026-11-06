<?php

namespace App\Http\Controllers;

use App\Models\CareerEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CareerEntryController extends Controller
{
    public function index(Request $request): Response
    {
        $entries = CareerEntry::where('user_id', $request->user()->id)
            ->orderByRaw("CASE status WHEN 'in_progress' THEN 1 WHEN 'planned' THEN 2 WHEN 'achieved' THEN 3 ELSE 4 END")
            ->orderByRaw('target_date IS NULL')
            ->orderBy('target_date')
            ->orderByDesc('updated_at')
            ->get();

        $summary = [
            'active_goals' => $entries->where('type', 'goal')->whereIn('status', ['planned', 'in_progress'])->count(),
            'applications' => $entries->where('type', 'application')->whereIn('status', ['planned', 'in_progress'])->count(),
            'achievements' => $entries->where('status', 'achieved')->count(),
            'certifications' => $entries->where('type', 'certification')->count(),
        ];

        return Inertia::render('Career/Index', [
            'entries' => $entries,
            'summary' => $summary,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['user_id'] = $request->user()->id;
        $data['completed_at'] = $data['status'] === 'achieved' ? now() : null;

        CareerEntry::create($data);

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Career entry added.',
        ]);
    }

    public function update(Request $request, CareerEntry $careerEntry): RedirectResponse
    {
        $this->authorizeOwner($request, $careerEntry);

        $data = $this->validated($request);

        $data['completed_at'] = match (true) {
            $data['status'] === 'achieved' && $careerEntry->completed_at === null => now(),
            $data['status'] !== 'achieved' => null,
            default => $careerEntry->completed_at,
        };

        $careerEntry->update($data);

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Career entry updated.',
        ]);
    }

    public function destroy(Request $request, CareerEntry $careerEntry): RedirectResponse
    {
        $this->authorizeOwner($request, $careerEntry);

        $careerEntry->delete();

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Career entry removed.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'type' => ['required', 'in:goal,achievement,application,certification'],
            'organization' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'in:planned,in_progress,achieved,archived'],
            'target_date' => ['nullable', 'date'],
        ]);
    }

    private function authorizeOwner(Request $request, CareerEntry $careerEntry): void
    {
        abort_unless($careerEntry->user_id === $request->user()->id, 403);
    }
}
