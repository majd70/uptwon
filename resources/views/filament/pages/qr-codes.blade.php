{{--
    Layout sizing uses inline styles on purpose: this page is rendered with
    Filament's own stylesheet, which does not contain arbitrary-value utilities
    from the public site's Tailwind build (max-w-[280px], lg:grid-cols-5, …).
    Filament also has no `styles` Blade stack, so a media query cannot be pushed
    from here — the page is a single column at every width by design.
--}}
<x-filament-panels::page>
    <div style="display:grid; gap:1.5rem; max-width:42rem;">

        <div>
            <form wire:submit.prevent>
                {{ $this->form }}
            </form>

            <div style="display:flex; flex-wrap:wrap; gap:0.75rem; margin-top:1.5rem;">
                <x-filament::button wire:click="downloadPng" icon="heroicon-o-arrow-down-tray">
                    Download PNG (1024px)
                </x-filament::button>

                <x-filament::button wire:click="downloadSvg" color="gray" icon="heroicon-o-arrow-down-tray">
                    Download SVG
                </x-filament::button>
            </div>

            <p class="fi-color-gray" style="margin-top:1rem; max-width:46ch; font-size:0.875rem; opacity:0.7;">
                PNG suits stickers, table cards and normal printing. SVG stays sharp at any
                size — use it for a poster or a large sign.
            </p>
        </div>

        <x-filament::section>
            <x-slot name="heading">Preview</x-slot>

            <div style="display:flex; flex-direction:column; align-items:center; gap:1rem;">
                <img src="{{ $this->preview }}" alt="QR code preview"
                     width="280" height="280"
                     style="width:100%; max-width:280px; height:auto; border-radius:0.75rem;">

                <div style="width:100%; max-width:340px; padding:0.75rem; border-radius:0.5rem;
                            background:rgba(128,128,128,0.1); font-size:0.75rem; text-align:center;
                            word-break:break-all;">
                    {{ $this->targetUrl }}
                </div>

                <p style="font-size:0.75rem; opacity:0.65; text-align:center;">
                    Every scan is counted on the dashboard.
                </p>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
