<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = AdminActivityLog::with('admin')
            ->when($request->string('resource_type')->toString(), fn ($q, $type) => $q->where('resource_type', $type))
            ->when($request->string('action')->toString(), fn ($q, $action) => $q->where('action', 'like', "%{$action}%"))
            ->latest()
            ->paginate(config('shop.per_page'))
            ->withQueryString();

        $resourceTypes = AdminActivityLog::query()->distinct()->pluck('resource_type');

        return view('admin.activity-logs.index', compact('logs', 'resourceTypes'));
    }
}
