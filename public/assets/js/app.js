(() => {
    const form = document.querySelector('#join-form');
    if (form) {
        form.addEventListener('submit', async event => {
            event.preventDefault();
            const error = document.querySelector('#join-error');
            const button = form.querySelector('button');
            button.disabled = true;
            error.textContent = '';
            try {
                const response = await fetch('?api=join', {method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(Object.fromEntries(new FormData(form)))});
                const result = await response.json();
                if (!result.ok) throw new Error(result.error);
                window.location.href = '?channel=' + encodeURIComponent(result.channel);
            } catch (requestError) {
                error.textContent = requestError.message || 'Unable to join this channel.';
                button.disabled = false;
            }
        });
    }

    const app = document.querySelector('#radio-app');
    if (!app || !window.WALKIE_SESSION) return;
    const session = window.WALKIE_SESSION;
    const status = document.querySelector('#connection-status');
    const light = document.querySelector('#status-light');
    const name = document.querySelector('#channel-name');
    const count = document.querySelector('#user-count');
    const list = document.querySelector('#participant-list');
    const speakerName = document.querySelector('#speaker-name');
    const speakerLabel = document.querySelector('#speaker-label');
    let sequence = 0;
    name.textContent = session.channel || 'channel';

    const setStatus = value => { status.textContent = value; light.className = value.toLowerCase(); };
    const post = async (action, extra = {}) => {
        const response = await fetch('?api=' + action, {method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({action, token: session.token, csrf: app.dataset.csrf, ...extra})});
        const result = await response.json();
        if (!result.ok) throw new Error(result.error);
        return result;
    };
    const render = state => {
        if (!state) return;
        const peers = state.peers || [];
        count.textContent = peers.length;
        list.innerHTML = peers.map(peer => `<div class="participant"><span class="avatar">${escapeHtml(peer.nickname.slice(0, 1).toUpperCase())}</span><div><strong>${escapeHtml(peer.nickname)}</strong><small>${state.speaker?.peer === peer.id ? 'TRANSMITTING' : 'LISTENING'}</small></div><i class="${state.speaker?.peer === peer.id ? 'speaking' : ''}"></i></div>`).join('');
        window.dispatchEvent(new CustomEvent('walkie:peers', {detail: peers.filter(peer => peer.id !== session.peer)}));
        if (state.speaker) { speakerName.textContent = state.speaker.peer === session.peer ? 'You are transmitting' : state.speaker.nickname + ' is talking'; speakerLabel.textContent = 'LIVE NOW'; } else { speakerName.textContent = 'Waiting for a speaker'; speakerLabel.textContent = 'ON AIR'; }
    };
    const escapeHtml = value => value.replace(/[&<>'"]/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[char]));
    const poll = async () => { try { const result = await post('poll', {after: sequence}); setStatus('CONNECTED'); render(result.state); (result.events || []).forEach(event => { sequence = Math.max(sequence, event.id); window.dispatchEvent(new CustomEvent('walkie:event', {detail: event})); }); } catch (error) { setStatus('RECONNECTING'); } };
    setStatus('CONNECTING'); poll(); const timer = setInterval(poll, 1500);
    document.querySelector('#leave-button').addEventListener('click', async () => { clearInterval(timer); try { await post('leave'); } finally { window.location.href = './'; } });
    window.walkie = {post, render, session};
})();
