<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SynthTrack</title>
    <link rel="stylesheet" href="/css/stage_style.css">
</head>
<body>
<div class="mobile-wrapper">
    <main class="viewport-content">
        @if(session('success'))
            <p class="flash-message flash-success" role="status">{{ session('success') }}</p>
        @endif
        @if(session('error'))
            <p class="flash-message flash-error" role="alert">{{ session('error') }}</p>
        @endif
        @if($errors->any())
            <p class="flash-message flash-error" role="alert">Проверьте заполнение полей формы.</p>
        @endif
        @yield('content')
    </main>
    <nav class="bottom-dock" aria-label="Основная навигация">
        <div class="tabs-row">
            <a href="/synthesis-stages/feed" class="tab-item {{ Request::is('synthesis-stages/feed*') ? 'active' : '' }}" @if(Request::is('synthesis-stages/feed*')) aria-current="page" @endif>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h11a3 3 0 0 1 3 3v5a3 3 0 0 1-3 3H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2Z"></path><path d="m18 11 4-2v7l-4-2"></path></svg><span>Лента</span>
            </a>
            <a href="/synthesis-stages" class="tab-item {{ Request::is('synthesis-stages') ? 'active' : '' }}" @if(Request::is('synthesis-stages')) aria-current="page" @endif>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 4v16"></path><path d="M5 4h13l-2 4 2 4H5"></path><circle cx="7" cy="18" r="2"></circle></svg><span>Этапы</span>
            </a>
            <a href="/synthesis-stages/draft" class="tab-item {{ Request::is('synthesis-stages/draft*') ? 'active' : '' }}" @if(Request::is('synthesis-stages/draft*')) aria-current="page" @endif>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"></path><path d="M14 2v6h6"></path><path d="m12 12 4 4m0-4-4 4"></path></svg><span>Черновик</span>
            </a>
        </div>
    </nav>
</div>
</body>
</html>
