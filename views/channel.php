<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#08131c">
    <link rel="manifest" href="public/manifest.webmanifest">
    <link rel="stylesheet" href="public/assets/css/app.css">
    <title>Channel | Walkie Talkie</title>
</head>
<body class="channel-page">
<header class="topbar">
    <a class="brand-link" href="./"><span class="brand-dot"></span> WALKIE <strong>TALKIE</strong></a>
    <div class="connection"><i id="status-light"></i><span id="connection-status">CONNECTING</span></div>
</header>
<main class="radio-layout" id="radio-app" data-csrf="<?= e($_SESSION['csrf']) ?>">
    <section class="radio-main">
        <div class="channel-heading"><div><p class="eyebrow">CURRENT CHANNEL</p><h1># <span id="channel-name">channel</span></h1></div><div class="session-pill"><span></span> LIVE SESSION</div></div>
        <div class="speaker-banner" id="speaker-banner"><span class="live-dot"></span><div><small id="speaker-label">ON AIR</small><strong id="speaker-name">Waiting for a speaker</strong></div></div>
        <div class="ptt-wrap"><div class="ptt-ring ring-one"></div><div class="ptt-ring ring-two"></div><button id="ptt-button" class="ptt-button" type="button" aria-pressed="false" aria-label="Hold to talk"><span class="ptt-icon">◉</span><strong>HOLD TO TALK</strong><small>SPACEBAR</small></button></div>
        <p class="floor-hint" id="floor-hint">The channel is clear. Hold the button to speak.</p>
        <div class="meter" aria-label="Audio level"><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span></div>
        <div class="radio-actions"><button id="mute-button" class="ghost-button" type="button">MIC OFF</button><button id="leave-button" class="danger-button" type="button">LEAVE CHANNEL <b>↗</b></button></div>
    </section>
    <aside class="side-panel"><div class="side-heading"><div><p class="eyebrow">IN THE CHANNEL</p><h2><span id="user-count">0</span> USERS</h2></div><span class="users-icon">◎</span></div><div id="participant-list" class="participant-list"></div><div class="side-footer"><span class="status-bar"></span><p>Push to talk is active<br><small>Maximum talk time: 30 seconds</small></p></div></aside>
</main>
<audio id="remote-audio" autoplay></audio>
<script>window.WALKIE_SESSION = <?= json_encode($_SESSION['walkie'] ?? []) ?>;</script>
<script src="public/assets/js/webrtc.js" defer></script>
<script src="public/assets/js/ptt.js" defer></script>
<script src="public/assets/js/app.js" defer></script>
<script src="public/assets/js/pwa.js" defer></script>
</body>
</html>
