<?php

namespace App\Filament\App\Resources\SuspensionPeriods;

use App\Filament\App\Resources\SuspensionPeriods\Pages\ManageSuspensionPeriods;
use App\Models\SuspensionPeriod;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Périodes de suspension du suivi (FR-59) : voyage, maladie… Les pesées de ces
 * jours restent enregistrées mais sont exclues de la tendance (FR-61) et
 * grisées dans les courbes (FR-36). Isolation par le global scope du modèle et
 * `SuspensionPeriodPolicy` (FR-5).
 */
class SuspensionPeriodResource extends Resource
{
    protected static ?string $model = SuspensionPeriod::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPauseCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Suivi';

    protected static ?string $navigationLabel = 'Suspensions';

    protected static ?string $modelLabel = 'période de suspension';

    protected static ?string $pluralModelLabel = 'Périodes de suspension';

    protected static ?string $slug = 'suspensions';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                DatePicker::make('starts_on')
                    ->label('Du')
                    ->default(now())
                    ->required(),
                DatePicker::make('ends_on')
                    ->label('Au (inclus)')
                    ->required()
                    ->afterOrEqual('starts_on')
                    ->validationMessages([
                        'after_or_equal' => 'La fin de la suspension doit être le même jour que le début ou après.',
                    ]),
                TextInput::make('reason')
                    ->label('Motif')
                    ->placeholder('Ex. : voyage gastronomique en Italie')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('starts_on', 'desc')
            ->emptyStateHeading('Aucune période de suspension')
            ->emptyStateDescription('Déclarez un voyage ou une maladie : les pesées de ces jours ne fausseront pas votre tendance.')
            ->columns([
                TextColumn::make('starts_on')->label('Du')->date('d/m/Y')->sortable(),
                TextColumn::make('ends_on')->label('Au')->date('d/m/Y'),
                TextColumn::make('duree')
                    ->label('Durée')
                    ->state(fn (SuspensionPeriod $periode): string => ((int) $periode->starts_on->diffInDays($periode->ends_on) + 1).' j'),
                TextColumn::make('reason')->label('Motif')->wrap(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSuspensionPeriods::route('/'),
        ];
    }
}
