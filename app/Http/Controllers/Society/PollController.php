<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Http\Requests\Society\StorePollRequest;
use App\Http\Requests\Society\UpdatePollStatusRequest;
use App\Models\Tenant\Poll;
use App\Models\Tenant\PollOption;
use App\Support\ListQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PollController extends Controller
{
    public function index(Request $request): View
    {
        $query = Poll::with('options');

        ListQuery::applySearch($query, $request, ['question']);

        if ($status = $request->string('status')->trim()->value()) {
            $query->where('status', $status);
        }

        ListQuery::applySort($query, $request, [
            'created' => 'created_at',
            'question' => 'question',
            'status' => 'status',
        ], 'created');

        $polls = $query->paginate(ListQuery::perPage($request, 25))->withQueryString();

        return view('society.polls.index', compact('polls'));
    }

    public function create(): View
    {
        return view('society.polls.create');
    }

    public function store(StorePollRequest $request): RedirectResponse
    {
        $validated = $request->validated();

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

    public function updateStatus(UpdatePollStatusRequest $request, int $id): RedirectResponse
    {
        $poll = Poll::findOrFail($id);

        $validated = $request->validated();

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
