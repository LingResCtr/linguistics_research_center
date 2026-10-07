<?php

namespace Tests\Feature\Concerns;

use App\Models\LexEtyma;
use App\Models\LexLanguage;
use App\Models\LexLexicon;
use App\Models\LexReflex;
use App\Models\LexSemanticField;
use Database\Seeders\FixtureLexiconSeeder;
use RuntimeException;

/**
 * Shared lookups for tests that exercise the "fixturelex" fixture built by
 * FixtureLexiconSeeder (see ADR 0005 §4). Fixture rows are located by their
 * natural keys (slug, abbr, entry, entries text) rather than hard-coded ids,
 * since ids depend on auto-increment order.
 */
trait FixtureLexiconTestHelpers
{
    protected function seedFixtureLexicon(): void
    {
        $this->seed(FixtureLexiconSeeder::class);
    }

    protected function fixtureLexicon(): LexLexicon
    {
        return LexLexicon::where('slug', 'fixturelex')->firstOrFail();
    }

    protected function fixtureLanguage(string $abbr): LexLanguage
    {
        return LexLanguage::where('abbr', $abbr)->firstOrFail();
    }

    protected function fixtureEtymon(string $entry): LexEtyma
    {
        return LexEtyma::where('entry', $entry)->firstOrFail();
    }

    protected function fixtureSemanticField(string $abbr): LexSemanticField
    {
        return LexSemanticField::where('abbr', $abbr)->firstOrFail();
    }

    /**
     * Find the fixture reflex whose `entries` JSON contains the given
     * headword text. Filtered in PHP (the fixture is small) to avoid
     * depending on JSON-path query quirks across drivers.
     */
    protected function fixtureReflexByEntry(string $text): LexReflex
    {
        $reflex = LexReflex::all()->first(
            fn (LexReflex $reflex) => collect($reflex->entries)->pluck('text')->contains($text)
        );

        if (! $reflex) {
            throw new RuntimeException("No fixture reflex found with entry text '{$text}'.");
        }

        return $reflex;
    }
}
