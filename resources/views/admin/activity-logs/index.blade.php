@extends('layouts.admin')

@section('title', 'অ্যাক্টিভিটি লগ')

@section('content')
<div class="space-y-5">
    <x-ui.breadcrumb :items="['Dashboard' => route('admin.dashboard'), 'Activity Logs' => null]" />

    <form method="GET" class="flex flex-wrap gap-3">
        <input name="action" value="{{ request('action') }}" placeholder="Filter by action…"
            class="flex-1 min-w-48 rounded-lg border border-gray-300 px-3.5 py-2 text-sm focus:border-wine-500 focus:ring-2 focus:ring-wine-500/30 outline-none">
        <select name="resource_type" class="rounded-lg border border-gray-300 px-3 py-2 text-sm bg-white">
            <option value="">All resources</option>
            @foreach ($resourceTypes as $type)
                <option value="{{ $type }}" @selected(request('resource_type')===$type)>{{ $type }}</option>
            @endforeach
        </select>
        <button class="inline-flex items-center gap-1.5 rounded-lg bg-wine-700 px-4 py-2 text-sm font-medium text-cream-100 hover:bg-wine-800"><x-ui.icon name="filter" :size="15" />Filter</button>
    </form>

    <div class="rounded-xl bg-white border border-cream-300/60 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-cream-50 text-gray-500">
                <tr>
                    <th class="text-left font-medium px-5 py-3">When</th>
                    <th class="text-left font-medium px-5 py-3">Admin</th>
                    <th class="text-left font-medium px-5 py-3">Action</th>
                    <th class="text-left font-medium px-5 py-3">Description</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($logs as $log)
                    <tr class="hover:bg-cream-50/50">
                        <td class="px-5 py-3 text-gray-500 whitespace-nowrap">{{ $log->created_at->format('M j, H:i') }}</td>
                        <td class="px-5 py-3 text-gray-700">{{ $log->admin?->name ?? 'System' }}</td>
                        <td class="px-5 py-3"><span class="font-mono text-xs bg-cream-100 text-wine-700 px-2 py-0.5 rounded">{{ $log->action }}</span></td>
                        <td class="px-5 py-3 text-gray-700">{{ $log->description }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-10 text-center text-gray-400">No activity logged yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $logs->links() }}
</div>
@endsection
