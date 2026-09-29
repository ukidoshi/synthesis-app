@extends('layouts.synthesis_layout')

@section('content')
    @if(!$draftStage)
        <section class="draft-empty" aria-labelledby="draft-empty-title">
            <div>
                <p class="eyebrow">Новый этап</p>
                <h1 id="draft-empty-title">Создайте черновик</h1>
                <p class="draft-empty-copy">Укажите название и выберите медиа.</p>
            </div>
            <form method="POST" action="/synthesis-stages/draft">
                @csrf
                <div class="form-stack draft-create-form">
                    <div class="form-group">
                        <label for="new-stage-name" class="form-label">Название этапа реакции</label>
                        <input id="new-stage-name" type="text" name="name" class="form-input" value="{{ old('name') }}" placeholder="Например, ацетилирование" required>
                    </div>
                    <div class="form-group">
                        <label for="draft-image" class="form-label">Фото этапа</label>
                        <input id="draft-image" type="text" class="form-input" placeholder="URL изображения или имя файла">
                    </div>
                    <div class="form-group">
                        <label for="draft-video" class="form-label">Короткое видео</label>
                        <input id="draft-video" type="text" class="form-input" placeholder="URL видео или имя файла">
                    </div>
                    <button type="submit" class="button button-primary">Далее</button>
                </div>
            </form>
        </section>
    @else
        <form method="POST" action="/synthesis-stages/{{ $draftStage->id }}/publish">
            @csrf
            <header class="top-nav">
                <div class="top-nav-title"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 12H5"></path><path d="m12 19-7-7 7-7"></path></svg><span>Редактирование синтеза</span></div>
                <button type="submit" class="btn-save">Опубликовать</button>
            </header>
            <div class="form-stack">
                <div class="form-group">
                    <label for="stage-name" class="form-label">НАЗВАНИЕ ЭТАПА РЕАКЦИИ</label>
                    <input id="stage-name" type="text" name="name" class="form-input" value="{{ $draftStage->name }}" required>
                </div>
                <div class="form-group">
                    <label for="temperature" class="form-label">Температура</label>
                    <input id="temperature" type="number" name="reaction_temperature_celsius" class="form-input" value="{{ $draftStage->reaction_temperature_celsius }}" required>
                </div>
                <div class="form-group">
                    <label for="yield" class="form-label">Объем выхода</label>
                    <input id="yield" type="number" step="0.01" name="yield_percentage" class="form-input" value="{{ $draftStage->yield_percentage }}" required>
                </div>
                <div class="form-group">
                    <label for="description" class="form-label">Краткое описание</label>
                    <textarea id="description" name="brief_description" class="form-input" rows="4" required>{{ $draftStage->brief_description }}</textarea>
                </div>
            </div>
        </form>
    @endif
@endsection
