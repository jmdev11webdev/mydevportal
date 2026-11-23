<?php

namespace App\Http\Controllers;

use App\Models\PipelineLead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PipelineLeadController extends Controller
{
    public function index(Request $request): Response
    {
        $leads = PipelineLead::where('user_id', $request->user()->id)
            ->orderByRaw("CASE stage WHEN 'new' THEN 1 WHEN 'contacted' THEN 2 WHEN 'proposal' THEN 3 WHEN 'won' THEN 4 WHEN 'lost' THEN 5 ELSE 6 END")
            ->orderByRaw('follow_up_date IS NULL')
            ->orderBy('follow_up_date')
            ->orderByDesc('updated_at')
            ->get();

        $stageStats = [];
        foreach (PipelineLead::STAGES as $stage) {
            $matching = $leads->where('stage', $stage);
            $stageStats[$stage] = [
                'count' => $matching->count(),
                'value' => (int) $matching->sum('value'),
            ];
        }

        $summary = [
            'total_count' => $leads->count(),
            'active_value' => (int) $leads->whereIn('stage', ['new', 'contacted', 'proposal'])->sum('value'),
            'won_value' => (int) $leads->where('stage', 'won')->sum('value'),
            'stages' => $stageStats,
        ];

        return Inertia::render('Pipeline/Index', [
            'leads' => $leads,
            'summary' => $summary,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, isUpdate: false);
        $stage = $data['stage'] ?? 'new';
        $data['stage'] = $stage;
        $data['won_at'] = $stage === 'won' ? now() : null;
        $data['lost_at'] = $stage === 'lost' ? now() : null;

        $request->user()->pipelineLeads()->create($data);

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Lead added to pipeline.',
        ]);
    }

    public function update(Request $request, PipelineLead $pipelineLead): RedirectResponse
    {
        $this->authorizeOwner($request, $pipelineLead);

        $data = $this->validated($request, isUpdate: true);

        if (array_key_exists('stage', $data)) {
            $stage = $data['stage'];
            $data['won_at'] = match (true) {
                $stage === 'won' && $pipelineLead->won_at === null => now(),
                $stage !== 'won' => null,
                default => $pipelineLead->won_at,
            };
            $data['lost_at'] = match (true) {
                $stage === 'lost' && $pipelineLead->lost_at === null => now(),
                $stage !== 'lost' => null,
                default => $pipelineLead->lost_at,
            };
        }

        $pipelineLead->update($data);

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Lead updated.',
        ]);
    }

    public function destroy(Request $request, PipelineLead $pipelineLead): RedirectResponse
    {
        $this->authorizeOwner($request, $pipelineLead);

        $pipelineLead->delete();

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Lead removed.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, bool $isUpdate): array
    {
        return $request->validate([
            'name' => [$isUpdate ? 'sometimes' : 'required', 'required', 'string', 'max:160'],
            'company' => ['nullable', 'string', 'max:160'],
            'contact' => ['nullable', 'string', 'max:160'],
            'stage' => [$isUpdate ? 'sometimes' : 'nullable', 'string', Rule::in(PipelineLead::STAGES)],
            'source' => ['nullable', 'string', 'max:120'],
            'value' => ['nullable', 'integer', 'min:0', 'max:999999999'],
            'follow_up_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function authorizeOwner(Request $request, PipelineLead $pipelineLead): void
    {
        abort_unless($pipelineLead->user_id === $request->user()->id, 403);
    }
}
