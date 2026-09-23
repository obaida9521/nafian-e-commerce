@props([
    'title' => 'Are you sure?',
    'message' => 'This action cannot be undone.',
    'action' => '#',
    'method' => 'DELETE',
    'trigger' => 'Delete',
    'confirm' => 'Confirm',
    'icon' => 'trash',
])

<div x-data="{ open: false }" class="inline">
    <button type="button" @click="open = true" {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5']) }}>
        @if($icon)<x-ui.icon :name="$icon" :size="15" />@endif{{ $trigger }}
    </button>

    <template x-teleport="body">
        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40" @click="open = false"></div>
            <div class="relative w-full max-w-sm rounded-xl bg-white p-6 shadow-xl"
                x-show="open" x-transition.scale.origin.center>
                <h3 class="text-lg font-semibold text-gray-900">{{ $title }}</h3>
                <p class="mt-2 text-sm text-gray-500">{{ $message }}</p>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="open = false"
                        class="inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100"><x-ui.icon name="x" :size="15" />Cancel</button>
                    <form method="POST" action="{{ $action }}">
                        @csrf
                        @method($method)
                        <button type="submit"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700"><x-ui.icon :name="$icon ?: 'check'" :size="15" />{{ $confirm }}</button>
                    </form>
                </div>
            </div>
        </div>
    </template>
</div>
