<?php

namespace App\Filament\App\Resources\AcquiredHabitChanges\Pages;

use App\Filament\App\Resources\AcquiredHabitChanges\AcquiredHabitChangeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAcquiredHabitChange extends EditRecord
{
    protected static string $resource = AcquiredHabitChangeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
