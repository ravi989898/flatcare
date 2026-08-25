<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Announcement;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    /**
     * List announcements, pinned first then newest. Admins see everything
     * (including drafts/archived) via the status filter; the default view
     * is what residents would see once a resident portal exists.
     */
    public function index(Request $request): View
    {
        $query = Announcement::query()->with('postedBy');

        $status = $request->string('status')->trim()->value();
        if ($status) {
            $query->where('status', $status);
        } else {
            $query->visible();
        }

        if ($category = $request->string('category')->trim()->value()) {
            $query->where('category', $category);
        }

        $announcements = $query->pinnedFirst()->paginate(10)->withQueryString();

        return view('society.announcements.index', compact('announcements', 'status'));
    }

    public function create(): View
    {
        return view('society.announcements.create');
    }

    public function store(Request $request, NotificationService $notifications): RedirectResponse
    {
        $validated = $this->validateAnnouncement($request);

        $announcement = Announcement::create([
            ...$validated,
            'status' => 'published',
            'published_at' => now(),
            'posted_by_user_id' => Auth::guard('society')->id(),
        ]);

        $notifications->notifyAllResidents(
            'new_notice',
            $announcement->title,
            \Illuminate\Support\Str::limit($announcement->body, 120),
            ['announcement_id' => $announcement->id],
        );

        return redirect()
            ->route('society.announcements.show', $announcement->id)
            ->with('success', 'Announcement published successfully.');
    }

    public function show(int $id): View
    {
        $announcement = Announcement::with('postedBy')->findOrFail($id);

        return view('society.announcements.show', compact('announcement'));
    }

    public function edit(int $id): View
    {
        $announcement = Announcement::findOrFail($id);

        return view('society.announcements.edit', compact('announcement'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $announcement = Announcement::findOrFail($id);
        $validated = $this->validateAnnouncement($request);

        $announcement->update($validated);

        return redirect()
            ->route('society.announcements.show', $announcement->id)
            ->with('success', 'Announcement updated successfully.');
    }

    /**
     * Archive (soft-remove from the visible board) rather than hard delete.
     */
    public function destroy(int $id): RedirectResponse
    {
        $announcement = Announcement::findOrFail($id);
        $announcement->update(['status' => 'archived']);

        return redirect()
            ->route('society.announcements.index')
            ->with('success', 'Announcement archived.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateAnnouncement(Request $request): array
    {
        // A blank date input arrives as "" rather than absent; normalize so
        // the nullable|date rule doesn't reject an intentionally empty field.
        if ($request->input('expires_at') === '') {
            $request->merge(['expires_at' => null]);
        }

        return $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'category' => 'required|in:' . implode(',', Announcement::CATEGORIES),
            'is_pinned' => 'boolean',
            'expires_at' => 'nullable|date|after:now',
        ]);
    }
}
