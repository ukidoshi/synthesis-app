<?php

namespace Database\Seeders;

use App\Models\Chemist;
use App\Models\SynthesisStage;
use Carbon\Carbon;
use Database\Factories\ChemistFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SynthesisDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $chemist = Chemist::query()->firstOrCreate(['email' => 'saryglar@bmstu.ru', 'full_name' => 'Начын Сарыглар', 'password' => 'test123']);
        $curie = Chemist::query()->firstOrCreate(['email' => 'curie@lab.com', 'full_name' => 'Мария Кюри', 'password' => 'test123']);

        Chemist::factory()->count(5)->create();

        $publishedOne = $this->stage($curie, [
            'name' => 'Восстановление нитробензола',
            'brief_description' => 'Каталитическое гидрирование нитробензола до 4-аминофенола в слабокислой среде при интенсивном перемешивании.',
            'lifecycle_status' => 'published', 'yield_percentage' => 82.50, 'reaction_temperature_celsius' => 80,
            'created_at' => $now->copy()->subDays(2), 'updated_at' => $now->copy()->subDay(),
            'media_image_url' => 'pexels-polina-tankilevitch-3735701.jpg',
            'media_video_url' => '8540622-uhd_2160_4096_25fps.mp4',
        ]);
        $publishedTwo = $this->stage($chemist, [
            'name' => 'Ацилирование 4-аминофенола',
            'brief_description' => 'Реакция 4-аминофенола с уксусным ангидридом при контролируемой температуре.',
            'lifecycle_status' => 'published', 'yield_percentage' => 91.00, 'reaction_temperature_celsius' => 100,
            'created_at' => $now->copy()->subHours(10), 'updated_at' => $now->copy()->subHours(5),
            'media_image_url' => 'pexels-kindelmedia-8326692.jpg',
            'media_video_url' => '8490425-hd_1080_1920_25fps.mp4',
        ]);
        $this->stage($chemist, [
            'name' => 'Перекристаллизация парацетамола',
            'brief_description' => 'Очистка сырца парацетамола из водного раствора при контролируемом охлаждении.',
            'lifecycle_status' => 'draft', 'yield_percentage' => 76.40, 'reaction_temperature_celsius' => 25,
            'created_at' => $now->copy()->subHours(2), 'updated_at' => null,
            'media_image_url' => 'pexels-kindelmedia-8325706.jpg',
            'media_video_url' => '8326542-uhd_2160_3840_30fps.mp4',
        ]);
        $this->stage($curie, [
            'name' => 'Нитрование фенола',
            'brief_description' => 'Неэффективная стадия сохранена только для демонстрации soft delete.',
            'lifecycle_status' => 'deleted', 'yield_percentage' => 45.00, 'reaction_temperature_celsius' => 15,
            'created_at' => $now->copy()->subMonth(), 'updated_at' => $now->copy()->subMonth(),
            'media_image_url' => 'phenol_nitration.jpg',
            'media_video_url' => 'phenol_nitration.mp4',
        ]);

        foreach ([[$publishedOne, $chemist], [$publishedOne, $curie], [$publishedTwo, $curie]] as [$stage, $likingChemist]) {
            DB::table('synthesis_stage_likes')->updateOrInsert(
                ['synthesis_stage_id' => $stage->id, 'chemist_id' => $likingChemist->id]
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
