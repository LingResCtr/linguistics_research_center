<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\EieolGloss;
use App\Models\EieolGlossedText;
use App\Models\EieolGrammar;
use App\Models\EieolHeadWord;
use App\Models\EieolLanguage;
use App\Models\EieolLesson;
use App\Models\EieolSeries;
use App\Models\LexEtyma;
use App\Models\LexLanguage;
use App\Models\LexLanguageFamily;
use App\Models\LexLanguageSubFamily;
use App\Models\LexLexicon;
use App\Models\LexPartOfSpeech;
use App\Models\LexReflex;
use App\Models\LexSemanticCategory;
use App\Models\LexSemanticField;
use App\Models\LexSource;
use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

/**
 * A small but deliberately hard fixture lexicon (slug "fixturelex") plus the
 * CMS pages, book, EIEOL series and admin user needed for local development,
 * the agent sandbox and the smoke tests. See ADR 0005 §4.
 *
 * Deliberately-hard cases baked in here (see individual comments below):
 *   - four daughter languages spanning Latin-with-diacritics, polytonic
 *     Greek, right-to-left Hebrew, and Telugu;
 *   - reconstructed etyma using asterisk, laryngeal, macron and superscript
 *     notation;
 *   - an etymon with 6 reflexes and one with none;
 *   - a reflex attached to two etyma (disputed etymology);
 *   - a reflex with an extra-data row;
 *   - sources with and without a page number;
 *   - glosses translated in en/es/te and glosses only in en.
 */
class FixtureLexiconSeeder extends Seeder
{
    public function run(): void
    {
        if (LexLexicon::where('slug', 'fixturelex')->exists()) {
            $this->command?->info('FixtureLexiconSeeder: fixturelex already exists, skipping.');

            return;
        }

        $lexicon = $this->seedLexicon();
        $languages = $this->seedLanguages($lexicon);
        $fields = $this->seedSemantics($lexicon);
        $sources = $this->seedSources($lexicon);
        $partsOfSpeech = $this->seedPartsOfSpeech($lexicon);
        $etyma = $this->seedEtymaAndReflexes($lexicon, $languages, $fields, $sources, $partsOfSpeech);
        $this->seedEieol($etyma);
        $this->seedCms();
        $this->seedUser();

        Artisan::call('app:generate-lexicon-data-cache', ['lexicon_id' => $lexicon->id]);
    }

    protected function seedLexicon(): LexLexicon
    {
        return LexLexicon::factory()->create([
            'name' => 'Fixture Lexicon',
            'slug' => 'fixturelex',
            'protolang_name' => [
                'en' => 'Proto-Fixture',
                'es' => 'Proto-Ficticio',
                'te' => 'ప్రోటో-ఫిక్చర్',
            ],
            'viewer_lang_options' => 'en, es, te',
            'landing_page_content' => [
                'en' => '<p>Welcome to the Fixture Lexicon, a small representative dataset for tests and onboarding.</p>',
                'es' => '<p>Bienvenido al Léxico Ficticio.</p>',
                'te' => '<p>ఫిక్చర్ లెక్సికాన్‌కు స్వాగతం.</p>',
            ],
            'protolanguage_page_content' => [
                'en' => '<p>Proto-Fixture is a made-up proto-language used only for tests.</p>',
                'es' => '<p>Proto-Ficticio es una lengua inventada solo para pruebas.</p>',
                'te' => '<p>ప్రోటో-ఫిక్చర్ ఒక కల్పిత భాష.</p>',
            ],
        ]);
    }

