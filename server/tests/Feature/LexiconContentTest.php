<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\FixtureLexiconTestHelpers;
use Tests\TestCase;

/**
 * Content-level assertions on top of the fixturelex fixture: the hardest
 * normalization/rendering cases from ADR 0005 §4 -- a reconstructed
 * headword rendered with its asterisk and diacritics intact, a viewer-
 * language switch producing a Telugu gloss, and a right-to-left Hebrew
 * headword rendering unmangled.
 */
class LexiconContentTest extends TestCase
{
    use FixtureLexiconTestHelpers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedFixtureLexicon();
    }

    public function test_etymon_page_shows_the_reconstructed_headword_exactly(): void
    {
        $etymon = $this->fixtureEtymon('ph₂tḕr');

        $response = $this->get("/lexicon/fixturelex/etymon/{$etymon->id}");

        $response->assertOk();
        // lex_etymon.blade.php renders the leading asterisk via <sup>*</sup>
        // followed immediately by the raw `entry` value, so the two are
        // asserted separately rather than as one concatenated string.
        $response->assertSee('<sup>*</sup>', false);
        // `entry` itself carries the laryngeal notation (h₂ subscript) and
        // the macron+grave (ḕ) diacritic.
        $response->assertSee($etymon->entry, false);
    }

    public function test_switching_to_telugu_renders_the_telugu_gloss_on_the_word_page(): void
    {
        $reflex = $this->fixtureReflexByEntry('fæder');

        // switch_lang() stores the viewer language in the session; the next
        // request's SetLocaleFromSessionMiddleware applies it before the
        // translatable `gloss` attribute is resolved.
        $this->get('/lexicon/fixturelex/switchlang/te');

        $response = $this->get("/lexicon/fixturelex/word/{$reflex->id}");

        $response->assertOk();
        $response->assertSee('తండ్రి', false);
    }

    public function test_rtl_hebrew_reflex_page_renders_the_non_latin_headword(): void
    {
        $reflex = $this->fixtureReflexByEntry('אָב');

        $response = $this->get("/lexicon/fixturelex/word/{$reflex->id}");

        $response->assertOk();
        $response->assertSee('אָב', false);
    }
}
