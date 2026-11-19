<?php

namespace App\Filament\App\Resources\AcquiredHabitChanges\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AcquiredHabitChangesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('changed_on', 'desc')
            ->emptyStateHeading('Aucun changement d\'habitude enregistré')
            ->emptyStateDescription('Notez ici ce que vous avez déjà changé durablement, par exemple « sucre supprimé dans le café ».')
            ->columns([
                TextColumn::make('changed_on')->label('Depuis le')->date('d/m/Y')->sortable(),
                TextColumn::make('description')->label('Changement')->searchable()->wrap(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
