<?php
namespace Clicalmani\Validation\Rules;

trait NormalizesModel
{
    /**
     * Normalise le nom du modèle
     */
    private function normalizeModelName(string $model): string
    {
        if (str_contains($model, '\\')) {
            return $model;
        }

        if (str_ends_with($model, 's')) {
            $model = substr($model, 0, -1);
        }

        return collect(explode('_', $model))
            ->map(fn(string $part) => ucfirst($part))
            ->join('');
    }
}