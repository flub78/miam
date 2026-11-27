<?php

namespace App\Filament\App\Resources\WeighIns\Pages;

use App\Filament\App\Resources\WeighIns\WeighInResource;
use App\Filament\App\Widgets\CourbePoids;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Laraskel\Charts\Widgets\Graphique;

/**
 * Liste des pesées avec la courbe de poids au-dessus : chaque création,
 * correction ou suppression rafraîchit la courbe (FR-32).
 */
class ManageWeighIns extends ManageRecords
{
    protected static string $resource = WeighInResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nouvelle pesée')
                ->createAnother(false)
                ->after(fn () => $this->dispatch(Graphique::EVENEMENT_RAFRAICHIR)),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CourbePoids::class,
        ];
    }
}