    /**
     * Four daughter languages under one family/sub-family, covering Latin
     * script with diacritics (Old English ǣ, þ), polytonic Greek, the
     * right-to-left Hebrew script, and Telugu.
     *
     * @return array<string, LexLanguage>
     */
    protected function seedLanguages(LexLexicon $lexicon): array
    {
        $family = LexLanguageFamily::factory()->create([
            'lexicon_id' => $lexicon->id,
            'name' => ['en' => 'Fixture Family', 'es' => 'Familia Ficticia', 'te' => 'ఫిక్చర్ కుటుంబం'],
            'order' => 1,
        ]);

        $subFamily = LexLanguageSubFamily::factory()->create([
            'family_id' => $family->id,
            'name' => ['en' => 'Fixture Branch', 'es' => 'Rama Ficticia', 'te' => 'ఫిక్చర్ శాఖ'],
            'order' => 1,
        ]);

        $oe = LexLanguage::factory()->create([
            'sub_family_id' => $subFamily->id,
            'name' => ['en' => 'Old English', 'es' => 'Inglés Antiguo', 'te' => 'పాత ఇంగ్లీష్'],
            'description' => ['en' => '<p>West Germanic; forms here deliberately use ǣ and þ.</p>'],
            'abbr' => 'OE',
            'order' => 1,
        ]);

        $grc = LexLanguage::factory()->create([
            'sub_family_id' => $subFamily->id,
            'name' => ['en' => 'Ancient Greek', 'es' => 'Griego Antiguo', 'te' => 'పురాతన గ్రీకు'],
            'description' => ['en' => '<p>Polytonic Greek, with breathings and accents.</p>'],
            'abbr' => 'GRC',
            'order' => 2,
        ]);

        $heb = LexLanguage::factory()->create([
            'sub_family_id' => $subFamily->id,
            'name' => ['en' => 'Hebrew', 'es' => 'Hebreo', 'te' => 'హీబ్రూ'],
            'description' => ['en' => '<p>Right-to-left script with niqqud.</p>'],
            'abbr' => 'HEB',
            'order' => 3,
        ]);

        $te = LexLanguage::factory()->create([
            'sub_family_id' => $subFamily->id,
            'name' => ['en' => 'Telugu', 'es' => 'Telugu', 'te' => 'తెలుగు'],
            'description' => ['en' => '<p>Dravidian language, Telugu script.</p>'],
            'abbr' => 'TE',
            'order' => 4,
        ]);

        return ['oe' => $oe, 'grc' => $grc, 'heb' => $heb, 'te' => $te];
    }

    /**
     * Two semantic categories, four semantic fields.
     *
     * @return array<string, LexSemanticField>
     */
    protected function seedSemantics(LexLexicon $lexicon): array
    {
        $kinship = LexSemanticCategory::factory()->create([
            'lexicon_id' => $lexicon->id,
            'text' => ['en' => 'Kinship', 'es' => 'Parentesco', 'te' => 'బంధుత్వం'],
            'number' => '1',
            'abbr' => 'KIN',
        ]);

        $bodyNature = LexSemanticCategory::factory()->create([
            'lexicon_id' => $lexicon->id,
            'text' => ['en' => 'The Body and Nature', 'es' => 'El Cuerpo y la Naturaleza', 'te' => 'శరీరం మరియు ప్రకృతి'],
            'number' => '2',
            'abbr' => 'BDN',
        ]);

        $parentTerms = LexSemanticField::factory()->create([
            'semantic_category_id' => $kinship->id,
            'text' => ['en' => 'parent terms', 'es' => 'términos de padres', 'te' => 'తల్లిదండ్రుల పదాలు'],
            'number' => '1.1',
            'abbr' => 'KIN.PAR',
        ]);

        $familyRelations = LexSemanticField::factory()->create([
            'semantic_category_id' => $kinship->id,
            'text' => ['en' => 'family relations', 'es' => 'relaciones familiares', 'te' => 'కుటుంబ సంబంధాలు'],
            'number' => '1.2',
            'abbr' => 'KIN.FAM',
        ]);

        $bodyParts = LexSemanticField::factory()->create([
            'semantic_category_id' => $bodyNature->id,
            'text' => ['en' => 'body parts', 'es' => 'partes del cuerpo', 'te' => 'శరీర భాగాలు'],
            'number' => '2.1',
            'abbr' => 'BDN.BOD',
        ]);

        $naturalElements = LexSemanticField::factory()->create([
            'semantic_category_id' => $bodyNature->id,
            'text' => ['en' => 'natural elements', 'es' => 'elementos naturales', 'te' => 'సహజ మూలకాలు'],
            'number' => '2.2',
            'abbr' => 'BDN.NAT',
        ]);

        return [
            'parent_terms' => $parentTerms,
            'family_relations' => $familyRelations,
            'body_parts' => $bodyParts,
            'natural_elements' => $naturalElements,
        ];
    }

