<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class NoteController extends Controller
{
    public function index(Request $request): Response
    {
        $notes = Note::where('user_id', $request->user()->id)
            ->orderByDesc('is_pinned')
            ->orderByDesc('updated_at')
            ->get();

        return Inertia::render('Notes/Index', [
            'notes' => $notes,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->user()->notes()->create($this->validated($request));

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Note saved to your private workspace.',
        ]);
    }

    public function update(Request $request, Note $note): RedirectResponse
    {
        $this->authorizeOwner($request, $note);

        $note->update($this->validated($request));

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Note updated.',
        ]);
    }

    public function pin(Request $request, Note $note): RedirectResponse
    {
        $this->authorizeOwner($request, $note);

        $note->update(['is_pinned' => ! $note->is_pinned]);

        return back()->with('toast', [
            'type' => 'success',
            'message' => $note->is_pinned ? 'Note pinned.' : 'Note unpinned.',
        ]);
    }

    public function destroy(Request $request, Note $note): RedirectResponse
    {
        $this->authorizeOwner($request, $note);

        $note->delete();

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Note deleted.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:10000'],
            'tags' => ['nullable', 'string', 'max:300'],
            'color' => ['required', 'in:slate,amber,emerald,sky,violet,rose'],
            'is_pinned' => ['boolean'],
        ]);

        $data['tags'] = Collection::make(explode(',', $data['tags'] ?? ''))
            ->map(fn (string $tag) => trim($tag))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $data;
    }

    private function authorizeOwner(Request $request, Note $note): void
    {
        abort_unless($note->user_id === $request->user()->id, 403);
    }
}
