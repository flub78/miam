<?php

namespace App\Policies;

/**
 * Accès aux lignes `App\Models\WeighIn` : propriétaire uniquement (FR-5).
 */
class WeighInPolicy extends PolitiqueProprietaire {}
