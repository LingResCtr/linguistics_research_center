<?php

namespace Tests\Feature;

use App\Models\EieolElement;
use App\Models\EieolGloss;
use App\Models\EieolGlossedText;
use App\Models\EieolHeadWord;
use App\Models\EieolLanguage;
use App\Models\EieolLesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The lesson editor stores a detached gloss with glossed_text_id NULL and a
 * head word without an etymon with etyma_id NULL, as production allows
 * (migration 2026_10_13_000000).
 */
class EieolDetachedGlossAndHeadWordTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private EieolLanguage $language;

    private EieolGlossedText $glossedText;

    private EieolGloss $gloss;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::factory()->create();
        $this->manager->assignRole('EIEOL Manager');

        $this->language = EieolLanguage::factory()->create();
        $lesson = EieolLesson::factory()->create(['language_id' => $this->language->id]);
        $this->glossedText = EieolGlossedText::factory()->create(['lesson_id' => $lesson->id]);
        $this->gloss = EieolGloss::factory()->create([
            'glossed_text_id' => $this->glossedText->id,
            'language_id' => $this->language->id,
            'order' => 10,
        ]);

        $element = new EieolElement;
        $element->gloss_id = $this->gloss->id;
        $element->part_of_speech = 'noun';
        $element->head_word_id = EieolHeadWord::factory()->create(['language_id' => $this->language->id])->id;
        $element->order = 1;
        $element->save();
    }

    public function test_both_columns_are_nullable(): void
    {
        $this->assertTrue($this->column('eieol_gloss', 'glossed_text_id')['nullable']);
        $this->assertTrue($this->column('eieol_head_word', 'etyma_id')['nullable']);
    }

    public function test_detaching_a_gloss_keeps_it_without_a_glossed_text(): void
    {
        $this->actingAs($this->manager)->delete('/admin2/eieol_glossed_text_gloss/'.$this->gloss->id)->assertOk();

        $gloss = $this->gloss->fresh();
        $this->assertNull($gloss->glossed_text_id);
        $this->assertNull($gloss->order);
    }

    public function test_deleting_a_glossed_text_keeps_its_glosses_detached(): void
    {
        $this->actingAs($this->manager)->delete('/admin2/eieol_glossed_text/'.$this->glossedText->id)->assertOk();

        $this->assertNull(EieolGlossedText::find($this->glossedText->id));
        $this->assertNull($this->gloss->fresh()->glossed_text_id);
    }

    public function test_copying_a_gloss_attaches_the_copy_to_the_target_text(): void
    {
        $target = EieolGlossedText::factory()->create(['lesson_id' => $this->glossedText->lesson_id, 'order' => 2]);

        $response = $this->actingAs($this->manager)->post('/admin2/eieol_glossed_text_gloss/copy_gloss', [
            'existing_gloss_id' => $this->gloss->id,
            'glossed_text_id' => $target->id,
        ])->assertOk()->assertJson(['success' => true]);

        $copy = EieolGloss::findOrFail($response->json('gloss_id'));
        $this->assertSame($target->id, $copy->glossed_text_id);
        $this->assertCount(1, $copy->elements);
        $this->assertSame($this->glossedText->id, $this->gloss->fresh()->glossed_text_id);
    }

    public function test_head_word_without_an_etymon_is_saved_with_no_etymon(): void
    {
        $this->actingAs($this->manager)->post('/admin2/eieol_head_word', [
            'word' => '<ager>',
            'definition' => 'field',
            'keywords' => 'field,land',
            'language_id' => $this->language->id,
            'etyma_id' => '0',
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertNull(EieolHeadWord::where('word', '<ager>')->firstOrFail()->etyma_id);
    }

    public function test_head_word_update_can_remove_its_etymon(): void
    {
        $headWord = EieolHeadWord::factory()->create(['language_id' => $this->language->id, 'word' => '<arvum>']);
        $this->assertNotNull($headWord->etyma_id);

        $this->actingAs($this->manager)->put('/admin2/eieol_head_word/'.$headWord->id, [
            'word' => $headWord->word,
            'definition' => $headWord->definition,
            'keywords' => $headWord->keywords,
            'language_id' => $this->language->id,
            'etyma_id' => '0',
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertNull($headWord->fresh()->etyma_id);
    }

    /**
     * @return array<string, mixed>
     */
    private function column(string $table, string $name): array
    {
        return collect(Schema::getColumns($table))->firstWhere('name', $name);
    }
}
