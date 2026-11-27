<?php

namespace App\Policies;

/**
 * Accès aux lignes `App\Models\TrackingSettings` : propriétaire uniquement (FR-5).
 */
class TrackingSettingsPolicy extends PolitiqueProprietaire {}
