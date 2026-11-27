<?php

namespace App\Filament\App\Resources\AcquiredHabitChanges\Pages;

use App\Filament\App\Resources\AcquiredHabitChanges\AcquiredHabitChangeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAcquiredHabitChanges extends ListRecords
{
    protected static string $resource = AcquiredHabitChangeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
