@extends('layouts.synthesis_layout')

@section('content')
    <article class="feed-box">
        <video class="feed-video-player" autoplay loop muted playsinline poster="/images/default_reaction.svg" src="{{ $currentStage->resolved_video }}"></video>
        <div class="feed-overlay" aria-hidden="true"></div>

        <aside class="feed-sidebar" aria-label="Действия с этапом">
            <div class="feed-badge"><span class="feed-badge-text">{{ $currentStage->yield_percentage }}%<br>ВЫХОД</span></div>
            <div class="feed-action">
                <div class="feed-btn" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78Z"></path></svg></div>
                <span class="feed-action-label">Лайк {{ $currentStage->likingChemists()->count() }}</span>
            </div>
            <a href="/synthesis-stages/feed/{{ $currentStage->id }}?next=true" class="feed-action feed-action-next">
                <div class="feed-btn" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg></div>
                <span class="feed-action-label">Далее</span>
            </a>
        </aside>

        <div class="feed-bottom-info">
            <h1 class="feed-title">{{ $currentStage->name }}</h1>
            <details>
                <summary class="feed-expand">ПОКАЗАТЬ ВСЁ <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg></summary>
                <p class="feed-desc">{{ $currentStage->brief_description ?: 'Нет описания для данной стадии.' }} Температура стабилизируется на {{ $currentStage->reaction_temperature_celsius }}°C.</p>
            </details>
        </div>
    </article>
@endsection
