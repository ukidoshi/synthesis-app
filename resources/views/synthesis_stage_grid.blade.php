@extends('layouts.synthesis_layout')

@section('content')
    <header class="header-section"><h1 class="app-title">SynthTrack</h1></header>
    <section class="grid-container" aria-label="Этапы синтеза">
        <details class="filter-box" open>
            <summary class="filter-title">Фильтры <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg></summary>
            <form method="GET" action="/synthesis-stages">
                <div class="form-group">
                    <label for="filter-temperature" class="form-label">Температура</label>
                    <input id="filter-temperature" type="number" name="filter_temperature" class="form-input" placeholder="°C" value="{{ $filterTemperature }}">
                    <label for="filter-desc" class="form-label">Компоненты</label>
                    <input id="filter-desc" type="search" name="filter_desc" class="form-input" placeholder="Название компонента, процесса..." value="{{ $filterDesc }}">
                </div>
                <div class="filter-actions">
                    <button type="submit" class="button button-primary">Применить</button>
                    <a href="/synthesis-stages" class="button button-outline">Сбросить</a>
                </div>
            </form>
        </details>
        <div class="cards-grid">
            @forelse($stages as $index => $stage)
                <article class="stage-card">
                <a href="/synthesis-stages/feed/{{ $stage->id }}" class="stage-tile" aria-label="Открыть этап: {{ $stage->name }}">
                    <img src="{{ $stage->resolved_image }}" alt="" class="stage-tile-img">
                    <span class="stage-tile-num">ЭТАП {{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }} {{ $stage->lifecycle_status == 'published' ? '' : ' | Черновик' }}</span>
                    <span class="stage-tile-title">{{ $stage->name }}</span>
                    <span class="stage-tile-details">
                        <span class="yield-label"><span>Выход</span><span class="yield-val">{{ $stage->yield_percentage }}%</span></span>
                        <progress class="yield-track" value="{{ $stage->yield_percentage }}" max="100">{{ $stage->yield_percentage }}%</progress>
                        <span class="temp-label">Температура: {{ $stage->reaction_temperature_celsius }}°C</span>
                    </span>
                </a>
                @if($stage->chemist_id === $currentChemistId)
                    <form method="POST" action="/synthesis-stages/{{ $stage->id }}/delete" class="stage-delete-form">
                        @csrf
                        <button type="submit" class="stage-delete-button">Удалить</button>
                    </form>
                @endif
                </article>
            @empty
                <p class="grid-empty">Записи не найдены</p>
            @endforelse
        </div>
    </section>
@endsection
