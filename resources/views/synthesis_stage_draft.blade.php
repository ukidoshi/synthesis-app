@extends('layouts.synthesis_layout')

@section('content')
    @if(!$draftStage)
        <section class="draft-empty" aria-labelledby="draft-empty-title">
            <div>
                <p class="eyebrow">Новый этап</p>
                <h1 id="draft-empty-title">Создайте черновик</h1>
                <p class="draft-empty-copy">Укажите название и выберите медиа.</p>
            </div>
            <form method="POST" action="/synthesis-stages/draft" enctype="multipart/form-data">
                @csrf
                <div class="form-stack draft-create-form">
                    <div class="form-group">
                        <label for="new-stage-name" class="form-label">Название этапа реакции</label>
                        <input id="new-stage-name" type="text" name="name" class="form-input" value="{{ old('name') }}" placeholder="Например, ацетилирование" required>
                    </div>
                    <div class="form-group">
                        <label for="draft-image" class="form-label">Фото этапа</label>
                        <input id="draft-image" type="file" name="media_image" class="form-input form-file" accept="image/*">
                    </div>
                    <div class="form-group">
                        <label for="draft-video" class="form-label">Короткое видео</label>
                        <input id="draft-video" type="file" name="media_video" class="form-input form-file" accept="video/mp4,video/webm,video/quicktime">
                    </div>
                    <button type="submit" class="button button-primary">Далее</button>
                </div>
            </form>
        </section>
    @else
        <section class="draft-empty" aria-labelledby="draft-edit-title">
            <div>
                <p class="eyebrow">Черновик</p>
                <h1 id="draft-edit-title">Редактирование синтеза</h1>
                <p class="draft-empty-copy">Заполните параметры реакции и опубликуйте этап.</p>
            </div>
            <form method="POST" action="/synthesis-stages/{{ $draftStage->id }}/publish" enctype="multipart/form-data">
                @csrf
                <div class="form-stack draft-create-form">
{{--                    <div class="form-group">--}}
{{--                        <label for="stage-name" class="form-label">Название этапа реакции</label>--}}
{{--                        <input id="stage-name" type="text" name="name" class="form-input" value="{{ old('name', $draftStage->name) }}" required>--}}
{{--                    </div>--}}
{{--                    <div class="form-group">--}}
{{--                        <label for="edit-stage-image" class="form-label">Фото этапа</label>--}}
{{--                        @if(filled($draftStage->getRawOriginal('media_image_url')))--}}
{{--                            <p class="form-hint">Текущий файл: {{ basename($draftStage->getRawOriginal('media_image_url')) }}</p>--}}
{{--                        @endif--}}
{{--                        <input id="edit-stage-image" type="file" name="media_image" class="form-input form-file" accept="image/*">--}}
{{--                    </div>--}}
{{--                    <div class="form-group">--}}
{{--                        <label for="edit-stage-video" class="form-label">Короткое видео</label>--}}
{{--                        @if(filled($draftStage->getRawOriginal('media_video_url')))--}}
{{--                            <p class="form-hint">Текущий файл: {{ basename($draftStage->getRawOriginal('media_video_url')) }}</p>--}}
{{--                        @endif--}}
{{--                        <input id="edit-stage-video" type="file" name="media_video" class="form-input form-file" accept="video/mp4,video/webm,video/quicktime">--}}
{{--                    </div>--}}
                    <div class="form-group">
                        <label for="temperature" class="form-label">Температура</label>
                        <input id="temperature" type="number" name="reaction_temperature_celsius" class="form-input" value="{{ old('reaction_temperature_celsius', $draftStage->reaction_temperature_celsius) }}" required>
                    </div>
                    <div class="form-group">
                        <label for="yield" class="form-label">Объем выхода</label>
                        <input id="yield" type="number" step="0.01" name="yield_percentage" class="form-input" value="{{ old('yield_percentage', $draftStage->yield_percentage) }}" required>
                    </div>
                    <div class="form-group">
                        <label for="description" class="form-label">Краткое описание</label>
                        <textarea id="description" name="brief_description" class="form-input" rows="4" required>{{ old('brief_description', $draftStage->brief_description) }}</textarea>
                    </div>
                    <button type="submit" class="button button-primary">Опубликовать</button>
                </div>
            </form>
        </section>
    @endif
@endsection
