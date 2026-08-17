<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <div class="flex justify-end gap-3">
            <x-filament::button
                tag="a"
                href="{{ route('landing') }}"
                target="_blank"
                color="gray"
                icon="heroicon-o-arrow-top-right-on-square">
                View public page
            </x-filament::button>

            <x-filament::button type="submit">
                Save changes
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
