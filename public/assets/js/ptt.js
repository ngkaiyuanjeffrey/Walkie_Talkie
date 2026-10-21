(() => {
    const button = document.querySelector('#ptt-button');
    if (!button || !window.walkie) return;
    let active = false;
    let stream;
    const hint = document.querySelector('#floor-hint');
    const meter = document.querySelector('.meter');
    const setMeter = on => meter.classList.toggle('active', on);
    const stopStream = () => { stream?.getTracks().forEach(track => track.stop()); stream = null; };
    const release = async () => {
        if (!active) return;
        active = false; button.classList.remove('transmitting'); button.setAttribute('aria-pressed', 'false'); button.querySelector('strong').textContent = 'HOLD TO TALK'; hint.textContent = 'The channel is clear. Hold the button to speak.'; setMeter(false); stopStream();
        try { await window.walkie.post('ptt_release'); } catch (_) {}
    };
    const press = async event => {
        event?.preventDefault(); if (active) return;
        try {
            const result = await window.walkie.post('ptt_request');
            if (!result.granted) { hint.textContent = (result.busy_by || 'Someone') + ' is currently speaking.'; return; }
            try { stream = await navigator.mediaDevices.getUserMedia({audio: {echoCancellation: true, noiseSuppression: true, autoGainControl: true}, video: false}); } catch (_) { hint.textContent = 'Microphone permission is required to talk.'; await window.walkie.post('ptt_release'); return; }
            active = true; button.classList.add('transmitting'); button.setAttribute('aria-pressed', 'true'); button.querySelector('strong').textContent = 'TRANSMITTING'; hint.textContent = 'You are live. Release to end transmission.'; setMeter(true); window.dispatchEvent(new CustomEvent('walkie:stream', {detail: stream}));
        } catch (error) { hint.textContent = error.message || 'Unable to access the channel.'; }
    };
    button.addEventListener('pointerdown', press); button.addEventListener('pointerup', release); button.addEventListener('pointercancel', release); button.addEventListener('pointerleave', release);
    document.addEventListener('keydown', event => { if (event.code === 'Space' && !event.repeat && document.activeElement !== document.querySelector('input')) press(event); });
    document.addEventListener('keyup', event => { if (event.code === 'Space') release(); });
    document.querySelector('#mute-button').addEventListener('click', () => { if (stream) { const track = stream.getAudioTracks()[0]; track.enabled = !track.enabled; document.querySelector('#mute-button').textContent = track.enabled ? 'MIC ON' : 'MIC OFF'; } });
    window.addEventListener('beforeunload', () => { if (navigator.sendBeacon && window.walkie.session) navigator.sendBeacon('?api=leave', new Blob([JSON.stringify({action:'leave', token:window.walkie.session.token, csrf:document.querySelector('#radio-app').dataset.csrf})], {type:'application/json'})); });
})();
