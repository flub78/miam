<?php

namespace App\Filament\App\Resources\AcquiredHabitChanges\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AcquiredHabitChangeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('changed_on')
                    ->label('Depuis le')
                    ->default(now())
                    ->maxDate(now())
                    ->required(),
                TextInput::make('description')
                    ->label('Changement')
                    ->placeholder('Ex. : sucre supprimé dans le café')
                    ->required()
                    ->maxLength(500),
            ]);
    }
}
