<?php

namespace Tests\Feature;

use App\Models\Chemist;
use App\Models\SynthesisStage;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SynthesisStageFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_feed_returns_only_published_stages_and_moves_to_next_one(): void
    {
        $chemist = Chemist::create(['chemist_full_name' => 'Начын Сарыглар', 'chemist_email' => 'saryglar@bmstu.ru']);
        $first = $this->stage($chemist, 'Первый этап', 'published');
        $second = $this->stage($chemist, 'Второй этап', 'published');
        $this->stage($chemist, 'Удалённый этап', 'deleted');

        $this->get('/synthesis-stages/feed')
            ->assertOk()
            ->assertSeeText('Первый этап')
            ->assertDontSeeText('Удалённый этап');

        $this->get("/synthesis-stages/feed/{$first->id}?next=true")
            ->assertOk()
            ->assertSeeText('Второй этап');

        $this->get("/synthesis-stages/feed/{$second->id}")->assertOk();
    }

    public function test_current_chemist_can_create_and_publish_a_draft(): void
    {
        $this->post('/synthesis-stages/draft', ['stage_name' => 'Новый этап'])
            ->assertRedirect('/synthesis-stages/draft');

        $draft = SynthesisStage::query()->where('stage_name', 'Новый этап')->firstOrFail();

        $this->post("/synthesis-stages/{$draft->id}/publish", [
            'stage_name' => 'Новый этап',
            'stage_brief_description' => 'Проверочное описание этапа.',
            'stage_yield_percentage' => 88.5,
            'reaction_temperature_celsius' => 72,
        ])->assertRedirect('/synthesis-stages');

        $this->assertDatabaseHas('synthesis_stages', [
            'id' => $draft->id,
            'stage_lifecycle_status' => 'published',
            'reaction_temperature_celsius' => 72,
        ]);
    }

    public function test_current_chemist_can_logically_delete_only_their_stage(): void
    {
        $owner = Chemist::create(['chemist_full_name' => 'Начын Сарыглар', 'chemist_email' => 'saryglar@bmstu.ru']);
        $stage = $this->stage($owner, 'Этап для удаления', 'published');

        $this->post("/synthesis-stages/{$stage->id}/delete")
            ->assertRedirect('/synthesis-stages');

        $this->assertDatabaseHas('synthesis_stages', [
            'id' => $stage->id,
            'stage_lifecycle_status' => 'deleted',
        ]);
        $this->get("/synthesis-stages/feed/{$stage->id}")->assertNotFound();
    }

    private function stage(Chemist $chemist, string $name, string $status): SynthesisStage
    {
        return SynthesisStage::create([
            'stage_name' => $name,
            'stage_brief_description' => 'Описание этапа.',
            'stage_lifecycle_status' => $status,
            'stage_yield_percentage' => 80,
            'reaction_temperature_celsius' => 60,
            'stage_created_at' => Carbon::now(),
            'stage_formed_at' => $status === 'published' ? Carbon::now() : null,
            'creator_chemist_id' => $chemist->id,
        ]);
    }
}
