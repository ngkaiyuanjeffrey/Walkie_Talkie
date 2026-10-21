(() => {
    const peers = new Map();
    const config = {iceServers: [{urls: 'stun:stun.l.google.com:19302'}, {urls: 'stun:stun1.l.google.com:19302'}]};
    const remoteAudio = document.querySelector('#remote-audio');
    const createConnection = peerId => {
        if (peers.has(peerId)) return peers.get(peerId);
        const connection = new RTCPeerConnection(config);
        peers.set(peerId, connection);
        connection.ontrack = event => { remoteAudio.srcObject = event.streams[0]; };
        connection.onicecandidate = event => event.candidate && window.walkie.post('signal', {to: peerId, data: {candidate: event.candidate}}).catch(() => {});
        return connection;
    };
    window.addEventListener('walkie:peers', ({detail}) => detail.forEach(peer => createConnection(peer.id)));
    window.addEventListener('walkie:event', async ({detail}) => {
        if (detail.type !== 'signal' || !window.walkie) return;
        const connection = createConnection(detail.from);
        if (detail.data?.offer) { await connection.setRemoteDescription(detail.data.offer); const answer = await connection.createAnswer(); await connection.setLocalDescription(answer); await window.walkie.post('signal', {to: detail.from, data: {answer}}); }
        if (detail.data?.answer) await connection.setRemoteDescription(detail.data.answer);
        if (detail.data?.candidate) await connection.addIceCandidate(detail.data.candidate);
    });
    window.addEventListener('walkie:stream', async ({detail: stream}) => {
        for (const [peerId, connection] of peers) {
            stream.getTracks().forEach(track => connection.addTrack(track, stream));
            const offer = await connection.createOffer(); await connection.setLocalDescription(offer);
            window.walkie.post('signal', {to: peerId, data: {offer}}).catch(() => {});
        }
    });
})();
