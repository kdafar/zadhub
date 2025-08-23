<x-filament-panels::page>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div>
            <x-filament-panels::form wire:submit="save">
                {{ $this->form }}
        
                <x-filament-panels::form.actions
                    :actions="$this->getFormActions()"
                />
            </x-filament-panels::form>
        </div>

        <div>
            <x-whatsapp-preview :data="$this->data" />
        </div>
    </div>
</x-filament-panels::page>
