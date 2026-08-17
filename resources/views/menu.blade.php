@php
    $s = settings();
@endphp

<x-layouts.public :page-title="__('menu.menu')">
    <div x-data="menuPage" class="u-bound mx-auto min-h-dvh max-w-[540px] pb-24">

        {{-- ── Sticky header ───────────────────────────────────────────── --}}
        <header class="sticky top-0 z-30 border-b border-[color:var(--rule-soft)]
                       bg-[color:color-mix(in_srgb,var(--ground)_92%,transparent)] backdrop-blur-md">
            <div class="px-5 pt-[max(0.75rem,env(safe-area-inset-top))]">

                <div class="flex items-center gap-3">
                    <a href="{{ route('landing') }}" aria-label="{{ __('menu.back') }}"
                       class="u-tap u-focus -ms-2 grid size-10 place-items-center rounded-full text-accent
                              transition hover:bg-[color:var(--rule)]">
                        <x-ui-icon name="back" class="size-5 rtl:rotate-180"/>
                    </a>

                    <h1 class="u-display min-w-0 flex-1 truncate text-center text-[1.0625rem] tracking-wide">
                        {{ $s->name }}
                    </h1>

                    @include('partials.lang-switch')
                </div>

                {{-- Search --}}
                <div class="relative mt-3">
                    <span class="pointer-events-none absolute inset-y-0 start-0 grid w-11 place-items-center text-[color:var(--text-faint)]">
                        <x-ui-icon name="search" class="size-[17px]"/>
                    </span>
                    <input type="search" x-model.debounce.150ms="query"
                           enterkeyhint="search" autocomplete="off"
                           aria-label="{{ __('menu.search') }}"
                           placeholder="{{ __('menu.search_placeholder') }}"
                           class="u-field u-tap u-focus">
                    <button type="button" x-show="searching" x-cloak @click="query = ''"
                            aria-label="{{ __('menu.clear_search') }}"
                            class="u-focus absolute inset-y-0 end-0 grid w-11 place-items-center text-[color:var(--text-faint)] hover:text-accent">
                        <x-ui-icon name="close" class="size-4"/>
                    </button>
                </div>

                {{-- Category strip: underlined labels rather than pills — closer
                     to the way a menu sets its section headings. --}}
                <nav x-show="!searching" aria-label="{{ __('menu.menu') }}"
                     x-ref="tabs" class="u-scroll-x -mx-5 mt-2 flex gap-6 px-5 pb-px">
                    @foreach ($categories as $category)
                        @php $anchor = $category->anchor(); @endphp
                        <button type="button" data-tab="{{ $anchor }}"
                                @click="goTo('{{ $anchor }}')"
                                :aria-current="active === '{{ $anchor }}' ? 'true' : 'false'"
                                class="u-tab u-tap u-focus">
                            {{ $category->name }}
                        </button>
                    @endforeach
                </nav>
            </div>
        </header>

        <main id="main" class="px-5">

            <p x-show="searching && resultCount === 0" x-cloak
               class="py-20 text-center text-[0.875rem] text-[color:var(--text-dim)]">
                {{ __('menu.no_results') }}
            </p>

            @foreach ($categories as $category)
                @php $anchor = $category->anchor(); @endphp
                <section id="{{ $anchor }}" data-category="{{ $anchor }}" class="u-anchor"
                         x-show="sectionHasMatch($el)">

                    <header x-show="!searching" class="pb-1 pt-10 text-center">
                        <h2 class="u-display u-foil text-[1.375rem] tracking-wide">
                            {{ $category->name }}
                        </h2>
                        <div class="u-rule mt-3" aria-hidden="true"><span class="u-diamond"></span></div>
                    </header>

                    <ul class="divide-y divide-[color:var(--rule-soft)]">
                        @foreach ($category->items as $item)
                            <li data-search="{{ $item->searchIndex() }}"
                                x-show="matches($el.dataset.search)">
                                {{-- The sheet reads name, description and price back out of
                                     this row rather than from a duplicated JSON payload —
                                     with 85 items that duplication doubled the page weight. --}}
                                <button type="button"
                                        data-full="{{ $item->imageUrl() }}"
                                        data-featured="{{ $item->is_featured ? '1' : '' }}"
                                        data-available="{{ $item->is_available ? '1' : '' }}"
                                        @click="open($el)"
                                        class="u-focus u-row{{ $item->is_available ? '' : ' u-row-out' }}">

                                    <span class="u-thumb">
                                        @if ($item->thumbUrl())
                                            {{-- The first rows are the LCP candidate, so they load
                                                 eagerly; everything below the fold stays lazy. --}}
                                            <img src="{{ $item->thumbUrl() }}" alt="{{ $item->name }}"
                                                 width="144" height="144" decoding="async"
                                                 loading="{{ $loop->parent->first && $loop->index < 4 ? 'eager' : 'lazy' }}"
                                                 @if ($loop->parent->first && $loop->index === 0) fetchpriority="high" @endif>
                                        @else
                                            <x-ui-icon name="image" class="size-6"/>
                                        @endif
                                    </span>

                                    <span class="u-row-body">
                                        <span class="u-row-head">
                                            <span data-name class="u-row-name">{{ $item->name }}</span>
                                            <span class="u-leader" aria-hidden="true"></span>
                                            <span data-price class="u-row-price">{{ $item->formattedPrice() }}</span>
                                        </span>

                                        @if (filled($item->description))
                                            <span data-desc class="u-row-desc u-clamp-2">{{ $item->description }}</span>
                                        @endif

                                        @if ($item->is_featured || ! $item->is_available)
                                            <span class="flex flex-wrap gap-1.5">
                                                @if ($item->is_featured)
                                                    <span class="u-badge u-badge-pick">
                                                        <x-ui-icon name="star" class="size-3 fill-current"/>
                                                        {{ __('menu.featured') }}
                                                    </span>
                                                @endif
                                                @unless ($item->is_available)
                                                    <span class="u-badge">{{ __('menu.unavailable') }}</span>
                                                @endunless
                                            </span>
                                        @endif
                                    </span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach

            <footer class="pb-4 pt-14 text-center">
                <div class="u-rule" aria-hidden="true"><span class="u-diamond"></span></div>
                <p class="mt-5 text-[0.6875rem] tracking-[0.14em] text-[color:var(--text-faint)]">
                    © {{ date('Y') }} · {{ $s->name }}
                </p>
            </footer>
        </main>

        {{-- ── Dish dialog ─────────────────────────────────────────────
             A centred card rather than a bottom sheet: the photograph is the
             point, and a card lets it sit whole instead of cropped against the
             bottom of the screen. --}}
        <div x-show="sheet" x-cloak
             @keydown.escape.window="close()"
             class="fixed inset-0 z-50 grid place-items-center p-4"
             role="dialog" aria-modal="true" :aria-label="sheet?.name">

            <div x-show="sheet" x-transition.opacity.duration.250ms @click="close()"
                 class="absolute inset-0 bg-black/80 backdrop-blur-[6px]"></div>

            <div x-show="sheet"
                 x-transition:enter="transition duration-300 ease-[cubic-bezier(.16,1,.3,1)]"
                 x-transition:enter-start="opacity-0 translate-y-6 scale-[0.96]"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition duration-200 ease-in"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-[0.97]"
                 class="u-dialog relative max-h-[88dvh] w-full max-w-[26rem] overflow-y-auto">

                {{-- Gold hairline frame, inset so it reads as a mount around the card --}}
                <span aria-hidden="true" class="u-dialog-frame"></span>

                <button type="button" @click="close()" aria-label="{{ __('menu.close') }}"
                        class="u-focus absolute end-3 top-3 z-20 grid size-9 place-items-center rounded-full
                               border border-[color:var(--rule)] bg-[color:color-mix(in_srgb,var(--ground)_75%,transparent)]
                               text-[color:var(--text-dim)] backdrop-blur transition
                               hover:border-[color:var(--accent)] hover:text-accent">
                    <x-ui-icon name="close" class="size-4"/>
                </button>

                <template x-if="sheet">
                    <div class="relative">
                        <template x-if="sheet.image">
                            {{-- The photograph fades into the card, so there is no
                                 hard edge where the image stops. --}}
                            <div class="relative">
                                <img :src="sheet.image" :alt="sheet.name"
                                     class="aspect-[4/3] w-full object-cover">
                                <div aria-hidden="true" class="absolute inset-0
                                     bg-[linear-gradient(to_bottom,transparent_45%,color-mix(in_srgb,var(--ground)_65%,transparent)_78%,var(--ground)_100%)]"></div>
                            </div>
                        </template>

                        <div class="relative px-7 pb-8" :class="sheet.image ? '-mt-8' : 'pt-12'">
                            <div class="u-rule" aria-hidden="true"><span class="u-diamond"></span></div>

                            <h2 class="u-display u-foil mt-4 text-center text-[1.625rem] leading-tight"
                                x-text="sheet.name"></h2>

                            <p class="mt-3 text-center">
                                <span class="u-price-badge" x-text="sheet.price"></span>
                            </p>

                            <div class="mt-3 flex flex-wrap justify-center gap-2">
                                <template x-if="sheet.featured">
                                    <span class="u-badge u-badge-pick">{{ __('menu.featured') }}</span>
                                </template>
                                <template x-if="!sheet.available">
                                    <span class="u-badge">{{ __('menu.unavailable') }}</span>
                                </template>
                            </div>

                            <p class="mt-5 text-center text-[0.9375rem] leading-[1.9] text-[color:var(--text-dim)]"
                               x-show="sheet.description" x-text="sheet.description"></p>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</x-layouts.public>
