# Walkie Talkie

A small, voice-only browser walkie-talkie built with PHP 8.3+, vanilla JavaScript, WebRTC, locked JSON room files, and a service worker. Guests choose a nickname and channel, then hold a push-to-talk button. The signaling API enforces one speaker at a time and expires inactive peers.

## Features

- Guest nickname and normalized channel names
- HMAC-SHA256 session tokens
- JSON room storage with file locking
- Presence polling, participant status, speaker floor, and a 30-second floor timeout
- WebRTC audio with echo cancellation, noise suppression, and STUN configuration
- Push-to-talk through pointer, touch, and Spacebar input
- Microphone permission handling and audio level animation
- Responsive radio interface and installable PWA shell
- No database, accounts, Node.js, or paid service required

## Requirements

- PHP 8.3 or newer with sessions enabled
- Apache with `mod_rewrite` (XAMPP works)
- Current Chrome, Edge, or Firefox
- HTTPS for microphone access outside localhost

## XAMPP installation

1. Copy this folder to `C:\xampp\htdocs\Walkie Talkie`.
2. Copy `.env.example` to `.env` and replace `SIGNAL_SECRET` with a long random value.
3. Start Apache in the XAMPP control panel.
4. Open `http://localhost/Walkie%20Talkie/`.
5. Open the same channel in a second browser/device and allow microphone access when transmitting.

Apache must be allowed to override `.htaccess`. The `storage/rooms` and `storage/logs` folders must be writable by Apache on Linux or cPanel.

## Mobile testing

Use the computer's LAN IP, for example `https://192.168.1.20/Walkie%20Talkie/`. Phones must be on the same Wi-Fi. Browsers generally require HTTPS for microphone access; localhost is the main development exception. For production, configure a valid TLS certificate.

## PWA installation

Open the site over HTTPS, then use Chrome/Edge's install action or Safari's Share menu and **Add to Home Screen**. The service worker caches the application shell but never caches API signaling responses.

## Deployment

For cPanel, upload the project, point the document root at this folder, create `.env` from `.env.example`, enable rewrite support, and grant the web server write access to `storage/rooms` and `storage/logs`. Never publish `.env` or use the development fallback secret. A TURN server is recommended for restrictive networks; STUN alone cannot guarantee WebRTC connectivity everywhere.

## API outline

All API calls are POST JSON requests to `?api=...` and include the session token and CSRF value. Supported actions are `join`, `poll`, `ptt_request`, `ptt_release`, `signal`, and `leave`. WebRTC descriptions and ICE candidates are relayed through the locked room event queue.

## Troubleshooting

- **Microphone unavailable:** use HTTPS, grant permission, and check that another application is not holding the device.
- **Channel full:** raise `SIGNAL_MAX_PEERS` only when the host can support the extra peers.
- **WebRTC fails:** test on the same LAN first, then add a TURN server for cross-network reliability.
- **Signaling unavailable:** check Apache, PHP sessions, rewrite rules, and folder permissions.
- **PWA does not install:** use HTTPS and reload once after service-worker registration.
- **Phone cannot connect:** use the host LAN IP, open the firewall port, and keep both devices on the same network.

## Testing checklist

Test one user, two users, competing PTT requests, release and reacquire, leave cleanup, reconnect polling, denied microphone permission, and the configured maximum peer count. PHP syntax can be checked with `php -l`; browser behavior should be tested in two browser windows with the same channel.
