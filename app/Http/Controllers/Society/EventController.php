<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Event;
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

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateEvent($request);

        $event = Event::create([
            ...$validated,
            'status' => 'published',
            'posted_by_user_id' => Auth::guard('society')->id(),
        ]);

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

    public function update(Request $request, int $id): RedirectResponse
    {
        $event = Event::findOrFail($id);
        $validated = $this->validateEvent($request);

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

    /**
     * @return array<string, mixed>
     */
    private function validateEvent(Request $request): array
    {
        // Blank optional inputs arrive as "" rather than absent; normalize
        // so nullable rules don't reject an intentionally empty field.
        foreach (['location', 'end_at'] as $field) {
            if ($request->input($field) === '') {
                $request->merge([$field => null]);
            }
        }

        return $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|in:' . implode(',', Event::CATEGORIES),
            'location' => 'nullable|string|max:255',
            'start_at' => 'required|date',
            'end_at' => 'nullable|date|after:start_at',
        ]);
    }
}
