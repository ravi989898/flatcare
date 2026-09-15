<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Http\Requests\Society\EventRequest;
use App\Models\Tenant\Event;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EventController extends Controller
{
    /**
     * Upcoming events by default, with tabs for past and cancelled.
     */
    public function index(Request $request): View
    {
        $tab = $request->string('tab')->trim()->value() ?: 'upcoming';

        $query = Event::query()->with('postedBy');

        match ($tab) {
            'past' => $query->past()->orderByDesc('start_at'),
            'cancelled' => $query->cancelled()->orderByDesc('start_at'),
            default => $query->upcoming()->orderBy('start_at'),
        };

        $events = $query->paginate(10)->withQueryString();

        return view('society.events.index', compact('events', 'tab'));
    }

    public function create(): View
    {
        return view('society.events.create');
    }

    public function store(EventRequest $request, NotificationService $notifications): RedirectResponse
    {
        $validated = $request->validated();

        $event = Event::create([
            ...$validated,
            'status' => 'published',
            'posted_by_user_id' => Auth::guard('society')->id(),
        ]);

        $notifications->notifyAllResidents(
            'event_reminder',
            $event->title,
            $event->location ? "At {$event->location}" : null,
            ['event_id' => $event->id],
        );

        return redirect()
            ->route('society.events.show', $event->id)
            ->with('success', 'Event published successfully.');
    }

    public function show(int $id): View
    {
        $event = Event::with('postedBy')->findOrFail($id);

        return view('society.events.show', compact('event'));
    }

    public function edit(int $id): View
    {
        $event = Event::findOrFail($id);

        return view('society.events.edit', compact('event'));
    }

    public function update(EventRequest $request, int $id): RedirectResponse
    {
        $event = Event::findOrFail($id);
        $validated = $request->validated();

        $event->update($validated);

        return redirect()
            ->route('society.events.show', $event->id)
            ->with('success', 'Event updated successfully.');
    }

    /**
     * Cancel rather than delete, so it still shows (with an explanation)
     * instead of silently vanishing for anyone who saw the original post.
     */
    public function destroy(int $id): RedirectResponse
    {
        $event = Event::findOrFail($id);
        $event->update(['status' => 'cancelled']);

        return redirect()
            ->route('society.events.index')
            ->with('success', 'Event cancelled.');
    }
}
