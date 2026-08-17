@props([
    'pageTitle' => null,
    'metaDescription' => null,
])

@php
    $s = settings();
    $locale = app()->getLocale();
    $dir = $locale === 'ar' ? 'rtl' : 'ltr';
    $title = filled($pageTitle) ? $pageTitle.' · '.$s->name : $s->name;
    $description = $metaDescription ?: __('menu.meta_description', ['name' => $s->name]);
    $ogImage = $s->coverUrl() ?? $s->logoUrl();
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}"
      style="--brand: {{ $s->primary_color }}; --brand-soft: {{ $s->secondary_color }}; --accent: {{ $s->accent_color }};">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="{{ $s->primary_color }}">

    {{-- Only the display face for the language actually being read. --}}
    @if ($locale === 'ar')
        <link rel="preload" as="font" type="font/woff2" href="{{ asset('fonts/reemkufi-600-ar.woff2') }}" crossorigin>
    @else
        <link rel="preload" as="font" type="font/woff2" href="{{ asset('fonts/marcellus-400-latin.woff2') }}" crossorigin>
    @endif

    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ url()->current() }}">

    <meta property="og:type" content="restaurant.restaurant">
    <meta property="og:site_name" content="{{ $s->name }}">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:locale" content="{{ $locale === 'ar' ? 'ar_EG' : 'en_US' }}">
    @if ($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
    @endif
    <meta name="twitter:card" content="{{ $ogImage ? 'summary_large_image' : 'summary' }}">

    @if ($s->logoUrl())
        <link rel="icon" href="{{ $s->logoUrl() }}">
        <link rel="apple-touch-icon" href="{{ $s->logoUrl() }}">
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">
    <a href="#main"
       class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:start-2 focus:z-50 focus:rounded-lg focus:bg-brand focus:px-4 focus:py-2 focus:text-white">
        {{ __('menu.menu') }}
    </a>

    {{ $slot }}
</body>
</html>
