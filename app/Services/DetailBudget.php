<?php

namespace App\Services;

/**
 * Détail du calcul du budget calorique quotidien (FR-7), en kcal par jour.
 */
final readonly class DetailBudget
{
    public function __construct(
        public int $metabolismeDeBase,
        public int $maintenance,
        public int $deficit,
        public int $budget,
        public bool $plancherApplique,
    ) {}
}
