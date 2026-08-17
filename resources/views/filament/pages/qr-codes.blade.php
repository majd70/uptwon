<x-filament-panels::page>
    <div class="grid gap-6 lg:grid-cols-3">

        <div class="lg:col-span-2">
            <form wire:submit.prevent class="space-y-6">
                {{ $this->form }}
            </form>

            <div class="mt-6 flex flex-wrap gap-3">
                <x-filament::button wire:click="downloadPng" icon="heroicon-o-arrow-down-tray">
                    Download PNG (1024px)
                </x-filament::button>

                <x-filament::button wire:click="downloadSvg" color="gray" icon="heroicon-o-arrow-down-tray">
                    Download SVG
                </x-filament::button>

                <x-filament::button wire:click="downloadTableZip" color="gray" icon="heroicon-o-archive-box">
                    Download table codes (ZIP)
                </x-filament::button>
            </div>
        </div>

        {{-- Live preview --}}
        <div>
            <x-filament::section>
                <x-slot name="heading">Preview</x-slot>

                <div class="flex flex-col items-center gap-4">
                    <img src="{{ $this->preview }}" alt="QR code preview"
                         class="w-full max-w-[280px] rounded-xl border border-gray-200 dark:border-gray-700">

                    <div class="w-full break-all rounded-lg bg-gray-50 p-3 text-center text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                        {{ $this->targetUrl }}
                    </div>

                    <p class="text-center text-xs text-gray-500 dark:text-gray-400">
                        Every scan is recorded on the dashboard. Codes use the highest error-correction
                        level, so they still scan when printed small or slightly smudged.
                    </p>
                </div>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
