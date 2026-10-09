<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\FixtureLexiconTestHelpers;
use Tests\TestCase;

class LexiconLocaleSwitchTest extends TestCase
{
    use FixtureLexiconTestHelpers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedFixtureLexicon();
    }

    public function test_offered_language_is_stored_and_reader_is_sent_back(): void
    {
        $this->get('/lexicon/fixturelex/switchlang/te?return_to=/lexicon/fixturelex/data')
            ->assertRedirect('/lexicon/fixturelex/data')
            ->assertSessionHas('viewer_lang_code', 'te');
    }

    public function test_language_the_lexicon_does_not_offer_is_ignored(): void
    {
        $this->get('/lexicon/fixturelex/switchlang/fr')
            ->assertRedirect('/lexicon/fixturelex')
            ->assertSessionMissing('viewer_lang_code');
    }

    public function test_return_path_off_site_falls_back_to_the_lexicon_home(): void
    {
        $this->get('/lexicon/fixturelex/switchlang/en?return_to=https://example.com/elsewhere')
            ->assertRedirect('/lexicon/fixturelex');

        $this->get('/lexicon/fixturelex/switchlang/en?return_to=//example.com/elsewhere')
            ->assertRedirect('/lexicon/fixturelex');
    }

    public function test_unknown_lexicon_is_not_found(): void
    {
        $this->get('/lexicon/no-such-lexicon/switchlang/en')->assertNotFound();
    }
}
