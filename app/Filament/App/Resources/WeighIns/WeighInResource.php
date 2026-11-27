<?php

namespace App\Filament\App\Resources\WeighIns;

use App\Filament\App\Resources\WeighIns\Pages\ManageWeighIns;
use App\Models\SuspensionPeriod;
use App\Models\WeighIn;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Laraskel\Charts\Widgets\Graphique;
use UnitEnum;

/**
 * Pesées de l'utilisateur connecté (FR-11, FR-12) : saisie, correction et
 * suppression en fenêtre modale, la courbe de poids au-dessus de la liste se
 * met à jour après chaque écriture (FR-32). Isolation par le global scope du
 * modèle et `WeighInPolicy` (FR-5).
 */
class WeighInResource extends Resource
{
    protected static ?string $model = WeighIn::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static string|UnitEnum|null $navigationGroup = 'Suivi';

    protected static ?string $navigationLabel = 'Pesées';

    protected static ?string $modelLabel = 'pesée';

    protected static ?string $pluralModelLabel = 'Pesées';

    protected static ?string $slug = 'pesees';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                DatePicker::make('measured_on')
                    ->label('Date')
                    ->default(now())
                    ->maxDate(now())
                    ->required()
                    ->validationMessages([
                        'before_or_equal' => 'Une pesée ne peut pas être datée dans le futur.',
                    ]),
                TextInput::make('weight_kg')
                    ->label('Poids')
                    ->suffix('kg')
                    ->numeric()
                    ->minValue(20)
                    ->maxValue(400)
                    ->step(0.1)
                    ->required()
                    ->validationMessages([
                        'required' => 'Indiquez le poids mesuré, en kg (entre 20 et 400).',
                    ]),
                TextInput::make('body_fat_pct')
                    ->label('Masse grasse')
                    ->suffix('%')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(80)
                    ->step(0.1),
                TextInput::make('muscle_mass_pct')
                    ->label('Masse musculaire')
                    ->suffix('%')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(90)
                    ->step(0.1),
                Textarea::make('note')
                    ->label('Note')
                    ->rows(2)
                    ->maxLength(500)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('measured_on', 'desc')
            ->emptyStateHeading('Aucune pesée')
            ->emptyStateDescription('Ajoutez votre première pesée : la courbe et la tendance se calculent à partir d\'elles.')
            ->columns([
                TextColumn::make('measured_on')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable()
                    ->description(fn (WeighIn $pesee): ?string => self::pendantUneSuspension($pesee) ? 'Pendant une suspension : exclue de la tendance' : null),
                TextColumn::make('weight_kg')->label('Poids')->suffix(' kg')->numeric(1, ',', ' ')->sortable(),
                TextColumn::make('body_fat_pct')->label('Masse grasse')->suffix(' %')->numeric(1, ',', ' ')->toggleable(),
                TextColumn::make('muscle_mass_pct')->label('Masse musculaire')->suffix(' %')->numeric(1, ',', ' ')->toggleable(),
                TextColumn::make('note')->label('Note')->limit(60)->wrap()->toggleable(),
            ])
            ->recordActions([
                EditAction::make()->after(fn (ManageWeighIns $livewire) => $livewire->dispatch(Graphique::EVENEMENT_RAFRAICHIR)),
                DeleteAction::make()->after(fn (ManageWeighIns $livewire) => $livewire->dispatch(Graphique::EVENEMENT_RAFRAICHIR)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageWeighIns::route('/'),
        ];
    }

    /**
     * Les suspensions sont chargées une fois par requête, pas une fois par ligne.
     */
    private static function pendantUneSuspension(WeighIn $pesee): bool
    {
        /** @var Collection<int, SuspensionPeriod> $suspensions */
        $suspensions = once(fn (): Collection => SuspensionPeriod::query()->get());

        return $suspensions->contains(fn (SuspensionPeriod $periode): bool => $periode->couvre($pesee->measured_on));
    }
}
