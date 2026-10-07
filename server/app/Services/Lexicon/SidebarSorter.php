<?php

namespace App\Services\Lexicon;

use Illuminate\Support\Collection;
use Normalizer;

/**
 * Orders sidebar entries for the public lexicon layouts.
 *
 * Entries are compared on a lower-cased, accent-stripped key so that "ā", "a"
 * and "A" sort together regardless of diacritics. This replaces two named PHP
 * functions that were declared inside Blade views and could not be rendered
 * twice in one process.
 */
class SidebarSorter
{
    /**
     * Sort etyma (or anything with an `entry` attribute) by their headword.
     */
    public static function byEntry(Collection $items): Collection
    {
        return $items->sortBy(fn ($item) => self::sortKey((string) $item->entry));
    }

    /**
     * Sort reflexes by their comma-separated entries (see LexReflex::getEntriesCSV()).
     */
    public static function byEntries(Collection $items): Collection
    {
        return $items->sortBy(fn ($item) => self::sortKey((string) $item->getEntriesCSV()));
    }

    /**
     * Lower-case, decompose to NFD, drop combining marks (the diacritics), then keep
     * only letters and digits so that asterisks, hyphens, spaces and punctuation do
     * not affect the order. Letters of any script are kept.
     */
    public static function sortKey(string $text): string
    {
        $decomposed = Normalizer::normalize(mb_strtolower($text), Normalizer::FORM_D) ?: '';
        $withoutMarks = preg_replace('/\p{Mn}+/u', '', $decomposed) ?? '';

        return preg_replace('/[^\p{L}\p{N}]+/u', '', $withoutMarks) ?? '';
    }
}
