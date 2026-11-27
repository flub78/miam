<x-filament-panels::page>
    <form wire:submit="enregistrer" class="grid gap-y-6">
        {{ $this->form }}

        <div>
            <x-filament::button type="submit">Enregistrer l'objectif</x-filament::button>
        </div>
    </form>

    {{ $this->table }}
</x-filament-panels::page>
