<?php

namespace App\Models;

use App\Models\Concerns\AppartientAUtilisateur;
use Database\Factories\AcquiredHabitChangeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Laraskel\Users\Models\Concerns\TracksAuthor;

/**
 * Un changement d'habitude acquis (FR-4), ex. « sucre supprimé dans le café ».
 *
 * @property int $id
 * @property int $user_id
 * @property Carbon $changed_on
 * @property string $description
 */
class AcquiredHabitChange extends Model
{
    use AppartientAUtilisateur;

    /** @use HasFactory<AcquiredHabitChangeFactory> */
    use HasFactory;

    use TracksAuthor;

    protected $fillable = ['changed_on', 'description'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'changed_on' => 'date',
        ];
    }
}
