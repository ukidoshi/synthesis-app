<?php

namespace App\Http\Controllers;

use App\Models\Chemist;
use App\Models\SynthesisStage;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Log;

class SynthesisStageController extends Controller
{
    private const DEFAULT_IMAGE = '/images/default_reaction.svg';
    private const DEFAULT_VIDEO = '/videos/default_reaction.mp4';

    private function currentChemist(): Chemist
    {
        return Chemist::query()->firstOrCreate(
            ['email' => 'saryglar@bmstu.ru']
        );
    }

    private function withResolvedImage(SynthesisStage $stage): SynthesisStage
    {
        $stage->resolved_image = filled($stage->media_image_url) ? $stage->media_image_url : self::DEFAULT_IMAGE;
        return $stage;
    }

    private function withResolvedVideo(SynthesisStage $stage): SynthesisStage
    {
        $stage->resolved_video = filled($stage->media_video_url) ? $stage->media_video_url : self::DEFAULT_VIDEO;
        return $stage;
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

        return view('synthesis_stage_draft', compact('draftStage'));
    }

    public function storeStageDraft(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:150']]);
        $chemist = $this->currentChemist();
        $draft = SynthesisStage::query()->where('chemist_id', $chemist->id)->where('lifecycle_status', 'draft')->first();

        if (!$draft) {
            SynthesisStage::create([
                'name' => $data['name'],
                'brief_description' => '',
                'lifecycle_status' => 'draft',
                'yield_percentage' => 0,
                'reaction_temperature_celsius' => 20,
                'chemist_id' => $chemist->id,
            ]);

            return to_route('synthesis-stages.draft')->with('success', 'Черновик создан. Заполните параметры реакции и опубликуйте этап.');
        }

        return to_route('synthesis-stages.draft')->with('success', 'У вас уже есть черновик — продолжите его заполнение.');
    }

    /** POST: publish a draft through ORM. */
    public function publishStage(Request $request, int $synthesisStageId)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'brief_description' => ['required', 'string', 'max:1000'],
            'yield_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'reaction_temperature_celsius' => ['required', 'integer', 'between:-273,2000'],
        ]);

        $stage = SynthesisStage::query()
            ->whereKey($synthesisStageId)
            ->where('chemist_id', $this->currentChemist()->id)
            ->where('lifecycle_status', 'draft')
            ->firstOrFail();

        $stage->update([...$data, 'lifecycle_status' => 'published']);

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