    /**
     * @return array<string, LexSource>
     */
    protected function seedSources(LexLexicon $lexicon): array
    {
        $withPage = LexSource::factory()->create([
            'lexicon_id' => $lexicon->id,
            'code' => 'POK',
            'display' => 'Pokorny, Indogermanisches etymologisches Wörterbuch',
        ]);

        $withoutPage = LexSource::factory()->create([
            'lexicon_id' => $lexicon->id,
            'code' => 'ANON',
            'display' => 'Anonymous fixture glossary',
        ]);

        return ['with_page' => $withPage, 'without_page' => $withoutPage];
    }

    /**
     * @return array<string, LexPartOfSpeech>
     */
    protected function seedPartsOfSpeech(LexLexicon $lexicon): array
    {
        $noun = LexPartOfSpeech::factory()->create([
            'lexicon_id' => $lexicon->id,
            'code' => 'n',
            'display' => ['en' => 'noun', 'es' => 'sustantivo', 'te' => 'నామవాచకం'],
        ]);

        $verb = LexPartOfSpeech::factory()->create([
            'lexicon_id' => $lexicon->id,
            'code' => 'v',
            'display' => ['en' => 'verb', 'es' => 'verbo', 'te' => 'క్రియ'],
        ]);

        return ['noun' => $noun, 'verb' => $verb];
    }

