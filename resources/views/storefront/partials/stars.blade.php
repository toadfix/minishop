@php($rounded = (int) round($rating ?? 0))
<span class="inline-flex align-middle text-amber-400" aria-label="{{ number_format($rating ?? 0, 1) }} out of 5">
    @for ($s = 1; $s <= 5; $s++)
        <svg class="h-4 w-4 {{ $s <= $rounded ? 'fill-current' : 'fill-gray-200' }}" viewBox="0 0 20 20" aria-hidden="true">
            <path d="M9.05 2.93c.3-.92 1.6-.92 1.9 0l1.36 4.18a1 1 0 00.95.69h4.4c.97 0 1.37 1.24.59 1.81l-3.56 2.59a1 1 0 00-.36 1.12l1.36 4.18c.3.92-.76 1.69-1.54 1.12l-3.56-2.59a1 1 0 00-1.18 0l-3.56 2.59c-.78.57-1.84-.2-1.54-1.12l1.36-4.18a1 1 0 00-.36-1.12L1.4 9.6c-.78-.57-.38-1.81.59-1.81h4.4a1 1 0 00.95-.69L9.05 2.93z"/>
        </svg>
    @endfor
</span>
