<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\EieolSeries;
use App\Models\LexEtyma;
use App\Models\LexLanguage;
use App\Models\LexReflex;
use App\Models\LexSemanticField;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Tests\Feature\Concerns\FixtureLexiconTestHelpers;
use Tests\TestCase;

/**
 * Smoke coverage for every public route family (ADR 0005 §3-4): each route
 * is asserted to return 200 using ids/slugs from the "fixturelex" fixture,
 * except where the route's own correct behaviour is a redirect, in which
 * case that redirect is asserted instead with a comment explaining why.
 *
 * FIXME: resources/views/lexicon/layout-etym.blade.php and layout-dict.blade.php
 * each declare a named top-level PHP function inside an @php block
 * (sortSidebarItemsByEntry() / sortSidebarItemsByEntries()). Laravel's view
 * engine `require`s (not `require_once`s) the compiled view file, so a
 * second render of a view extending the same layout, in the same PHP
 * process, hits an uncatchable "Cannot redeclare function" fatal error and
 * kills the whole process -- confirmed by reproducing it with two plain
 * requests to the same etymon route in one test. That means any two of
 * {etymon, field, language/protolanguage} (layout-etym) or any two of
 * {word, language/{id}} (layout-dict) rendered anywhere in one `php artisan
 * test` run will crash the entire suite, not just fail one assertion. This
 * is a real bug in those four templates (production Apache workers that
 * serve more than one such page will hit it too), not a fixture problem, so
 * per the task brief every test below that renders one of these two
 * layouts runs in its own process via #[RunInSeparateProcess] rather than
 * being skipped.
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

    #[RunInSeparateProcess]
    public function test_lexicon_protolanguage_home(): void
    {
        $this->get('/lexicon/fixturelex/language/protolanguage')->assertOk();
    }

    #[RunInSeparateProcess]
    public function test_lexicon_language_home(): void
    {
        $this->get("/lexicon/fixturelex/language/{$this->oldEnglish->id}")->assertOk();
    }

    #[RunInSeparateProcess]
    public function test_lexicon_etymon(): void
    {
        $this->get("/lexicon/fixturelex/etymon/{$this->fatherEtymon->id}")->assertOk();
    }

    #[RunInSeparateProcess]
    public function test_lexicon_word(): void
    {
        $this->get("/lexicon/fixturelex/word/{$this->fatherReflex->id}")->assertOk();
    }

    #[RunInSeparateProcess]
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
}
