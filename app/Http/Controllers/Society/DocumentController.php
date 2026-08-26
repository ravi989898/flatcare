<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Document;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Society document library (Documents tab in the mobile app). There's no
 * admin-side document feature to build on top of — this is the whole
 * thing: upload, categorize, list, delete. Files go on the shared
 * "public" disk, same as users.profile_photo_path.
 */
class DocumentController extends Controller
{
    public function index(Request $request): View
    {
        $query = Document::query()->with('uploadedBy');

        $category = $request->string('category')->trim()->value();
        if ($category) {
            $query->where('category', $category);
        }

        $documents = $query->latest()->paginate(20)->withQueryString();

        return view('society.documents.index', ['documents' => $documents, 'category' => $category]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|in:' . implode(',', Document::CATEGORIES),
            'file' => 'required|file|max:10240', // 10 MB
        ]);

        $file = $request->file('file');
        $path = $file->store('documents', 'public');

        Document::create([
            'title' => $validated['title'],
            'category' => $validated['category'],
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getClientMimeType(),
            'uploaded_by_user_id' => Auth::guard('society')->id(),
        ]);

        return redirect()
            ->route('society.documents.index')
            ->with('success', 'Document uploaded successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $document = Document::findOrFail($id);
        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return redirect()
            ->route('society.documents.index')
            ->with('success', 'Document deleted.');
    }
}
