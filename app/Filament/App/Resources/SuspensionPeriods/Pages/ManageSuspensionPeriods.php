<?php

namespace App\Filament\App\Resources\SuspensionPeriods\Pages;

use App\Filament\App\Resources\SuspensionPeriods\SuspensionPeriodResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageSuspensionPeriods extends ManageRecords
{
    protected static string $resource = SuspensionPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Déclarer une suspension')->createAnother(false),
        ];
    }
}