    /**
     * @param  array<string, LexLanguage>  $languages
     * @param  array<string, LexSemanticField>  $fields
     * @param  array<string, LexSource>  $sources
     * @param  array<string, LexPartOfSpeech>  $partsOfSpeech
     * @return array<string, LexEtyma>
     */
    protected function seedEtymaAndReflexes(
        LexLexicon $lexicon,
        array $languages,
        array $fields,
        array $sources,
        array $partsOfSpeech,
    ): array {
        ['oe' => $oe, 'grc' => $grc, 'heb' => $heb, 'te' => $te] = $languages;

        // --- Etymon 1: *ph₂tḕr "father" -- the etymon with 6+ reflexes ---
        $father = LexEtyma::factory()->create([
            'lexicon_id' => $lexicon->id,
            'entry' => 'ph₂tḕr',
            'order' => 1,
            'gloss' => ['en' => 'father', 'es' => 'padre', 'te' => 'తండ్రి'],
        ]);
        $father->semantic_fields()->attach($fields['parent_terms']->id);

        $feder = LexReflex::factory()->create([
            'language_id' => $oe->id,
            'lang_attribute' => 'ang',
            'gloss' => ['en' => 'father', 'es' => 'padre', 'te' => 'తండ్రి'],
            'entries' => [['text' => 'fæder']],
        ]);
        $feder->parts_of_speech()->create(['text' => 'n.', 'order' => 1]);
        $feder->sources()->attach($sources['with_page']->id, [
            'page_number' => '134',
            'original_text' => 'cited in Pokorny p. 134',
        ]);

        $federas = LexReflex::factory()->create([
            'language_id' => $oe->id,
            'lang_attribute' => 'ang',
            'gloss' => ['en' => 'fathers'], // en only, deliberately
            'entries' => [['text' => 'fæderas']],
        ]);

        $pater = LexReflex::factory()->create([
            'language_id' => $grc->id,
            'lang_attribute' => 'grc',
            'gloss' => ['en' => 'father', 'es' => 'padre', 'te' => 'తండ్రి'],
            'entries' => [['text' => 'πατήρ']],
        ]);

        $patera = LexReflex::factory()->create([
            'language_id' => $grc->id,
            'lang_attribute' => 'grc',
            'gloss' => ['en' => 'father (accusative)'], // en only, deliberately
            'entries' => [['text' => 'πατέρα']],
        ]);

        $av = LexReflex::factory()->create([
            'language_id' => $heb->id,
            'lang_attribute' => 'he',
            'gloss' => ['en' => 'father', 'es' => 'padre', 'te' => 'తండ్రి'],
            'entries' => [['text' => 'אָב']],
        ]);

        $tandri = LexReflex::factory()->create([
            'language_id' => $te->id,
            'lang_attribute' => 'te',
            'gloss' => ['en' => 'father', 'es' => 'padre', 'te' => 'తండ్రి'],
            'entries' => [['text' => 'తండ్రి']],
        ]);

        $father->reflexes()->attach([$feder->id, $federas->id, $pater->id, $patera->id, $av->id, $tandri->id]);

        // --- Etymon 2: *mātér- "mother" ---
        $mother = LexEtyma::factory()->create([
            'lexicon_id' => $lexicon->id,
            'entry' => 'mātér',
            'order' => 2,
            'gloss' => ['en' => 'mother', 'es' => 'madre', 'te' => 'అమ్మ'],
        ]);
        $mother->semantic_fields()->attach([$fields['parent_terms']->id, $fields['family_relations']->id]);

        $modor = LexReflex::factory()->create([
            'language_id' => $oe->id,
            'lang_attribute' => 'ang',
            'gloss' => ['en' => 'mother', 'es' => 'madre', 'te' => 'అమ్మ'],
            'entries' => [['text' => 'mōdor']],
        ]);
        $mother->reflexes()->attach($modor->id);

        // --- Etymon 3: *dʰǵʰemon- "earth" ---
        $earth = LexEtyma::factory()->create([
            'lexicon_id' => $lexicon->id,
            'entry' => 'dʰǵʰemon',
            'order' => 3,
            'gloss' => ['en' => 'earth', 'es' => 'tierra', 'te' => 'భూమి'],
        ]);
        $earth->semantic_fields()->attach($fields['natural_elements']->id);

        // ge: a reflex with two etyma (disputed etymology -- folk-linked to
        // both "mother" and "earth", as in "Mother Earth").
        $ge = LexReflex::factory()->create([
            'language_id' => $grc->id,
            'lang_attribute' => 'grc',
            'gloss' => ['en' => 'earth, land', 'es' => 'tierra', 'te' => 'భూమి'],
            'entries' => [['text' => 'γῆ']],
        ]);
        $mother->reflexes()->attach($ge->id);
        $earth->reflexes()->attach($ge->id);

        $bhoomi = LexReflex::factory()->create([
            'language_id' => $te->id,
            'lang_attribute' => 'te',
            'gloss' => ['en' => 'earth', 'es' => 'tierra', 'te' => 'భూమి'],
            'entries' => [['text' => 'భూమి']],
        ]);
        $earth->reflexes()->attach($bhoomi->id);

        // --- Etymon 4: *wódr̥ "water" ---
        $water = LexEtyma::factory()->create([
            'lexicon_id' => $lexicon->id,
            'entry' => 'wódr̥',
            'order' => 4,
            'gloss' => ['en' => 'water', 'es' => 'agua', 'te' => 'నీరు'],
        ]);
        $water->semantic_fields()->attach($fields['natural_elements']->id);

        // Deliberately spelled with ǣ (rather than the historically
        // attested æ) so the Old English script-coverage case exercises
        // both diacritics used in the ADR's fixture requirements.
        $water_oe = LexReflex::factory()->create([
            'language_id' => $oe->id,
            'lang_attribute' => 'ang',
            'gloss' => ['en' => 'water', 'es' => 'agua', 'te' => 'నీరు'],
            'entries' => [['text' => 'wǣter']],
        ]);
        $water_oe->extra_data()->create([
            'key' => 'dialect',
            'value' => ['en' => 'West Saxon'],
        ]);

        $mayim = LexReflex::factory()->create([
            'language_id' => $heb->id,
            'lang_attribute' => 'he',
            'gloss' => ['en' => 'water', 'es' => 'agua', 'te' => 'నీరు'],
            'entries' => [['text' => 'מַיִם']],
        ]);

        $water->reflexes()->attach([$water_oe->id, $mayim->id]);

        // --- Etymon 5: *ǵónu "knee" ---
        $knee = LexEtyma::factory()->create([
            'lexicon_id' => $lexicon->id,
            'entry' => 'ǵónu',
            'order' => 5,
            'gloss' => ['en' => 'knee', 'es' => 'rodilla', 'te' => 'మోకాలు'],
        ]);
        $knee->semantic_fields()->attach($fields['body_parts']->id);

        $gonu = LexReflex::factory()->create([
            'language_id' => $grc->id,
            'lang_attribute' => 'grc',
            'gloss' => ['en' => 'knee', 'es' => 'rodilla', 'te' => 'మోకాలు'],
            'entries' => [['text' => 'γόνυ']],
        ]);
        $knee->reflexes()->attach($gonu->id);

        // --- Etymon 6: *kʷis "who" -- the etymon with no reflexes ---
        LexEtyma::factory()->create([
            'lexicon_id' => $lexicon->id,
            'entry' => 'kʷis',
            'order' => 6,
            'gloss' => ['en' => 'who', 'es' => 'quién', 'te' => 'ఎవరు'],
        ]);

        // --- Etymon 7: *h₁ésti "is, exists" ---
        $isEtymon = LexEtyma::factory()->create([
            'lexicon_id' => $lexicon->id,
            'entry' => 'h₁ésti',
            'order' => 7,
            'gloss' => ['en' => 'is, exists', 'es' => 'es, existe', 'te' => 'ఉంది'],
        ]);

        $undi = LexReflex::factory()->create([
            'language_id' => $te->id,
            'lang_attribute' => 'te',
            'gloss' => ['en' => 'is'], // en only, deliberately
            'entries' => [['text' => 'ఉంది']],
        ]);
        $undi->parts_of_speech()->create(['text' => 'v.', 'order' => 1]);

        // Old English "biþ" -- deliberately includes þ for script coverage.
        $bith = LexReflex::factory()->create([
            'language_id' => $oe->id,
            'lang_attribute' => 'ang',
            'gloss' => ['en' => 'is'], // en only, deliberately
            'entries' => [['text' => 'biþ']],
        ]);
        $bith->parts_of_speech()->create(['text' => 'v.', 'order' => 1]);

        $isEtymon->reflexes()->attach([$undi->id, $bith->id]);

        // --- Etymon 8: *wr̥dʰom "word" ---
        $wordEtymon = LexEtyma::factory()->create([
            'lexicon_id' => $lexicon->id,
            'entry' => 'wr̥dʰom',
            'order' => 8,
            'gloss' => ['en' => 'word', 'es' => 'palabra', 'te' => 'పదం'],
        ]);

        $word_oe = LexReflex::factory()->create([
            'language_id' => $oe->id,
            'lang_attribute' => 'ang',
            'gloss' => ['en' => 'word', 'es' => 'palabra', 'te' => 'పదం'],
            'entries' => [['text' => 'word']],
        ]);
        $word_oe->sources()->attach($sources['without_page']->id, [
            'page_number' => null,
            'original_text' => 'from an anonymous fixture glossary',
        ]);

        $epos = LexReflex::factory()->create([
            'language_id' => $grc->id,
            'lang_attribute' => 'grc',
            'gloss' => ['en' => 'word, speech', 'es' => 'palabra', 'te' => 'పదం'],
            'entries' => [['text' => 'ἔπος']],
        ]);

        $wordEtymon->reflexes()->attach([$word_oe->id, $epos->id]);

        return [
            'father' => $father,
            'mother' => $mother,
            'earth' => $earth,
            'water' => $water,
            'knee' => $knee,
            'is' => $isEtymon,
            'word' => $wordEtymon,
            // named reflexes, for the smoke and content tests
            'father_oe_reflex' => $feder,
            'father_heb_reflex' => $av,
            'father_te_reflex' => $tandri,
        ];
    }

