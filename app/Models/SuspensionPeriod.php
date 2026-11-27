<?php

namespace App\Models;

use App\Models\Concerns\AppartientAUtilisateur;
use Carbon\CarbonInterface;
use Database\Factories\SuspensionPeriodFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Laraskel\Users\Models\Concerns\TracksAuthor;

/**
 * Période de suspension du suivi (FR-59 à FR-61) : voyage, maladie… Les
 * pesées saisies pendant une suspension sont conservées mais exclues des
 * calculs de tendance.
 *
 * @property int $id
 * @property int $user_id
 * @property Carbon $starts_on
 * @property Carbon $ends_on
 * @property string $reason
 */
class SuspensionPeriod extends Model
{
    use AppartientAUtilisateur;

    /** @use HasFactory<SuspensionPeriodFactory> */
    use HasFactory;

    use TracksAuthor;

    protected $fillable = ['starts_on', 'ends_on', 'reason'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    /**
     * Vrai si le jour (bornes incluses) tombe dans la période.
     */
    public function couvre(CarbonInterface $jour): bool
    {
        $date = $jour->toDateString();

        return $date >= $this->starts_on->toDateString() && $date <= $this->ends_on->toDateString();
    }
}
