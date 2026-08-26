<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Poll;
use App\Models\Tenant\PollOption;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PollController extends Controller
{
    public function index(): View
    {
        $polls = Poll::with('options')->latest()->paginate(15);

        return view('society.polls.index', compact('polls'));
    }

    public function create(): View
    {
        return view('society.polls.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'question' => 'required|string|max:255',
            'description' => 'nullable|string',
            'closes_at' => 'nullable|date|after:now',
            'options' => 'required|array|min:2',
            'options.*' => 'required|string|max:255',
        ]);

        $poll = Poll::create([
            'question' => $validated['question'],
            'description' => $validated['description'] ?? null,
            'closes_at' => $validated['closes_at'] ?? null,
            'status' => 'open',
            'created_by_user_id' => Auth::guard('society')->id(),
        ]);

        foreach (array_values($validated['options']) as $i => $label) {
            PollOption::create(['poll_id' => $poll->id, 'label' => $label, 'sort_order' => $i]);
        }

        return redirect()
            ->route('society.polls.show', $poll->id)
            ->with('success', 'Poll published successfully.');
    }

    public function show(int $id): View
    {
        $poll = Poll::with(['options', 'votes.voter'])->findOrFail($id);

        return view('society.polls.show', compact('poll'));
    }

    public function updateStatus(Request $request, int $id): RedirectResponse
    {
        $poll = Poll::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:' . implode(',', Poll::STATUSES),
        ]);

        $poll->update($validated);

        return redirect()
            ->route('society.polls.show', $poll->id)
            ->with('success', 'Poll status updated.');
    }

    public function destroy(int $id): RedirectResponse
    {
        Poll::findOrFail($id)->delete();

        return redirect()
            ->route('society.polls.index')
            ->with('success', 'Poll deleted.');
    }
}