    /**
     * One EIEOL series, with one lesson having one grammar section and one
     * glossed text with two glosses, and a head word linked to an etymon.
     *
     * @param  array<string, LexEtyma>  $etyma
     */
    protected function seedEieol(array $etyma): void
    {
        $language = EieolLanguage::factory()->create([
            'language' => 'Fixture EIEOL Language',
            'lang_attribute' => 'en',
        ]);

        $series = EieolSeries::factory()->create([
            'title' => 'Fixture Series',
            'slug' => 'fixtureeieol',
            'order' => 1,
            'published' => 1,
        ]);

        $lesson = EieolLesson::factory()->create([
            'series_id' => $series->id,
            'language_id' => $language->id,
            'title' => 'Lesson One',
            'order' => 1,
        ]);

        EieolGrammar::factory()->create([
            'lesson_id' => $lesson->id,
            'title' => 'Grammar Note One',
            'order' => 1,
            'section_number' => '1',
        ]);

        $glossedText = EieolGlossedText::factory()->create([
            'lesson_id' => $lesson->id,
            'glossed_text' => '<p>fæder biþ here.</p>',
            'order' => 1,
        ]);

        // Note: EieolGloss (like EieolGrammar, EieolGlossedText and
        // EieolHeadWord) declares no $guarded/$fillable, so it must be
        // created through its factory (which Laravel runs inside
        // Model::unguarded()) rather than via a relation's create()/
        // createMany(), which would otherwise throw MassAssignmentException.
        EieolGloss::factory()->create([
            'glossed_text_id' => $glossedText->id,
            'language_id' => $language->id,
            'surface_form' => 'fæder',
            'contextual_gloss' => 'father',
            'order' => 1,
        ]);

        EieolGloss::factory()->create([
            'glossed_text_id' => $glossedText->id,
            'language_id' => $language->id,
            'surface_form' => 'biþ',
            'contextual_gloss' => 'is',
            'order' => 2,
        ]);

        EieolHeadWord::factory()->create([
            'word' => 'fæder',
            'definition' => 'father',
            'language_id' => $language->id,
            'etyma_id' => $etyma['father']->id,
            'keywords' => 'father,parent',
        ]);
    }

