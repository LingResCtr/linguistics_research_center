<?php

namespace App\Http\Controllers;

use App\Http\Requests\LexiconDataRequest;
use App\Models\LexEtyma;
use App\Models\LexLanguage;
use App\Models\LexLexicon;
use App\Models\LexReflex;
use App\Models\LexSemanticField;
use App\Models\Page;
use App\Services\Lexicon\DataTableQuery;
use Illuminate\Database\QueryException;
use InvalidArgumentException;
use Session;

class PublicLexiconController extends Controller
{
    public function index($lexicon_slug)
    {
        $lex = $this->getLexicon($lexicon_slug);

        return view('lexicon/lex_home', [
            'lexicon' => $lex,
            'selected_sidebar' => 'language',
        ]);
    }

    public function switch_lang($lexicon_slug, $lang)
    {
        Session::put('viewer_lang_code', $lang);
        if (request()->input('return_to')) {
            return redirect(request()->input('return_to'));
        }

        return redirect('/lexicon/'.$lexicon_slug);
    }

    public function protolanguage_home($lexicon_slug)
    {
        $lex = $this->getLexicon($lexicon_slug);

        return view('lexicon/lex_protolanguage_home', [
            'lexicon' => $lex,
            'protolang' => true,
            'selected_sidebar' => 'headword',
        ]);
    }

    public function etymon($lexicon_slug, $etymon_id)
    {
        $lex = $this->getLexicon($lexicon_slug);
        $etymon = LexEtyma::with([
            'reflexes',
            'reflexes.language',
        ])
            ->where('lexicon_id', $lex->id)
            ->findOrFail($etymon_id);

        return view('lexicon/lex_etymon', [
            'lexicon' => $lex,
            'etymon' => $etymon,
            'selected_sidebar' => 'headword',
            'selected_sidebar_id' => $etymon->id,
        ]);
    }

    public function field($lexicon_slug, $field_id)
    {
        $lex = $this->getLexicon($lexicon_slug);
        $field = LexSemanticField::with([
            'etyma',
            'etyma.reflexes',
            'etyma.reflexes.language',
        ])
            ->whereHas('semantic_category', function ($query) use ($lex) {
                $query->where('lexicon_id', $lex->id);
            })
            ->findOrFail($field_id);

        return view('lexicon/lex_field', [
            'lexicon' => $lex,
            'field' => $field,
            'selected_sidebar' => 'category',
            'selected_sidebar_id' => $field->id,
        ]);
    }

    public function word_home($lexicon_slug, $word_id)
    {
        $lex = $this->getLexicon($lexicon_slug);
        $word = LexReflex::with([
            'etyma',
            'etyma.reflexes',
            'etyma.reflexes.language',
            'sources',
        ])
            ->whereHas('language.language_sub_family.language_family', function ($query) use ($lex) {
                $query->where('lexicon_id', $lex->id);
            })
            ->findOrFail($word_id);
        $language = $word->language;

        return view('lexicon/lex_word', [
            'lexicon' => $lex,
            'language' => $language,
            'word' => $word,
            'selected_sidebar' => 'headword',
            'selected_sidebar_id' => $word->id,
        ]);
    }

    public function lang_home($lexicon_slug, $lang_id)
    {
        $lex = $this->getLexicon($lexicon_slug);
        $language = LexLanguage::query()
            ->whereHas('language_sub_family.language_family', function ($query) use ($lex) {
                $query->where('lexicon_id', $lex->id);
            })
            ->findOrFail($lang_id);

        return view('lexicon/lex_language', [
            'lexicon' => $lex,
            'language' => $language,
            'selected_sidebar' => 'headword',
        ]);
    }

    public function page($lexicon_slug, $page_slug_fragment)
    {
        $lex = $this->getLexicon($lexicon_slug);
        $page_url = 'lexicon/'.$lexicon_slug.'/page/'.$page_slug_fragment;
        $page = Page::where('slug', $page_url)->firstOrFail();

        return view('lexicon/lex_page', [
            'lexicon' => $lex,
            'page' => $page,
            'selected_sidebar' => 'headword',
        ]);
    }

    public function data($lexicon_slug)
    {
        $lex = LexLexicon::where('slug', $lexicon_slug)->firstOrFail();

        return view('lexicon/lex_data', [
            'lexicon' => $lex,
        ]);
    }

    protected function getLexicon($lexicon_slug)
    {
        return LexLexicon::where('slug', $lexicon_slug)
            ->with([
                'language_families',
                'language_families.language_sub_families',
                'language_families.language_sub_families.languages',
                'semantic_categories',
                'semantic_categories.semantic_fields',
            ])->firstOrFail();
    }

    public function ajaxData(LexiconDataRequest $request, $lex_slug)
    {
        $lex = LexLexicon::where('slug', $lex_slug)->firstOrFail();
        $query = new DataTableQuery($lex, Session::get('viewer_lang_code', 'en'));

        try {
            return response()->json($query->run($request->validated()));
        } catch (InvalidArgumentException $e) {
            return response()->json(['draw' => (int) $request->input('draw', 0), 'error' => $e->getMessage()], 422);
        } catch (QueryException) {
            return response()->json([
                'draw' => (int) $request->input('draw', 0),
                'error' => 'The search expression could not be evaluated.',
            ], 422);
        }
    }
}
