<x-filament-panels::page>
    <form wire:submit="enregistrer" class="grid gap-y-6">
        {{ $this->form }}

        <div>
            <x-filament::button type="submit">Enregistrer</x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
