<x-filament-panels::page>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2">
            <x-filament-panels::form wire:submit="save">
                {{ $this->form }}
        
                <x-filament-panels::form.actions
                    :actions="$this->getFormActions()"
                />
            </x-filament-panels::form>
        </div>

        <div class="lg:col-span-1">
            <x-whatsapp-preview :data="$this->data" :active-screen-index="$this->activeScreenIndex" />
        </div>
    </div>
</x-filament-panels::page>
