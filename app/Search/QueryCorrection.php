<?php

namespace App\Search;

class QueryCorrection
{
    /**
     * Suggest a close query when the typed words are not in the vocabulary.
     *
     * @param  array<int, string>  $words
     */
    public function suggest(string $query, array $words): ?string
    {
        $tokens = $this->tokens($query);
        $dictionary = $this->dictionary($words);

        if ($tokens === [] || $dictionary === []) {
            return null;
        }

        $corrected = [];
        $changed = false;

        foreach ($tokens as $token) {
            $match = $this->closest($token, $dictionary);

            if ($match === null) {
                return null;
            }

            $corrected[] = $match;
            $changed = $changed || mb_strtolower($match) !== mb_strtolower($token);
        }

        if (! $changed) {
            return null;
        }

        return implode(' ', $corrected);
    }

    /**
     * @param  array<int, string>  $words
     * @return array<string, string>
     */
    private function dictionary(array $words): array
    {
        $dictionary = [];

        foreach ($words as $word) {
            foreach ($this->tokens($word) as $token) {
                $dictionary[mb_strtolower($token)] ??= $token;
            }
        }

        return $dictionary;
    }

    /**
     * @return list<string>
     */
    private function tokens(string $text): array
    {
        preg_match_all('/[\p{L}\p{N}]+/u', $text, $matches);

        return $matches[0];
    }

    /**
     * @param  array<string, string>  $dictionary
     */
    private function closest(string $token, array $dictionary): ?string
    {
        $key = mb_strtolower($token);

        if (isset($dictionary[$key])) {
            return $dictionary[$key];
        }

        $limit = $this->maxDistance(mb_strlen($key));

        if ($limit === 0) {
            return null;
        }

        $best = null;
        $bestDistance = $limit + 1;

        foreach ($dictionary as $candidate => $display) {
            if (abs(mb_strlen($candidate) - mb_strlen($key)) > $limit) {
                continue;
            }

            if (! $this->isAscii($key) || ! $this->isAscii($candidate)) {
                continue;
            }

            $distance = levenshtein($key, $candidate);

            if ($distance < $bestDistance) {
                $best = $display;
                $bestDistance = $distance;
            }
        }

        return $bestDistance <= $limit ? $best : null;
    }

    private function maxDistance(int $length): int
    {
        if ($length >= 9) {
            return 2;
        }

        if ($length >= 5) {
            return 1;
        }

        return 0;
    }

    private function isAscii(string $value): bool
    {
        return preg_match('/^[\x20-\x7E]+$/', $value) === 1;
    }
}
