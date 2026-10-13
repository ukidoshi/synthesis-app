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

    /**
     * @OA\Get(
     *     path="/synthesis-stages",
     *     tags={"Synthesis Stages"},
     *     summary="Список опубликованных этапов",
     *     description="Только lifecycle_status=published. Фильтр по минимальной температуре.",
     *     @OA\Parameter(
     *         name="filter_temperature",
     *         in="query",
     *         required=false,
     *         description="Минимальная температура реакции, °C (reaction_temperature_celsius >= значение)",
     *         @OA\Schema(type="integer", example=80)
     *     ),
     *     @OA\Response(response=200, description="Список этапов", @OA\JsonContent(ref="#/components/schemas/SynthesisStageCollectionResponse"))
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/synthesis-stages/feed/{id}",
     *     tags={"Synthesis Stages"},
     *     summary="Лента: один опубликованный этап",
     *     description="Без id — первый этап. С id — этот этап. С next=true — следующий после id (циклически).",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=false,
     *         description="ID текущего этапа (можно опустить в URL: GET /synthesis-stages/feed)",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="next",
     *         in="query",
     *         required=false,
     *         description="Перейти к следующему этапу после id",
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *     @OA\Response(response=200, description="Этап ленты", @OA\JsonContent(ref="#/components/schemas/SynthesisStageResponse")),
     *     @OA\Response(
     *         response=404,
     *         description="Нет опубликованных этапов",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Нет опубликованных этапов"))
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/synthesis-stages/draft",
     *     tags={"Synthesis Stages"},
     *     summary="Черновик текущего пользователя",
     *     description="Один черновик на химика (последний по id).",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Черновик найден", @OA\JsonContent(ref="#/components/schemas/SynthesisStageResponse")),
     *     @OA\Response(
     *         response=404,
     *         description="Черновик не найден",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Черновик не найден"))
     *     ),
     *     @OA\Response(response=401, description="JWT не передан или недействителен")
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/synthesis-stages",
     *     tags={"Synthesis Stages"},
     *     summary="Создание черновика (multipart, MinIO)",
     *     description="Загрузка медиа в S3/MinIO. Повторный черновик — 400.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name"},
     *                 @OA\Property(property="name", type="string", maxLength=150, example="Перекристаллизация парацетамола"),
     *                 @OA\Property(
     *                     property="media_image",
     *                     type="string",
     *                     format="binary",
     *                     description="Изображение этапа (image/*, max 10 MB)"
     *                 ),
     *                 @OA\Property(
     *                     property="media_video",
     *                     type="string",
     *                     format="binary",
     *                     description="Короткое видео (mp4/webm/quicktime, max 50 MB)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Черновик создан", @OA\JsonContent(ref="#/components/schemas/SynthesisStageResponse")),
     *     @OA\Response(
     *         response=400,
     *         description="Черновик уже существует",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Черновик уже существует"))
     *     ),
     *     @OA\Response(response=401, description="Не авторизован"),
     *     @OA\Response(response=422, description="Ошибка валидации", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */
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

    /**
     * @OA\Put(
     *     path="/synthesis-stages/{id}/publish",
     *     tags={"Synthesis Stages"},
     *     summary="Публикация черновика",
     *     description="Меняет status на published. Только свой черновик.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID этапа-черновика",
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={"brief_description","yield_percentage","reaction_temperature_celsius"},
     *                 @OA\Property(property="brief_description", type="string", maxLength=1000, example="Очистка сырца из водного раствора."),
     *                 @OA\Property(property="yield_percentage", type="number", format="float", minimum=0, maximum=100, example=76.4),
     *                 @OA\Property(property="reaction_temperature_celsius", type="integer", minimum=-273, maximum=2000, example=25)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Этап опубликован", @OA\JsonContent(ref="#/components/schemas/SynthesisStageResponse")),
     *     @OA\Response(response=401, description="Не авторизован"),
     *     @OA\Response(response=404, description="Черновик не найден или не принадлежит пользователю"),
     *     @OA\Response(response=422, description="Ошибка валидации", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */
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

    /**
     * @OA\Delete(
     *     path="/synthesis-stages/{id}",
     *     tags={"Synthesis Stages"},
     *     summary="Логическое удаление этапа",
     *     description="lifecycle_status → deleted. Только свои этапы.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", example=3)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Удалено",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Этап логически удалён"))
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Нет прав или этап не найден",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Невозможно удалить этот этап (не найден или нет прав)"))
     *     ),
     *     @OA\Response(response=401, description="Не авторизован")
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/synthesis-stages/{id}/like",
     *     tags={"Synthesis Stages"},
     *     summary="Поставить или снять лайк",
     *     description="action=1 — лайк, action=0 — отмена.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID опубликованного этапа",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={"action"},
     *                 @OA\Property(property="action", type="integer", enum={0,1}, example=1, description="1 — лайк, 0 — снять лайк")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Статус лайка обновлён",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Статус лайка обновлен"))
     *     ),
     *     @OA\Response(response=401, description="Не авторизован"),
     *     @OA\Response(response=422, description="Ошибка валидации", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */
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
