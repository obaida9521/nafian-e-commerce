@props(['items' => []])

<nav class="flex items-center gap-1.5 text-sm text-gray-500" aria-label="Breadcrumb">
    @foreach ($items as $label => $url)
        @if (! $loop->last && $url)
            <a href="{{ $url }}" class="hover:text-wine-700">{{ $label }}</a>
            <span class="text-gray-300">/</span>
        @else
            <span class="text-gray-900 font-medium">{{ is_int($label) ? $url : $label }}</span>
        @endif
    @endforeach
</nav>