    /**
     * The CMS pages the public controllers need to render '/', '/books',
     * '/lex' and '/eieol' without a missing-page 500, plus one Book with one
     * section.
     */
    protected function seedCms(): void
    {
        Page::factory()->create([
            'slug' => 'index',
            'name' => ['en' => 'Home'],
            'content' => ['en' => '<p>Welcome to the fixture site.</p>', 'es' => '<p>Bienvenido.</p>', 'te' => '<p>స్వాగతం.</p>'],
        ]);

        Page::factory()->create([
            'slug' => 'books',
            'name' => ['en' => 'Books'],
            'content' => ['en' => '<p>Fixture books index.</p>'],
        ]);

        Page::factory()->create([
            'slug' => 'lex',
            'name' => ['en' => 'Lex'],
            'content' => ['en' => '<p>Fixture legacy IELEX landing page.</p>'],
        ]);

        Page::factory()->create([
            'slug' => 'eieol',
            'name' => ['en' => 'EIEOL'],
            'content' => ['en' => '<p>Fixture EIEOL landing page.</p>'],
        ]);

        $book = Book::factory()->create([
            'name' => 'Fixture Book',
            'slug' => 'fixturebook',
        ]);

        $book->sections()->create([
            'name' => 'Introduction',
            'slug' => 'intro',
            'order' => 1,
            'content' => '<p>Fixture book section content.</p>',
        ]);
    }

    protected function seedUser(): void
    {
        $user = User::factory()->create([
            'name' => 'Fixture Site Manager',
            'username' => 'fixture-site-manager',
            'email' => 'fixture-site-manager@fixturelex.test',
        ]);

        $user->assignRole('Site Manager');
    }
}
