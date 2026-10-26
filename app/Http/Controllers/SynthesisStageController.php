<?php

namespace App\Http\Controllers;

use App\Models\Chemist;
use App\Models\SynthesisStage;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Log;

class SynthesisStageController extends Controller
{
    private const DEFAULT_IMAGE = '/images/default_reaction.svg';
    private const DEFAULT_VIDEO = '/videos/default_reaction.mp4';

    private const MEDIA_IMAGE_RULES = ['nullable', 'file', 'image', 'max:10240'];
    private const MEDIA_VIDEO_RULES = ['nullable', 'file', 'mimetypes:video/mp4,video/webm,video/quicktime', 'max:51200'];

    private function currentChemist(): Chemist
    {
        return Chemist::query()->firstOrCreate(
            ['email' => 'saryglar@bmstu.ru']
        );
    }

    private function withResolvedImage(SynthesisStage $stage): SynthesisStage
    {
        $rawPath = $stage->getRawOriginal('media_image_url');

        if (filled($rawPath) && Storage::disk('s3')->exists($rawPath)) {
            $stage->resolved_image = Storage::disk('s3')->url($rawPath);
        } else {
            $stage->resolved_image = asset(self::DEFAULT_IMAGE);
        }

        return $stage;
    }

    private function withResolvedVideo(SynthesisStage $stage): SynthesisStage
    {
        $rawPath = $stage->getRawOriginal('media_video_url');

        if (filled($rawPath) && Storage::disk('s3')->exists($rawPath)) {
            $stage->resolved_video = Storage::disk('s3')->url($rawPath);
        } else {
            $stage->resolved_video = asset(self::DEFAULT_VIDEO);
        }

        return $stage;
    }

    private function storeUploadedMedia(UploadedFile $file, string $folder): string
    {
        return $file->store($folder, ['disk' => 's3', 'visibility' => 'public']);
    }

    /** GET: tiles with server-side temperature filtering. */
    public function renderStageGrid(Request $request)
    {
        $filterTemperature = $request->integer('filter_temperature') ?: null;
        $filterDesc = $request->string('filter_desc') ?: null;

        $stages = SynthesisStage::query()
            ->whereIn('lifecycle_status', ['published', 'draft'])
            ->when($filterTemperature !== null,
                fn($query) => $query->where('reaction_temperature_celsius', '>=', $filterTemperature)
            )
            ->when($filterDesc !== null && $filterDesc != '',
                fn(Builder $query) => $query->whereFullText('brief_description', $filterDesc)
            )
            ->withCount('likingChemists')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn(SynthesisStage $stage) => $this->withResolvedImage($stage));

        return view('synthesis_stage_grid', [
            'stages' => $stages,
            'filterTemperature' => $filterTemperature,
            'filterDesc' => $filterDesc,
            'currentChemistId' => $this->currentChemist()->id,
        ]);
    }

    /** GET: a single published stage, including the next-stage flow. */
    public function renderStageFeed(Request $request, ?int $synthesisStageId = null)
    {
        $baseQuery = SynthesisStage::query()
            ->where('lifecycle_status', 'published')
            ->withCount('likingChemists');

        if ($synthesisStageId !== null && $request->boolean('next')) {
            abort_unless((clone $baseQuery)->whereKey($synthesisStageId)->exists(), 404, 'Этап не опубликован или удалён.');
            $currentStage = (clone $baseQuery)->where('id', '>', $synthesisStageId)->orderBy('id')->first() ?? (clone $baseQuery)->orderBy('id')->first();
        } elseif ($synthesisStageId !== null) {
            $currentStage = (clone $baseQuery)->find($synthesisStageId);
        } else {
            $currentStage = (clone $baseQuery)->orderBy('id')->first();
        }

        abort_unless($currentStage, 404, 'Нет опубликованных этапов синтеза.');

        return view('synthesis_stage_feed', ['currentStage' => $this->withResolvedVideo($currentStage)]);
    }

    /** GET: the current chemist's only draft. */
    public function renderStageDraft()
    {
        $draftStage = SynthesisStage::query()
            ->where('chemist_id', $this->currentChemist()->id)
            ->where('lifecycle_status', 'draft')
            ->latest('id')
            ->first();

        // Медиа черновика из Silo(MinIO) или дефолтные файлы с SSR-сервера
        if ($draftStage) {
            $this->withResolvedVideo($this->withResolvedImage($draftStage));
        }
        $defaultImage = asset(self::DEFAULT_IMAGE);
        $defaultVideo = asset(self::DEFAULT_VIDEO);

        return view('synthesis_stage_draft', compact('draftStage', 'defaultImage', 'defaultVideo'));
    }

    public function storeStageDraft(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'media_image' => self::MEDIA_IMAGE_RULES,
            'media_video' => self::MEDIA_VIDEO_RULES,
        ]);
        $chemist = $this->currentChemist();
        $draft = SynthesisStage::query()->where('chemist_id', $chemist->id)->where('lifecycle_status', 'draft')->first();

        if (!$draft) {
            $mediaImagePath = $request->file('media_image')
                ? $this->storeUploadedMedia($request->file('media_image'), 'stages/images')
                : null;
            $mediaVideoPath = $request->file('media_video')
                ? $this->storeUploadedMedia($request->file('media_video'), 'stages/videos')
                : null;

            SynthesisStage::create([
                'name' => $data['name'],
                'brief_description' => '',
                'lifecycle_status' => 'draft',
                'yield_percentage' => 0,
                'reaction_temperature_celsius' => 20,
                'chemist_id' => $chemist->id,
                'media_image_url' => $mediaImagePath,
                'media_video_url' => $mediaVideoPath,
            ]);

            return to_route('synthesis-stages.draft')->with('success', 'Черновик создан. Заполните параметры реакции и опубликуйте этап.');
        }

        return to_route('synthesis-stages.draft')->with('success', 'У вас уже есть черновик — продолжите его заполнение.');
    }

    /** POST: publish a draft through ORM. */
    public function publishStage(Request $request, int $synthesisStageId)
    {
        $data = $request->validate([
            'brief_description' => ['required', 'string', 'max:1000'],
            'yield_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'reaction_temperature_celsius' => ['required', 'integer', 'between:-273,2000'],
        ]);

        $stage = SynthesisStage::query()
            ->whereKey($synthesisStageId)
            ->where('chemist_id', $this->currentChemist()->id)
            ->where('lifecycle_status', 'draft')
            ->firstOrFail();

        $update = [...$data, 'lifecycle_status' => 'published'];

        $stage->update($update);

        return to_route('synthesis-stages.grid')->with('success', 'Этап опубликован и доступен в ленте.');
    }

    public function destroyStageDirectSql(int $synthesisStageId)
    {
        $updated = DB::update(
            "UPDATE synthesis_stages SET lifecycle_status = 'deleted', updated_at = NOW() WHERE id = ? AND chemist_id = ? AND lifecycle_status <> 'deleted'",
            [$synthesisStageId, $this->currentChemist()->id]
        );

        return to_route('synthesis-stages.grid')->with(
            $updated ? 'success' : 'error',
            $updated ? 'Этап логически удалён.' : 'Невозможно удалить этот этап.'
        );
    }
}
