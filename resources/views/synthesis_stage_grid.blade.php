@extends('layouts.synthesis_layout')

@section('content')
    <div class="header-section">
        <div class="app-title">SynthTrack</div>
    </div>

    <div class="grid-container">
        <div class="filter-box">
            <div class="filter-title">Фильтры</div>
            <form method="GET" action="/synthesis-stages">
                <div class="form-group" style="margin-bottom: 12px;">
                    <label class="form-label">Температура</label>
                    <input type="number" name="filter_temperature" class="form-input" placeholder="Например, 80 °C" value="{{ $filterTemperature }}">
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn-apply">Применить</button>
                    <a href="/synthesis-stages" class="btn-reset">Сбросить</a>
                </div>
            </form>
        </div>

        <div class="cards-grid">
            @forelse($stages as $index => $stage)
                <a href="/synthesis-stages/feed/{{ $stage->synthesis_stage_id }}" class="stage-tile">
                    <img src="{{ $stage->resolved_image }}" alt="Stage" class="stage-tile-img" onerror="this.src='/images/default_reaction.svg'">

                    <div class="stage-tile-num">ЭТАП {{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</div>
                    <div class="stage-tile-title">{{ $stage->stage_name }}</div>

                    <div style="margin-top: auto;">
                        <div class="yield-label">
                            <span>Выход</span>
                            <span class="yield-val">{{ $stage->stage_yield_percentage }}%</span>
                        </div>
                        <div class="yield-track">
                            <div class="yield-fill" style="width: {{ $stage->stage_yield_percentage }}%;"></div>
                        </div>
                        <div class="temp-label">Температура: {{ $stage->reaction_temperature_celsius }}°C</div>
                    </div>
                </a>
            @empty
                <div style="grid-column: span 2; text-align: center; color: #8D9DAE; padding: 20px;">Записи не найдены</div>
            @endforelse
        </div>
    </div>
@endsection
