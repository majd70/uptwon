@props(['name', 'class' => 'size-5'])

@php
    // Inline SVGs only — no icon font or sprite request on a page that has to
    // stay fast on a phone connection.
    $paths = [
        'phone' => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.2a2 2 0 0 1 2.1-.5c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2Z"/>',
        'whatsapp' => '<path d="M12 2a10 10 0 0 0-8.6 15L2 22l5.1-1.3A10 10 0 1 0 12 2Z"/><path d="M8.5 7.8c.2-.4.4-.4.6-.4h.6c.2 0 .5 0 .7.5l.9 2c.1.3 0 .5-.1.7l-.5.6c-.2.2-.3.4-.1.7a8 8 0 0 0 3.6 3.1c.3.2.5.1.7-.1l.6-.7c.2-.2.4-.2.6-.1l2 1c.3.1.4.3.4.5s0 1-.4 1.4c-.3.4-1 .8-1.7.8-1.4 0-3.6-.9-5.5-2.8a11 11 0 0 1-2.6-4.4c-.2-1 .1-1.9.4-2.3Z" fill="currentColor" stroke="none"/>',
        'instagram' => '<rect x="2.5" y="2.5" width="19" height="19" rx="5.5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1.2" fill="currentColor" stroke="none"/>',
        'facebook' => '<path d="M14.5 8.5V6.8c0-.8.2-1.3 1.4-1.3h1.6V2.6A20 20 0 0 0 15.2 2.5c-2.4 0-4 1.5-4 4.2v1.8H8.5v3h2.7V21h3.3v-9.5h2.6l.4-3Z" fill="currentColor" stroke="none"/>',
        'tiktok' => '<path d="M16.2 2.5c.4 2.3 1.7 3.7 4 3.9v2.9a6.9 6.9 0 0 1-3.9-1.2v5.6a5.8 5.8 0 1 1-5-5.7v3a2.8 2.8 0 1 0 2 2.7V2.5h2.9Z" fill="currentColor" stroke="none"/>',
        'map' => '<path d="M12 21s-7-5.4-7-10.4a7 7 0 1 1 14 0C19 15.6 12 21 12 21Z"/><circle cx="12" cy="10.5" r="2.6"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.2 2"/>',
        'menu' => '<path d="M4.5 3.5h11a2 2 0 0 1 2 2v15h-13a2 2 0 0 1-2-2v-13a2 2 0 0 1 2-2Z"/><path d="M17.5 8.5h2a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-2"/><path d="M6.5 8h7M6.5 12h7M6.5 16h4"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'chevron' => '<path d="m9 5 7 7-7 7"/>',
        'arrow' => '<path d="M4 12h15m0 0-6-6m6 6-6 6"/>',
        'close' => '<path d="M6 6l12 12M18 6 6 18"/>',
        'back' => '<path d="M15 5 8 12l7 7"/>',
        'star' => '<path d="m12 3.5 2.6 5.4 5.9.8-4.3 4.1 1 5.9-5.2-2.8-5.2 2.8 1-5.9L3.5 9.7l5.9-.8L12 3.5Z"/>',
        'image' => '<rect x="3" y="4.5" width="18" height="15" rx="2.5"/><circle cx="8.5" cy="10" r="1.6"/><path d="m3.5 17 4.8-4.3a2 2 0 0 1 2.7 0l3 2.8a2 2 0 0 0 2.7 0l1.8-1.6"/>',
    ];
@endphp

<svg {{ $attributes->merge(['class' => $class]) }}
     viewBox="0 0 24 24" fill="none" stroke="currentColor"
     stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"
     aria-hidden="true" focusable="false">
    {!! $paths[$name] ?? '' !!}
</svg>
