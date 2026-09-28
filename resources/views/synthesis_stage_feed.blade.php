@extends('layouts.synthesis_layout')

@section('content')
    <div class="feed-box">
        <video class="feed-video-player" autoplay loop muted playsinline src="{{ $currentStage->resolved_video }}"></video>
        <div class="feed-overlay"></div>

        <div class="feed-sidebar">
            <div class="feed-badge">
                <span class="feed-badge-text">{{ $currentStage->stage_yield_percentage }}%<br>ВЫХОД</span>
            </div>

            <div class="feed-action">
                <div class="feed-btn">
                    <svg viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                </div>
                <span class="feed-action-label">Лайк</span>
            </div>

            <a href="/synthesis-stages/feed/{{ $currentStage->synthesis_stage_id }}?next=true" class="feed-action">
                <div class="feed-btn" style="background: rgba(255,255,255,0.2); backdrop-filter: blur(4px);">
                    <svg viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                </div>
                <span class="feed-action-label">Далее</span>
            </a>
        </div>

        <div class="feed-bottom-info">
            <div class="feed-title">{{ $currentStage->stage_name }}</div>
            <details>
                <summary class="feed-expand">ПОКАЗАТЬ ВСЁ <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg></summary>
                <div class="feed-desc" style="margin-top: 8px;">
                    {{ $currentStage->stage_brief_description ?: 'Нет описания для данной стадии' }}<br>
                    Температура стабилизируется на {{ $currentStage->reaction_temperature_celsius }}°C.
                </div>
            </details>
        </div>
    </div>
@endsection
