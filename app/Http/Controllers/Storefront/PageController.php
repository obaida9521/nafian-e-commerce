<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use Illuminate\View\View;

class PageController extends Controller
{
    /**
     * Static help / company pages linked from the footer.
     *
     * @var array<string, string>
     */
    public const PAGES = [
        'delivery' => 'ডেলিভারি',
        'returns' => 'রিটার্ন',
        'faq' => 'প্রশ্নোত্তর',
        'contact' => 'যোগাযোগ',
        'about' => 'আমাদের সম্পর্কে',
        'privacy' => 'প্রাইভেসি পলিসি',
        'terms' => 'শর্তাবলি',
        'authenticity' => 'অথেনটিসিটি',
    ];

    public function show(string $slug, SettingsService $settings): View
    {
        abort_unless(array_key_exists($slug, self::PAGES), 404);

        return view('storefront.page', [
            'slug' => $slug,
            'title' => self::PAGES[$slug],
            'general' => $settings->group('general'),
        ]);
    }
}
