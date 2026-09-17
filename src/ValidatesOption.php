<?php
namespace Clicalmani\Validation;

trait ValidatesOption
{
    /**
    * Validates individual characters in a custom format string.
    *
    * @param string $format
    * @return bool
    */
    private function validateFormatString(string $format): bool
    {
        $allowedChars = ['Y', 'm', 'd', 'H', 'i', 's', ' '];
        $allowedSeparators = ['-', '/', '.', ':', 'T', ' ', '+'];
        
        $clean = str_replace($allowedSeparators, '', $format);
        $chars = str_split($clean);
        
        foreach ($chars as $char) {
            if (!in_array($char, $allowedChars, true)) {
                return false;
            }
        }
        
        return true;
    }

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

    /**
     * Parses human-readable size notations (K, M, G) into byte integers.
     *
     * @param string $value
     * @return int
     */
    private function parseSize(string $value): int
    {
        $value = strtoupper($value);
        
        if (preg_match('/^(\d+)([KMG])?$/', $value, $matches)) {
            $size = (int) $matches[1];
            $unit = $matches[2] ?? '';
            
            return match ($unit) {
                'K' => $size * 1024,
                'M' => $size * 1024 * 1024,
                'G' => $size * 1024 * 1024 * 1024,
                default => $size
            };
        }
        
        return (int) $value;
    }

    /**
     * Parses numeric notation strings including shorthand units (K, M, G).
     *
     * @param string $value
     * @return float|int
     */
    private function parseNumeric(string $value): float|int
    {
        $value = trim($value);
        
        if (preg_match('/^(\d+\.?\d*)([KMG])?$/i', $value, $matches)) {
            $number = (float) $matches[1];
            $unit = strtoupper($matches[2] ?? '');
            
            return match ($unit) {
                'K' => $number * 1000,
                'M' => $number * 1000000,
                'G' => $number * 1000000000,
                default => $number,
            };
        }
        
        return (float) $value;
    }

    /**
     * Validates structural syntax of regular expression pattern string.
     *
     * @param string $pattern
     * @return bool
     */
    private function validatePattern(string $pattern): bool
    {
        if (empty($pattern)) {
            return false;
        }

        $delimiter = $this->detectDelimiter($pattern);
        $testPattern = $delimiter . $pattern . $delimiter;
        
        return @preg_match($testPattern, 'test') !== false;
    }

    /**
     * Validates regex modifier flags string against PCRE allowed set.
     *
     * @param string $flags
     * @return bool
     */
    private function validateFlags(string $flags): bool
    {
        $allowedFlags = ['i', 'm', 's', 'x', 'u', 'U', 'A', 'D', 'S', 'J'];
        $flagChars = str_split($flags);
        
        foreach ($flagChars as $char) {
            if (!in_array($char, $allowedFlags, true)) {
                return false;
            }
        }
        
        return true;
    }
}