<?php

namespace Database\Seeders;

use App\Models\Chemist;
use App\Models\SynthesisStage;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SynthesisDatabaseSeeder extends Seeder
{
    private const MEDIA_BASE_URL = 'http://localhost:9000/synthesis-app-media';

    /** Seed a stable demo dataset without overwriting the user's own records. */
    public function run(): void
    {
        $now = Carbon::now();
        $chemist = Chemist::query()->firstOrCreate(['email' => 'saryglar@bmstu.ru'], ['full_name' => 'Начын Сарыглар']);
        $curie = Chemist::query()->firstOrCreate(['email' => 'curie@lab.com'], ['full_name' => 'Мария Кюри']);

        $publishedOne = $this->stage($chemist, [
            'name' => 'Восстановление нитробензола',
            'brief_description' => 'Каталитическое гидрирование нитробензола до 4-аминофенола в слабокислой среде при интенсивном перемешивании.',
            'lifecycle_status' => 'published', 'yield_percentage' => 82.50, 'reaction_temperature_celsius' => 80,
            'created_at' => $now->copy()->subDays(2), 'updated_at' => $now->copy()->subDay(),
            'media_image_url' => self::MEDIA_BASE_URL . '/nitro_reduction.jpg',
            'media_video_url' => self::MEDIA_BASE_URL . '/nitro_reduction.mp4',
        ]);
        $publishedTwo = $this->stage($chemist, [
            'name' => 'Ацилирование 4-аминофенола',
            'brief_description' => 'Реакция 4-аминофенола с уксусным ангидридом при контролируемой температуре.',
            'lifecycle_status' => 'published', 'yield_percentage' => 91.00, 'reaction_temperature_celsius' => 100,
            'created_at' => $now->copy()->subHours(10), 'updated_at' => $now->copy()->subHours(5),
            'media_image_url' => self::MEDIA_BASE_URL . '/acylation_process.jpg',
            'media_video_url' => self::MEDIA_BASE_URL . '/acylation_process.mp4',
        ]);
        $this->stage($chemist, [
            'name' => 'Перекристаллизация парацетамола',
            'brief_description' => 'Очистка сырца парацетамола из водного раствора при контролируемом охлаждении.',
            'lifecycle_status' => 'draft', 'yield_percentage' => 76.40, 'reaction_temperature_celsius' => 25,
            'created_at' => $now->copy()->subHours(2), 'updated_at' => null,
            'media_image_url' => self::MEDIA_BASE_URL . '/default2.jpg',
            'media_video_url' => self::MEDIA_BASE_URL . '/phenol_nitration.mp4',
        ]);
        $this->stage($curie, [
            'name' => 'Нитрование фенола',
            'brief_description' => 'Неэффективная стадия сохранена только для демонстрации soft delete.',
            'lifecycle_status' => 'deleted', 'yield_percentage' => 45.00, 'reaction_temperature_celsius' => 15,
            'created_at' => $now->copy()->subMonth(), 'updated_at' => $now->copy()->subMonth(),
            'media_image_url' => self::MEDIA_BASE_URL . '/phenol_nitration.jpg',
            'media_video_url' => self::MEDIA_BASE_URL . '/phenol_nitration.mp4',
        ]);

        foreach ([[$publishedOne, $chemist], [$publishedOne, $curie], [$publishedTwo, $curie]] as [$stage, $likingChemist]) {
            DB::table('synthesis_stage_likes')->updateOrInsert(
                ['synthesis_stage_id' => $stage->id, 'chemist_id' => $likingChemist->id],
                ['created_at' => $now, 'updated_at' => $now]
            );
        }
    }

    private function stage(Chemist $chemist, array $attributes): SynthesisStage
    {
        $stage = SynthesisStage::query()->updateOrCreate(
            ['name' => $attributes['name'], 'chemist_id' => $chemist->id],
            $attributes
        );
        return $stage;
    }
}
