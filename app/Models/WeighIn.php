<?php

namespace App\Models;

use App\Models\Concerns\AppartientAUtilisateur;
use Database\Factories\WeighInFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Laraskel\Users\Models\Concerns\TracksAuthor;

/**
 * Une pesée (FR-11, FR-12). Plusieurs pesées un même jour sont permises ; les
 * calculs de tendance utilisent leur moyenne journalière.
 *
 * @property int $id
 * @property int $user_id
 * @property Carbon $measured_on
 * @property string $weight_kg
 * @property ?string $body_fat_pct
 * @property ?string $muscle_mass_pct
 * @property ?string $note
 */
class WeighIn extends Model
{
    use AppartientAUtilisateur;

    /** @use HasFactory<WeighInFactory> */
    use HasFactory;

    use TracksAuthor;

    protected $fillable = ['measured_on', 'weight_kg', 'body_fat_pct', 'muscle_mass_pct', 'note'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'measured_on' => 'date',
            'weight_kg' => 'decimal:1',
            'body_fat_pct' => 'decimal:1',
            'muscle_mass_pct' => 'decimal:1',
        ];
    }
}
