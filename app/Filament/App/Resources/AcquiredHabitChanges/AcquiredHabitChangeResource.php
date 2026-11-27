<?php

namespace App\Filament\App\Resources\AcquiredHabitChanges;

use App\Filament\App\Resources\AcquiredHabitChanges\Pages\CreateAcquiredHabitChange;
use App\Filament\App\Resources\AcquiredHabitChanges\Pages\EditAcquiredHabitChange;
use App\Filament\App\Resources\AcquiredHabitChanges\Pages\ListAcquiredHabitChanges;
use App\Filament\App\Resources\AcquiredHabitChanges\Schemas\AcquiredHabitChangeForm;
use App\Filament\App\Resources\AcquiredHabitChanges\Tables\AcquiredHabitChangesTable;
use App\Models\AcquiredHabitChange;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Changements d'habitudes acquis de l'utilisateur connecté (FR-4). Isolation par
 * le global scope du modèle et `AcquiredHabitChangePolicy` (FR-5).
 */
class AcquiredHabitChangeResource extends Resource
{
    protected static ?string $model = AcquiredHabitChange::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckBadge;

    protected static string|UnitEnum|null $navigationGroup = 'Mon profil';

    protected static ?string $navigationLabel = 'Habitudes acquises';

    protected static ?string $modelLabel = 'changement d\'habitude';

    protected static ?string $pluralModelLabel = 'Changements d\'habitudes acquis';

    protected static ?string $slug = 'habitudes';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return AcquiredHabitChangeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AcquiredHabitChangesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAcquiredHabitChanges::route('/'),
            'create' => CreateAcquiredHabitChange::route('/create'),
            'edit' => EditAcquiredHabitChange::route('/{record}/edit'),
        ];
    }
}
