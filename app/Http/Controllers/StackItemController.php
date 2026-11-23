<?php

namespace App\Http\Controllers;

use App\Models\StackItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class StackItemController extends Controller
{
    public function index(Request $request): Response
    {
        $items = StackItem::where('user_id', $request->user()->id)
            ->orderByDesc('is_pinned')
            ->orderByRaw("CASE status WHEN 'core' THEN 1 WHEN 'proficient' THEN 2 WHEN 'learning' THEN 3 WHEN 'evaluating' THEN 4 WHEN 'retired' THEN 5 ELSE 6 END")
            ->orderBy('name')
            ->get();

        $summary = [
            'total' => $items->count(),
            'core' => $items->where('status', 'core')->count(),
            'learning' => $items->where('status', 'learning')->count(),
            'evaluating' => $items->where('status', 'evaluating')->count(),
        ];

        return Inertia::render('Stack/Index', [
            'items' => $items,
            'summary' => $summary,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $request->user()->stackItems()->create($data);

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Stack item added.',
        ]);
    }

    public function update(Request $request, StackItem $stackItem): RedirectResponse
    {
        $this->authorizeOwner($request, $stackItem);

        $stackItem->update($this->validated($request));

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Stack item updated.',
        ]);
    }

    public function pin(Request $request, StackItem $stackItem): RedirectResponse
    {
        $this->authorizeOwner($request, $stackItem);

        $stackItem->update([
            'is_pinned' => ! $stackItem->is_pinned,
        ]);

        return back()->with('toast', [
            'type' => 'success',
            'message' => $stackItem->is_pinned ? 'Pinned to top.' : 'Unpinned.',
        ]);
    }

    public function destroy(Request $request, StackItem $stackItem): RedirectResponse
    {
        $this->authorizeOwner($request, $stackItem);

        $stackItem->delete();

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Stack item removed.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'category' => ['required', 'string', Rule::in(StackItem::CATEGORIES)],
            'status' => ['required', 'string', Rule::in(StackItem::STATUSES)],
            'version' => ['nullable', 'string', 'max:40'],
            'url' => ['nullable', 'url', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_pinned' => ['sometimes', 'boolean'],
        ]);
    }

    private function authorizeOwner(Request $request, StackItem $stackItem): void
    {
        abort_unless($stackItem->user_id === $request->user()->id, 403);
    }
}
