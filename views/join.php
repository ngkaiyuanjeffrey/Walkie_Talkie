<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#08131c">
    <link rel="manifest" href="public/manifest.webmanifest">
    <link rel="stylesheet" href="public/assets/css/app.css">
    <title>Walkie Talkie | Push to Talk</title>
</head>
<body class="landing">
<main class="join-shell">
    <section class="brand-panel">
        <div class="brand-mark"><span class="brand-dot"></span> WT</div>
        <p class="eyebrow">PRIVATE VOICE CHANNELS</p>
        <h1>Talk when<br><em>it matters.</em></h1>
        <p class="lede">A focused, browser-based walkie-talkie for teams that need a clear line and no clutter.</p>
        <div class="signal-line"><span></span><span></span><span></span><span></span><span></span></div>
        <p class="microcopy">Voice only · No accounts · Your channel stays yours</p>
    </section>
    <section class="join-panel">
        <div class="panel-kicker">START A SESSION <span>01 / 02</span></div>
        <h2>Join a channel</h2>
        <p class="muted">Enter the same channel as your team. Choose a name people will recognize.</p>
        <form id="join-form" class="join-form">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
            <label for="nickname">Your name</label>
            <input id="nickname" name="nickname" maxlength="24" autocomplete="nickname" placeholder="e.g. Alex" required>
            <label for="channel">Channel name</label>
            <input id="channel" name="channel" maxlength="64" autocomplete="off" placeholder="e.g. field-team" required>
            <button class="primary-button" type="submit"><span>JOIN CHANNEL</span><b>→</b></button>
            <p id="join-error" class="form-error" role="alert"></p>
        </form>
        <div class="join-note"><span>◉</span> Works best on a secure connection with microphone access.</div>
    </section>
</main>
<script src="public/assets/js/app.js" defer></script>
<script src="public/assets/js/pwa.js" defer></script>
</body>
</html>
