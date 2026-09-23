<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\ActivityLogger;
use App\Services\MediaLibraryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    public function index(): View
    {
        $categories = Category::with('parent')
            ->withCount('products')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.categories.index', [
            'categories' => $categories,
            'homeCount' => $categories->where('show_on_home', true)->count(),
        ]);
    }

    public function toggleHome(Category $category): RedirectResponse
    {
        $category->update(['show_on_home' => ! $category->show_on_home]);

        $this->activityLogger->log(
            auth('admin')->user(),
            'category.home_toggled',
            'category',
            $category->id,
            ($category->show_on_home ? 'Enabled' : 'Disabled')." {$category->name} on homepage",
        );

        return back()->with('success', $category->show_on_home ? 'হোমপেজে দেখানো হচ্ছে।' : 'হোমপেজ থেকে লুকানো হয়েছে।');
    }

    public function create(): View
    {
        return view('admin.categories.create', [
            'category' => new Category,
            'parents' => Category::parents()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $category = Category::create($data);

        $this->activityLogger->log(
            auth('admin')->user(),
            'category.created',
            'category',
            $category->id,
            "Created category {$category->name}",
        );

        return redirect()->route('admin.categories.index')->with('success', 'ক্যাটাগরি তৈরি হয়েছে।');
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.edit', [
            'category' => $category,
            'parents' => Category::parents()->where('id', '!=', $category->id)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $this->validateData($request, $category);
        $category->update($data);

        $this->activityLogger->log(
            auth('admin')->user(),
            'category.updated',
            'category',
            $category->id,
            "Updated category {$category->name}",
        );

        return redirect()->route('admin.categories.index')->with('success', 'ক্যাটাগরি আপডেট হয়েছে।');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        $this->activityLogger->log(
            auth('admin')->user(),
            'category.deleted',
            'category',
            $category->id,
            "Deleted category {$category->name}",
        );

        return redirect()->route('admin.categories.index')->with('success', 'ক্যাটাগরি মুছে ফেলা হয়েছে।');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateData(Request $request, ?Category $category = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'tone' => ['nullable', 'string', 'max:9'],
            'tone2' => ['nullable', 'string', 'max:9'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'show_on_home' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:1024'],
            'remove_image' => ['nullable', 'boolean'],
            'library_image' => ['nullable', 'string', 'regex:'.MediaLibraryService::KEY_PATTERN],
        ]);

        unset($validated['image'], $validated['remove_image'], $validated['library_image']);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active');
        $validated['show_on_home'] = $request->boolean('show_on_home');

        if ($request->hasFile('image')) {
            if ($category?->image_path) {
                Storage::disk('public')->delete($category->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('categories', 'public');
        } elseif ($request->filled('library_image') && ($copied = app(MediaLibraryService::class)->copyToDirectory($request->string('library_image')->toString(), 'categories'))) {
            if ($category?->image_path) {
                Storage::disk('public')->delete($category->image_path);
            }
            $validated['image_path'] = $copied;
        } elseif ($request->boolean('remove_image')) {
            if ($category?->image_path) {
                Storage::disk('public')->delete($category->image_path);
            }
            $validated['image_path'] = null;
        }

        return $validated;
    }
}
