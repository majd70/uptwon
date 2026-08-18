@php
    $s = settings();

    $socials = collect([
        ['key' => 'instagram', 'url' => $s->instagram_url, 'label' => __('menu.instagram')],
        ['key' => 'facebook',  'url' => $s->facebook_url,  'label' => __('menu.facebook')],
        ['key' => 'tiktok',    'url' => $s->tiktok_url,    'label' => __('menu.tiktok')],
    ])->filter(fn ($x) => filled($x['url']))->values();

    $hours = $s->hours();
@endphp

<x-layouts.public>
    <div class="relative min-h-dvh overflow-x-clip">

        {{-- The backdrop lives at page level, not inside the content column, so it
             spans the whole viewport and never shows a vertical edge. It fades to
             solid ground at its foot, so there is no horizontal seam either. --}}
        <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 top-0 h-[30rem] overflow-hidden">
            @if ($heroImage)
                <img src="{{ $heroImage }}" alt="" fetchpriority="high"
                     class="size-full scale-110 object-cover"
                     style="filter: brightness(0.34) saturate(0.9) blur(3px); opacity: 0.85;">
            @endif
            <div class="absolute inset-0
                 bg-[linear-gradient(to_bottom,color-mix(in_srgb,var(--ground)_25%,transparent)_0%,color-mix(in_srgb,var(--ground)_58%,transparent)_52%,var(--ground)_100%)]"></div>
            <div class="absolute inset-0
                 bg-[radial-gradient(85%_70%_at_50%_24%,transparent,color-mix(in_srgb,var(--ground)_80%,transparent)_75%,var(--ground)_100%)]"></div>
            <div class="u-pattern absolute inset-0"></div>
        </div>

        {{-- Two hairlines run the full length of the page, so the hero belongs to a
             continuous card instead of reading as a panel on a background. --}}
        <div class="u-bound relative mx-auto min-h-dvh w-full max-w-[440px]">

        <header class="absolute inset-x-0 top-0 z-30 px-5 pt-[max(1rem,env(safe-area-inset-top))]">
            <div class="flex justify-end">
                @include('partials.lang-switch')
            </div>
        </header>

        <main id="main">

            {{-- ── Hero ─────────────────────────────────────────────────── --}}
            <section class="relative px-6 pb-9 pt-28 text-center">

                <div class="relative z-10">
                {{-- Monogram in a gilded double ring --}}
                <div class="u-ring mx-auto size-[6.5rem]">
                    @if ($s->logoUrl())
                        <img src="{{ $s->logoUrl() }}" alt="{{ $s->name }}" width="104" height="104"
                             class="size-full rounded-full object-cover">
                    @else
                        <span class="u-display u-foil text-[1.625rem] tracking-[0.18em]">{{ $s->monogramText() }}</span>
                    @endif
                </div>

                <h1 class="u-display u-foil mt-8 text-balance text-[clamp(1.875rem,8vw,2.375rem)] leading-[1.2]">
                    {{ $s->name }}
                </h1>

                @if (filled($s->tagline))
                    <p class="u-eyebrow mt-3.5">{{ $s->tagline }}</p>
                @endif

                <div class="u-rule mt-6" aria-hidden="true"><span class="u-diamond"></span></div>
                </div>
            </section>

            {{-- ── The one thing this page exists for ───────────────────── --}}
            <div class="px-6">
                <a href="{{ route('menu') }}" class="u-cta u-tap u-focus">
                    <x-ui-icon name="menu" class="size-5 shrink-0"/>
                    <span class="u-display flex-1 text-start text-[1.0625rem] font-semibold tracking-wide">
                        {{ __('menu.view_menu') }}
                    </span>
                    <x-ui-icon name="arrow" class="size-5 shrink-0 rtl:rotate-180"/>
                </a>
            </div>

            {{-- ── Contact ──────────────────────────────────────────────── --}}
            <section class="mt-11 px-6">
                <h2 class="u-eyebrow">{{ __('menu.get_in_touch') }}</h2>

                <div class="u-list mt-4">
                    @if ($s->whatsappUrl())
                        <a href="{{ $s->whatsappUrl() }}" target="_blank" rel="noopener noreferrer"
                           class="u-line u-tap u-focus">
                            <span class="u-line-icon"><x-ui-icon name="whatsapp" class="size-[19px]"/></span>
                            <span class="min-w-0 flex-1">
                                <span class="u-line-title">{{ __('menu.whatsapp') }}</span>
                                <span class="u-line-sub">{{ __('menu.whatsapp_sub') }}</span>
                            </span>
                            <x-ui-icon name="arrow" class="size-4 shrink-0 text-[color:var(--text-faint)] rtl:rotate-180"/>
                        </a>
                    @endif

                    @if (filled($s->phone))
                        <a href="tel:{{ $s->phone }}" class="u-line u-tap u-focus">
                            <span class="u-line-icon"><x-ui-icon name="phone" class="size-[19px]"/></span>
                            <span class="min-w-0 flex-1">
                                <span class="u-line-title">{{ __('menu.call_us') }}</span>
                                {{-- Phone numbers stay LTR or bidi reverses the digit groups --}}
                                <span class="u-line-sub" dir="ltr" style="text-align: start">{{ $s->phone }}</span>
                            </span>
                            <x-ui-icon name="arrow" class="size-4 shrink-0 text-[color:var(--text-faint)] rtl:rotate-180"/>
                        </a>
                    @endif

                    @if (filled($s->google_maps_url))
                        <a href="{{ $s->google_maps_url }}" target="_blank" rel="noopener noreferrer"
                           class="u-line u-tap u-focus">
                            <span class="u-line-icon"><x-ui-icon name="map" class="size-[19px]"/></span>
                            <span class="min-w-0 flex-1">
                                <span class="u-line-title">{{ __('menu.location') }}</span>
                                <span class="u-line-sub truncate">{{ $s->address ?: __('menu.location_sub') }}</span>
                            </span>
                            <x-ui-icon name="arrow" class="size-4 shrink-0 text-[color:var(--text-faint)] rtl:rotate-180"/>
                        </a>
                    @endif

                    {{-- Social sits directly above the opening hours --}}
                    @foreach ($socials as $social)
                        <a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer"
                           class="u-line u-tap u-focus">
                            <span class="u-line-icon"><x-ui-icon :name="$social['key']" class="size-[19px]"/></span>
                            <span class="min-w-0 flex-1">
                                <span class="u-line-title">{{ $social['label'] }}</span>
                                <span class="u-line-sub">{{ __('menu.follow_us') }}</span>
                            </span>
                            <x-ui-icon name="arrow" class="size-4 shrink-0 text-[color:var(--text-faint)] rtl:rotate-180"/>
                        </a>
                    @endforeach

                    @if (! empty($hours))
                        <div x-data="{ open: false }">
                            <button type="button" @click="open = !open" :aria-expanded="open"
                                    aria-controls="working-hours" class="u-line u-tap u-focus">
                                <span class="u-line-icon"><x-ui-icon name="clock" class="size-[19px]"/></span>
                                <span class="min-w-0 flex-1">
                                    <span class="u-line-title">{{ __('menu.working_hours') }}</span>
                                    <span class="u-line-sub">{{ __('menu.working_hours_sub') }}</span>
                                </span>
                                <x-ui-icon name="chevron"
                                           class="size-4 shrink-0 text-[color:var(--text-faint)] transition-transform duration-200 rotate-90"
                                           ::class="open ? '-rotate-90' : 'rotate-90'"/>
                            </button>

                            <div id="working-hours" x-show="open" x-collapse x-cloak>
                                <dl class="space-y-2.5 pb-5 pt-1 ps-[3.125rem] text-[0.8125rem]">
                                    @foreach ($hours as $row)
                                        <div class="flex items-baseline gap-3">
                                            <dt class="text-[color:var(--text-dim)]">
                                                {{ app()->getLocale() === 'ar' ? ($row['label_ar'] ?? $row['label_en'] ?? '') : ($row['label_en'] ?? $row['label_ar'] ?? '') }}
                                            </dt>
                                            <span class="u-leader" aria-hidden="true"></span>
                                            <dd class="font-semibold tabular-nums text-accent" dir="ltr">{{ $row['hours'] }}</dd>
                                        </div>
                                    @endforeach
                                </dl>
                            </div>
                        </div>
                    @endif
                </div>
            </section>

            <footer class="px-6 pb-[max(2.5rem,env(safe-area-inset-bottom))] pt-14 text-center">
                <div class="u-rule" aria-hidden="true"><span class="u-diamond"></span></div>
                <p class="mt-5 text-[0.6875rem] tracking-[0.14em] text-[color:var(--text-faint)]">
                    © {{ date('Y') }} · {{ $s->name }}
                </p>
            </footer>
        </main>
        </div>
    </div>
</x-layouts.public>
