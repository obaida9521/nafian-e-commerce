<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubscriberController extends Controller
{
    /**
     * Sign an email up for the newsletter, or a phone number for campaign SMS.
     */
    public function store(Request $request, AnalyticsService $analytics): RedirectResponse|JsonResponse
    {
        $request->merge(['source' => $request->input('source', 'newsletter')]);

        $validated = $request->validate([
            'source' => ['required', Rule::in(['newsletter', 'campaign_sms'])],
            'contact' => ['required', 'string', 'max:150'],
        ], [], ['contact' => $request->input('source') === 'campaign_sms' ? 'মোবাইল নম্বর' : 'ইমেইল']);

        if ($validated['source'] === 'campaign_sms') {
            $contact = normalize_phone($validated['contact']);
            $channel = 'sms';
            $valid = (bool) preg_match('/^01[3-9][0-9]{8}$/', $contact);
            $error = 'সঠিক মোবাইল নম্বর দিন।';
            $message = 'ধন্যবাদ! নতুন অফার শুরু হলেই এসএমএস পাবেন।';
        } else {
            $contact = mb_strtolower(trim($validated['contact']));
            $channel = 'email';
            $valid = filter_var($contact, FILTER_VALIDATE_EMAIL) !== false;
            $error = 'সঠিক ইমেইল ঠিকানা দিন।';
            $message = 'ধন্যবাদ! পরের চিঠিটি আপনার ইনবক্সে যাবে।';
        }

        if (! $valid) {
            return $request->expectsJson()
                ? response()->json(['message' => $error, 'errors' => ['contact' => [$error]]], 422)
                : back()->withErrors(['contact' => $error], $validated['source'])->withInput();
        }

        $subscriber = Subscriber::firstOrCreate(
            ['contact' => $contact, 'source' => $validated['source']],
            ['channel' => $channel],
        );

        if ($subscriber->wasRecentlyCreated) {
            $analytics->lead($validated['source'], $contact);
        }

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'analytics' => $analytics->pull()])
            : back()->with('success', $message);
    }
}
