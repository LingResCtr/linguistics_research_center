<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\EieolSeries;
use App\Models\LexEtyma;
use App\Models\LexLanguage;
use App\Models\LexLexicon;
use App\Models\LexReflex;
use App\Models\LexSemanticField;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\FixtureLexiconTestHelpers;
use Tests\TestCase;

/**
 * Smoke coverage for every public route family (ADR 0005 §3-4): each route
 * is asserted to return 200 using ids/slugs from the "fixturelex" fixture,
 * except where the route's own correct behaviour is a redirect, in which
 * case that redirect is asserted instead with a comment explaining why.
 */
class PublicRoutesSmokeTest extends TestCase
{
    use FixtureLexiconTestHelpers;
    use RefreshDatabase;

    protected Book $book;

    protected EieolSeries $series;

    protected LexEtyma $fatherEtymon;

    protected LexReflex $fatherReflex;

    protected LexLanguage $oldEnglish;

    protected LexSemanticField $parentTerms;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedFixtureLexicon();

        $this->book = Book::where('slug', 'fixturebook')->firstOrFail();
        $this->series = EieolSeries::where('slug', 'fixtureeieol')->firstOrFail();
        $this->fatherEtymon = $this->fixtureEtymon('ph₂tḕr');
        $this->fatherReflex = $this->fixtureReflexByEntry('fæder');
        $this->oldEnglish = $this->fixtureLanguage('OE');
        $this->parentTerms = $this->fixtureSemanticField('KIN.PAR');
    }

    public function test_home_page(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_books_index(): void
    {
        $this->get('/books')->assertOk();
    }

    public function test_book_home_redirects_to_its_first_section(): void
    {
        // PublicBookController::bookHome() always redirects to the first
        // section; a bare book slug never renders content of its own, so
        // 302 (not 200) is the correct behaviour here.
        $section = $this->book->sections->first();
        $this->get("/books/{$this->book->slug}")
            ->assertRedirect("/books/{$this->book->slug}/{$section->slug}");
    }

    public function test_book_section(): void
    {
        $section = $this->book->sections->first();
        $this->get("/books/{$this->book->slug}/{$section->slug}")->assertOk();
    }

    public function test_eieol_index(): void
    {
        $this->get('/eieol')->assertOk();
    }

    public function test_eieol_series_redirects_to_its_first_lesson(): void
    {
        // PublicEieolController::eieol_first_lesson() always redirects to
        // the lowest-order lesson, so 302 (not 200) is correct here too.
        $this->get("/eieol/{$this->series->slug}")
            ->assertRedirect("/eieol/{$this->series->slug}/1");
    }

    public function test_eieol_lesson(): void
    {
        $this->get("/eieol/{$this->series->slug}/1")->assertOk();
    }

    public function test_eieol_toc(): void
    {
        $this->get("/eieol_toc/{$this->series->slug}")->assertOk();
    }

    public function test_lex_landing_page(): void
    {
        $this->get('/lex')->assertOk();
    }

    public function test_lexicon_home(): void
    {
        $this->get('/lexicon/fixturelex')->assertOk();
    }

    public function test_lexicon_protolanguage_home(): void
    {
        $this->get('/lexicon/fixturelex/language/protolanguage')->assertOk();
    }

    public function test_lexicon_language_home(): void
    {
        $this->get("/lexicon/fixturelex/language/{$this->oldEnglish->id}")->assertOk();
    }

    public function test_lexicon_etymon(): void
    {
        $this->get("/lexicon/fixturelex/etymon/{$this->fatherEtymon->id}")->assertOk();
    }

    public function test_lexicon_word(): void
    {
        $this->get("/lexicon/fixturelex/word/{$this->fatherReflex->id}")->assertOk();
    }

    public function test_lexicon_field(): void
    {
        $this->get("/lexicon/fixturelex/field/{$this->parentTerms->id}")->assertOk();
    }

    public function test_lexicon_data_page(): void
    {
        $this->get('/lexicon/fixturelex/data')->assertOk();
    }

    public function test_lexicon_ajax_data(): void
    {
        // PublicLexiconController::ajaxData() reads a DataTables-shaped
        // query string: draw/start/length, one entry per column (with a
        // per-column search), a top-level search, and an order clause.
        $columns = ['root', 'meaning', 'semantic_tag', 'etymon', 'language', 'part_of_speech'];

        $query = [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => '', 'regex' => false],
            'order' => [['name' => 'meaning', 'dir' => 'asc']],
            'columns' => array_map(
                fn (string $name) => ['name' => $name, 'search' => ['value' => '', 'regex' => false]],
                $columns
            ),
        ];

        $this->get('/api/v1/lexicon/fixturelex/data?'.http_build_query($query))->assertOk();
    }

    public function test_robots_txt(): void
    {
        $this->get('/robots.txt')->assertOk();
    }

    public function test_login_page(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_admin_login_page(): void
    {
        $this->get('/admin/login')->assertOk();
    }

    /**
     * The etymon and word layouts used to declare named PHP functions inside
     *
     * @php blocks, so rendering the same layout twice in one PHP process died
     * with "Cannot redeclare function". Sorting now lives in
     * App\Services\Lexicon\SidebarSorter; this guards against a regression.
     */
    public function test_lexicon_layouts_can_be_rendered_twice_in_one_process(): void
    {
        $lexicon = LexLexicon::where('slug', 'fixturelex')->firstOrFail();
        $etymon = LexEtyma::where('lexicon_id', $lexicon->id)->firstOrFail();
        $reflex = LexReflex::whereHas('etyma', fn ($q) => $q->where('lexicon_id', $lexicon->id))->firstOrFail();

        $this->get("/lexicon/fixturelex/etymon/{$etymon->id}")->assertOk();
        $this->get("/lexicon/fixturelex/etymon/{$etymon->id}")->assertOk();
        $this->get("/lexicon/fixturelex/word/{$reflex->id}")->assertOk();
        $this->get("/lexicon/fixturelex/word/{$reflex->id}")->assertOk();
    }
}
