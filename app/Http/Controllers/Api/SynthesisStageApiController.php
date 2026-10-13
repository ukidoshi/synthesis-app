<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SynthesisStage;
use App\Http\Resources\SynthesisStageResource;
use App\Services\AuthSingleton;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;

class SynthesisStageApiController extends Controller
{
    private const MEDIA_IMAGE_RULES = ['nullable', 'file', 'image', 'max:10240'];
    private const MEDIA_VIDEO_RULES = ['nullable', 'file', 'mimetypes:video/mp4,video/webm,video/quicktime', 'max:51200'];

    private function storeUploadedMedia(UploadedFile $file, string $folder): string
    {
        // Названия файлов генерируются на латинице автоматически методом store[cite: 2]
        return $file->store($folder, ['disk' => 's3', 'visibility' => 'public']);
    }

    // 1. GET список с фильтрацией (только опубликованные)
    public function index(Request $request)
    {
        $filterTemperature = $request->integer('filter_temperature') ?: null;

        $query = SynthesisStage::query()
            ->where('lifecycle_status', 'published')
            ->when($filterTemperature !== null,
                fn($q) => $q->where('reaction_temperature_celsius', '>=', $filterTemperature)
            )
            ->withCount('likingChemists')
            ->orderByDesc('id');

        return SynthesisStageResource::collection($query->get());
    }

    // 2. GET лента (только опубликованные)
    public function feed(Request $request, $id = null)
    {
        $baseQuery = SynthesisStage::query()
            ->where('lifecycle_status', 'published')
            ->withCount('likingChemists');

        if ($id !== null && $request->boolean('next')) {
            $stage = (clone $baseQuery)->where('id', '>', $id)->orderBy('id')->first() ?? (clone $baseQuery)->orderBy('id')->first();
        } elseif ($id !== null) {
            $stage = (clone $baseQuery)->find($id);
        } else {
            $stage = (clone $baseQuery)->orderBy('id')->first();
        }

        if (!$stage) return response()->json(['message' => 'Нет опубликованных этапов'], 404);

        return new SynthesisStageResource($stage);
    }

    // 3. GET получение черновика (ид не указывается)
    public function draft()
    {
        $draft = SynthesisStage::query()
            ->where('chemist_id', AuthSingleton::getId())
            ->where('lifecycle_status', 'draft')
            ->latest('id')
            ->first();

        if (!$draft) return response()->json(['message' => 'Черновик не найден'], 404);

        return new SynthesisStageResource($draft);
    }

    // 4. POST добавление файлов изображения и видео (как файлы)[cite: 2]
    public function store(Request $request)
    {
        // Системные поля передавать с клиента запрещено[cite: 2]
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'media_image' => self::MEDIA_IMAGE_RULES,
            'media_video' => self::MEDIA_VIDEO_RULES,
        ]);

        $chemistId = AuthSingleton::getId();
        $existingDraft = SynthesisStage::where('chemist_id', $chemistId)
            ->where('lifecycle_status', 'draft')
            ->first();

        if ($existingDraft) {
            return response()->json(['message' => 'Черновик уже существует'], 400);
        }

        $mediaImagePath = $request->hasFile('media_image')
            ? $this->storeUploadedMedia($request->file('media_image'), 'stages/images')
            : null;

        $mediaVideoPath = $request->hasFile('media_video')
            ? $this->storeUploadedMedia($request->file('media_video'), 'stages/videos')
            : null;

        $stage = SynthesisStage::create([
            'name' => $data['name'],
            'brief_description' => '',
            'lifecycle_status' => 'draft',
            'yield_percentage' => 0,
            'reaction_temperature_celsius' => 20,
            'chemist_id' => $chemistId,
            'media_image_url' => $mediaImagePath,
            'media_video_url' => $mediaVideoPath,
        ]);

        return new SynthesisStageResource($stage);
    }

    // 5. PUT публикации (смена статуса)
    public function publish(Request $request, int $id)
    {
        // Разрешаем обновлять только бизнес-поля, статусы и ИД передавать нельзя[cite: 2]
        $data = $request->validate([
            'brief_description' => ['required', 'string', 'max:1000'],
            'yield_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'reaction_temperature_celsius' => ['required', 'integer', 'between:-273,2000'],
        ]);

        $stage = SynthesisStage::query()
            ->whereKey($id)
            ->where('chemist_id', AuthSingleton::getId())
            ->where('lifecycle_status', 'draft')
            ->firstOrFail();

        $stage->update([...$data, 'lifecycle_status' => 'published']);

        return new SynthesisStageResource($stage);
    }

    // 6. DELETE удаление (soft delete, только услуги этого пользователя)[cite: 2]
    public function destroy(int $id)
    {
        $updated = DB::update(
            "UPDATE synthesis_stages SET lifecycle_status = 'deleted', updated_at = NOW() WHERE id = ? AND chemist_id = ? AND lifecycle_status <> 'deleted'",
            [$id, AuthSingleton::getId()]
        );

        if (!$updated) {
            return response()->json(['message' => 'Невозможно удалить этот этап (не найден или нет прав)'], 403);
        }

        return response()->json(['message' => 'Этап логически удалён']);
    }

    // 7. POST like (0 отменяет, 1 ставит)[cite: 2]
    public function toggleLike(Request $request, int $id)
    {
        $action = $request->integer('action');

        if ($action === 1) {
            DB::table('synthesis_stage_likes')->updateOrInsert(
                ['synthesis_stage_id' => $id, 'chemist_id' => AuthSingleton::getId()]
            );
        } elseif ($action === 0) {
            DB::table('synthesis_stage_likes')
                ->where('synthesis_stage_id', $id)
                ->where('chemist_id', AuthSingleton::getId())
                ->delete();
        }

        return response()->json(['message' => 'Статус лайка обновлен']);
    }
}
