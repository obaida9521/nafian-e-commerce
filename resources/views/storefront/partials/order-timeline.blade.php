@php
    /** @var \App\Models\Order $order */
    $steps = $order->trackingSteps();
    $dense = $dense ?? false;
@endphp
<div class="flex flex-col">
    @foreach($steps as $step)
        @php $isLast = $loop->last; @endphp
        <div class="grid {{ $dense ? 'grid-cols-[22px_1fr] gap-3' : 'grid-cols-[28px_1fr] gap-4' }}">
            <div class="flex flex-col items-center">
                <span @class([
                    'rounded-full flex-none',
                    $dense ? 'w-3 h-3' : 'w-3.5 h-3.5',
                    'bg-mocha' => $step['state'] === 'done',
                    'bg-accent ring-[5px] ring-accent-soft' => $step['state'] === 'current',
                    'bg-line' => $step['state'] === 'upcoming',
                ])></span>
                @unless($isLast)
                    <span @class(['flex-1 w-0.5', 'bg-mocha' => $step['state'] === 'done', 'bg-line' => $step['state'] !== 'done'])></span>
                @endunless
            </div>
            <div class="{{ $isLast ? '' : ($dense ? 'pb-[18px]' : 'pb-[26px]') }}">
                <div @class([
                    'font-semibold',
                    $dense ? 'text-[14.5px]' : 'text-[15.5px]',
                    'text-accent' => $step['state'] === 'current',
                    'text-muted' => $step['state'] === 'upcoming',
                ])>{{ $step['label'] }}</div>
                <div class="mt-0.5 {{ $dense ? 'text-[12.5px]' : 'text-[14px]' }} text-muted">
                    @if($step['at']){{ bn_date($step['at'], 'j M') }}, {{ bn_time($step['at']) }}@endif
                    @if($step['at'] && $step['note']) · @endif
                    @if($step['note']){{ $step['note'] }}@endif
                </div>
            </div>
        </div>
    @endforeach
</div>
