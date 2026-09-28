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
    <div class="viewport-content">
        @yield('content')
    </div>
    <nav class="bottom-dock">
        <div class="tabs-row">
            <a href="/synthesis-stages/feed" class="tab-item {{ Request::is('synthesis-stages/feed*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18"></rect><line x1="7" y1="2" x2="7" y2="22"></line><line x1="17" y1="2" x2="17" y2="22"></line><line x1="2" y1="12" x2="22" y2="12"></line><line x1="2" y1="7" x2="7" y2="7"></line><line x1="2" y1="17" x2="7" y2="17"></line><line x1="17" y1="17" x2="22" y2="17"></line><line x1="17" y1="7" x2="22" y2="7"></line></svg>
                <span>Лента</span>
            </a>
            <a href="/synthesis-stages" class="tab-item {{ Request::is('synthesis-stages') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                <span>Этапы</span>
            </a>
            <a href="/synthesis-stages/draft" class="tab-item {{ Request::is('synthesis-stages/draft*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                <span>Черновик</span>
            </a>
        </div>
        <div style="display:flex; justify-content:center; padding: 8px 0;"><div style="width:134px; height:5px; background:#111C24; border-radius:100px;"></div></div>
    </nav>
</div>
</body>
</html>
