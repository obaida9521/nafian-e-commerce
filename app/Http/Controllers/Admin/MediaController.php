<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use App\Services\MediaLibraryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MediaController extends Controller
{
    private const PER_PAGE = 40;

    public function __construct(
        private readonly MediaLibraryService $library,
        private readonly ActivityLogger $activityLogger,
    ) {}

    /**
     * The media manager page, or its JSON feed for the picker modal.
     */
    public function index(Request $request): View|JsonResponse
    {
        $validated = $request->validate([
            'source' => ['nullable', Rule::in(array_keys(MediaLibraryService::SOURCES))],
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $items = $this->library->paginate(
            $validated['source'] ?? null,
            $validated['search'] ?? null,
            self::PER_PAGE,
            (int) ($validated['page'] ?? 1),
        );

        if ($request->wantsJson()) {
            return response()->json([
                'data' => $items->items(),
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'total' => $items->total(),
            ]);
        }

        return view('admin.media.index', [
            'items' => $items,
            'counts' => $this->library->counts(),
            'sources' => MediaLibraryService::SOURCES,
            'source' => $validated['source'] ?? null,
            'search' => $validated['search'] ?? null,
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'files' => ['required', 'array', 'max:20'],
            'files.*' => ['image', 'mimes:png,jpg,jpeg,webp,gif', 'max:5120'],
        ], [], ['files.*' => 'ছবি']);

        $items = $this->library->upload($request->file('files', []), auth('admin')->user());

        $this->activityLogger->log(auth('admin')->user(), 'media.uploaded', 'media', null, 'Uploaded '.$items->count().' image(s) to the media library');

        $message = bn_digits($items->count()).'টি ছবি মিডিয়াতে যোগ হয়েছে।';

        return $request->wantsJson()
            ? response()->json(['message' => $message, 'data' => $items->values()])
            : back()->with('success', $message);
    }

    public function destroy(Request $request, string $key): RedirectResponse|JsonResponse
    {
        $deleted = $this->library->delete($key);

        if ($deleted) {
            $this->activityLogger->log(auth('admin')->user(), 'media.deleted', 'media', null, "Deleted media {$key}");
        }

        $message = $deleted ? 'ছবি মুছে ফেলা হয়েছে।' : 'এই ছবিটি এখান থেকে মোছা যাবে না — যেখানে ব্যবহৃত সেখান থেকে সরান।';

        if ($request->wantsJson()) {
            return response()->json(['message' => $message], $deleted ? 200 : 422);
        }

        return back()->with($deleted ? 'success' : 'error', $message);
    }
}
