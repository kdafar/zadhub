@php
    $history = $getRecord()?->flow_history ?? [];
    $history = array_reverse($history); // latest first
@endphp

<div class="space-y-2">
    @forelse ($history as $row)
        <div class="rounded-xl border p-3">
            <div class="text-sm">
                <span class="font-semibold">{{ $row['event'] ?? 'event' }}</span>
                <span class="opacity-70">• {{ $row['at'] ?? '' }}</span>
            </div>
            <div class="text-xs mt-1">
                <div><span class="opacity-70">Screen:</span> {{ $row['screen'] ?? '—' }}</div>
                @if(!empty($row['meta']))
                    <details class="mt-1">
                        <summary class="cursor-pointer text-blue-700">Meta</summary>
                        <pre class="text-[11px] leading-4 mt-1">{{ json_encode($row['meta'], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
                    </details>
                @endif
            </div>
        </div>
    @empty
        <div class="text-sm opacity-60">No history yet.</div>
    @endforelse
</div>
