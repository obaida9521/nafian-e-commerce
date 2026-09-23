<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Category;
use App\Models\MediaAsset;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * One view over every image in the store: library uploads, product and variant photos
 * (Spatie media), category images and the brand logo.
 *
 * Items are addressed by a key — `media:{id}`, `category:{id}` or `logo` — which the pickers
 * submit. Choosing an item *copies* its file into the target, so removing it from the
 * library (or from another product) never breaks where it was used.
 */
class MediaLibraryService
{
    /** Pattern a submitted library key must match. */
    public const KEY_PATTERN = '/^(media:\d+|category:\d+|logo)$/';

    /**
     * @var array<string, string>
     */
    public const SOURCES = [
        'library' => 'লাইব্রেরি',
        'product' => 'পণ্য',
        'variant' => 'ভ্যারিয়েন্ট',
        'category' => 'ক্যাটাগরি',
        'logo' => 'লোগো',
    ];

    public function __construct(private readonly SettingsService $settings) {}

    /**
     * Newest first, optionally narrowed by source and a filename/owner search.
     *
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginate(?string $source, ?string $search, int $perPage, int $page): LengthAwarePaginator
    {
        $items = $this->items()
            ->when($source && isset(self::SOURCES[$source]), fn (Collection $c) => $c->where('source', $source))
            ->when(filled($search), function (Collection $c) use ($search) {
                $needle = mb_strtolower(trim((string) $search));

                return $c->filter(fn (array $item) => str_contains(mb_strtolower($item['name'].' '.$item['owner']), $needle));
            })
            ->values();

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()],
        );
    }

    /**
     * Count per source for the filter chips.
     *
     * @return array<string, int>
     */
    public function counts(): array
    {
        $items = $this->items();

        return ['all' => $items->count()] + collect(self::SOURCES)->map(fn ($label, $key) => $items->where('source', $key)->count())->all();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function items(): Collection
    {
        $media = Media::query()
            ->whereIn('model_type', [MediaAsset::class, Product::class, ProductVariant::class])
            ->with(['model' => fn ($morph) => $morph->morphWith([ProductVariant::class => ['product']])])
            ->get()
            ->map(fn (Media $m) => $this->fromMedia($m))
            ->filter();

        $categories = Category::query()
            ->whereNotNull('image_path')
            ->get()
            ->map(fn (Category $category) => $this->fromPath(
                "category:{$category->id}",
                $category->image_path,
                'category',
                $category->name,
                route('admin.categories.edit', $category),
                $category->updated_at?->toIso8601String(),
            ))
            ->filter();

        $logoPath = $this->settings->group('general')['logo'] ?? null;
        $logo = $logoPath ? $this->fromPath('logo', $logoPath, 'logo', 'ব্র্যান্ড লোগো', route('admin.settings.index'), null) : null;

        return $media->concat($categories)
            ->when($logo, fn (Collection $c) => $c->push($logo))
            ->sortByDesc('created_at')
            ->values();
    }

    /**
     * Store uploaded images in the library.
     *
     * @param  array<int, UploadedFile>  $files
     * @return Collection<int, array<string, mixed>>
     */
    public function upload(array $files, ?Admin $admin): Collection
    {
        return collect($files)->map(function (UploadedFile $file) use ($admin) {
            $asset = MediaAsset::create([
                'title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'admin_id' => $admin?->id,
            ]);
            $media = $asset->addMedia($file)->toMediaCollection('file');

            return $this->fromMedia($media->setRelation('model', $asset));
        });
    }

    /**
     * Remove a library upload (images attached to products etc. are managed from their own forms).
     */
    public function delete(string $key): bool
    {
        $media = $this->mediaFor($key);

        if (! $media || $media->model_type !== MediaAsset::class) {
            return false;
        }

        $asset = $media->model;
        $media->delete();
        $asset?->delete();

        return true;
    }

    /**
     * Copy a library item into a model's media collection.
     */
    public function copyToCollection(string $key, HasMedia $model, string $collection): bool
    {
        $path = $this->absolutePath($key);

        if (! $path) {
            return false;
        }

        $model->addMedia($path)
            ->preservingOriginal()
            ->usingFileName($this->freshName($path))
            ->toMediaCollection($collection);

        return true;
    }

    /**
     * Copy a library item into a public-disk folder, returning the new relative path.
     */
    public function copyToDirectory(string $key, string $directory): ?string
    {
        $path = $this->absolutePath($key);

        if (! $path) {
            return null;
        }

        $target = trim($directory, '/').'/'.Str::random(40).'.'.strtolower(pathinfo($path, PATHINFO_EXTENSION) ?: 'jpg');
        Storage::disk('public')->put($target, file_get_contents($path));

        return $target;
    }

    public function absolutePath(string $key): ?string
    {
        if (! preg_match(self::KEY_PATTERN, $key)) {
            return null;
        }

        $path = match (true) {
            str_starts_with($key, 'media:') => $this->mediaFor($key)?->getPath(),
            str_starts_with($key, 'category:') => ($relative = Category::find((int) Str::after($key, ':'))?->image_path)
                ? Storage::disk('public')->path($relative) : null,
            default => ($relative = $this->settings->group('general')['logo'] ?? null)
                ? Storage::disk('public')->path($relative) : null,
        };

        return $path && is_file($path) ? $path : null;
    }

    private function mediaFor(string $key): ?Media
    {
        return str_starts_with($key, 'media:') ? Media::with('model')->find((int) Str::after($key, ':')) : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fromMedia(Media $media): ?array
    {
        $model = $media->model;

        [$source, $owner, $ownerUrl] = match (true) {
            $model instanceof MediaAsset => ['library', $model->title ?: $media->name, null],
            $model instanceof Product => ['product', $model->name, route('admin.products.edit', $model)],
            $model instanceof ProductVariant => [
                'variant',
                trim(($model->product?->name ?? '').' · '.$model->display_name, ' ·'),
                $model->product ? route('admin.products.edit', $model->product) : null,
            ],
            default => [null, null, null],
        };

        if ($source === null) {
            return null;
        }

        return [
            'key' => "media:{$media->id}",
            'url' => $media->getUrl(),
            'thumb' => $media->hasGeneratedConversion('thumb') ? $media->getUrl('thumb') : $media->getUrl(),
            'name' => $media->file_name,
            'size' => (int) $media->size,
            'mime' => $media->mime_type,
            'source' => $source,
            'source_label' => self::SOURCES[$source],
            'owner' => $owner,
            'owner_url' => $ownerUrl,
            'deletable' => $source === 'library',
            'created_at' => $media->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fromPath(string $key, string $relative, string $source, string $owner, ?string $ownerUrl, ?string $createdAt): ?array
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($relative)) {
            return null;
        }

        $url = $disk->url($relative);

        return [
            'key' => $key,
            'url' => $url,
            'thumb' => $url,
            'name' => basename($relative),
            'size' => (int) $disk->size($relative),
            'mime' => $disk->mimeType($relative) ?: null,
            'source' => $source,
            'source_label' => self::SOURCES[$source],
            'owner' => $owner,
            'owner_url' => $ownerUrl,
            'deletable' => false,
            'created_at' => $createdAt ?? date(DATE_ATOM, $disk->lastModified($relative)),
        ];
    }

    private function freshName(string $path): string
    {
        $base = Str::slug(pathinfo($path, PATHINFO_FILENAME)) ?: 'image';

        return $base.'-'.Str::lower(Str::random(6)).'.'.strtolower(pathinfo($path, PATHINFO_EXTENSION) ?: 'jpg');
    }
}
