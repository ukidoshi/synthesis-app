@extends('layouts.synthesis_layout')

@section('content')
    @if(!$draftStage)
        <section class="draft-empty" aria-labelledby="draft-empty-title">
            <div>
                <p class="eyebrow">Новый этап</p>
                <h1 id="draft-empty-title">Создайте черновик</h1>
                <p class="draft-empty-copy">Выберите фото и видео, укажите название.</p>
            </div>
            <form method="POST" action="/synthesis-stages/draft" enctype="multipart/form-data">
                @csrf
                <div class="form-stack draft-create-form">
                    {{-- Фото и видео по умолчанию; клик по превью открывает проводник (без JS, через label) --}}
                    <div class="draft-media">
                        <div class="form-group">
                            <span class="form-label">Фото этапа</span>
                            <label for="draft-image" class="draft-media-frame draft-media-pick">
                                <img src="{{ $defaultImage }}" alt="Фото этапа по умолчанию" class="draft-media-content">
                                <span class="draft-media-badge">Выбрать фото</span>
                            </label>
                            <input id="draft-image" type="file" name="media_image" class="form-input form-file" accept="image/*">
                        </div>
                        <div class="form-group">
                            <span class="form-label">Короткое видео</span>
                            <label for="draft-video" class="draft-media-frame draft-media-pick">
                                <video src="{{ $defaultVideo }}" poster="{{ $defaultImage }}" class="draft-media-content" autoplay loop muted playsinline></video>
                                <span class="draft-media-badge">Выбрать видео</span>
                            </label>
                            <input id="draft-video" type="file" name="media_video" class="form-input form-file" accept="video/mp4,video/webm,video/quicktime">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="new-stage-name" class="form-label">Название этапа реакции</label>
                        <input id="new-stage-name" type="text" name="name" class="form-input" value="{{ old('name') }}" placeholder="Например, ацетилирование" required>
                    </div>
                    <button type="submit" class="button button-primary">Далее</button>
                </div>
            </form>
        </section>
    @else
        <section class="draft-empty" aria-labelledby="draft-edit-title">
            <div>
                <p class="eyebrow">Черновик</p>
                <h1 id="draft-edit-title">{{ $draftStage->name }}</h1>
                <p class="draft-empty-copy">Заполните параметры реакции и опубликуйте этап.</p>
            </div>
            <form method="POST" action="/synthesis-stages/{{ $draftStage->id }}/publish">
                @csrf
                <div class="form-stack draft-create-form">
                    {{-- Медиа после «Далее» только для просмотра: загруженные или по умолчанию --}}
                    <div class="draft-media">
                        <div class="form-group">
                            <span class="form-label">Фото этапа</span>
                            <div class="draft-media-frame">
                                <img src="{{ $draftStage->resolved_image }}" alt="Фото этапа {{ $draftStage->name }}" class="draft-media-content">
                            </div>
                        </div>
                        <div class="form-group">
                            <span class="form-label">Короткое видео</span>
                            <div class="draft-media-frame">
                                <video src="{{ $draftStage->resolved_video }}" poster="{{ $draftStage->resolved_image }}" class="draft-media-content" autoplay loop muted playsinline></video>
                            </div>
                        </div>
                    </div>
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
