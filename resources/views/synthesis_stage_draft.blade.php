@extends('layouts.synthesis_layout')

@section('content')
    @if(!$draftStage)
        <div style="padding: 20px; text-align: center;">
            <h3 style="color: #0A3B5C; margin-bottom: 12px;">У вас нет черновика</h3>
            <form method="POST" action="/synthesis-stages/draft">
                @csrf
                <button type="submit" class="btn-save" style="width: 100%;">Создать пустой черновик</button>
            </form>
        </div>
    @else
        <form method="POST" action="/synthesis-stages/{{ $draftStage->synthesis_stage_id }}/publish">
            @csrf
            <div class="top-nav">
                <div class="top-nav-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0A3B5C" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                    Редактирование синтеза
                </div>
                <button type="submit" class="btn-save">Сохранить</button>
            </div>

            <div class="form-stack">
                <div class="form-group">
                    <label class="form-label">Название этапа реакции</label>
                    <input type="text" name="stage_name" class="form-input" value="{{ $draftStage->stage_name }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Температура (°C)</label>
                    <input type="number" name="reaction_temperature_celsius" class="form-input" value="{{ $draftStage->reaction_temperature_celsius }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Объем выхода (%)</label>
                    <input type="number" step="0.01" name="stage_yield_percentage" class="form-input" value="{{ $draftStage->stage_yield_percentage }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Краткое описание</label>
                    <textarea name="stage_brief_description" class="form-input" rows="3">{{ $draftStage->stage_brief_description }}</textarea>
                </div>
            </div>
        </form>
    @endif
@endsection
