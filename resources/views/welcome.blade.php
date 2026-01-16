<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cbox WebSocket Server</title>
    <link rel="icon" type="image/png" href="/assets/cbox-icon.png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            color: #fff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .container {
            max-width: 800px;
            text-align: center;
        }
        .logo {
            margin: 0 auto 2rem;
            max-width: 280px;
        }
        .logo img {
            width: 100%;
            height: auto;
        }
        h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #00d9ff 0%, #0066ff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .subtitle {
            font-size: 1.25rem;
            color: #a0aec0;
            margin-bottom: 3rem;
        }
        .status {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            margin-bottom: 3rem;
        }
        .status-dot {
            width: 10px;
            height: 10px;
            background: #10b981;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .status-text {
            color: #10b981;
            font-weight: 500;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        .info-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: left;
        }
        .info-card-title {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #64748b;
            margin-bottom: 0.5rem;
        }
        .info-card-value {
            font-family: 'SF Mono', Monaco, 'Courier New', monospace;
            font-size: 0.875rem;
            color: #00d9ff;
            word-break: break-all;
        }
        .cta-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, #00d9ff 0%, #0066ff 100%);
            color: #fff;
            text-decoration: none;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.125rem;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 10px 40px rgba(0, 102, 255, 0.3);
        }
        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 50px rgba(0, 102, 255, 0.4);
        }
        .cta-button svg {
            width: 20px;
            height: 20px;
        }
        footer {
            margin-top: 3rem;
            color: #64748b;
            font-size: 0.875rem;
        }
        footer a {
            color: #00d9ff;
            text-decoration: none;
        }
        footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <img src="/assets/cbox-logo-negative.png" alt="Cbox Logo">
        </div>

        <h1>Cbox WebSocket Server</h1>
        <p class="subtitle">Real-time communication powered by Laravel Reverb</p>

        <div class="status">
            <span class="status-dot"></span>
            <span class="status-text">Server Online</span>
        </div>

        <div class="info-grid">
            <div class="info-card">
                <div class="info-card-title">WebSocket URL</div>
                <div class="info-card-value">ws://{{ request()->getHost() }}:{{ config('reverb.servers.reverb.port', 8080) }}/app/{app_key}</div>
            </div>
            <div class="info-card">
                <div class="info-card-title">Protocol</div>
                <div class="info-card-value">Pusher Protocol</div>
            </div>
            <div class="info-card">
                <div class="info-card-title">API Endpoint</div>
                <div class="info-card-value">/api/apps</div>
            </div>
            <div class="info-card">
                <div class="info-card-title">Metrics</div>
                <div class="info-card-value">/api/metrics</div>
            </div>
        </div>

        <a href="https://cbox.dk" class="cta-button" target="_blank" rel="noopener noreferrer">
            Visit Cbox.dk
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6M15 3h6v6M10 14L21 3"/>
            </svg>
        </a>
    </div>

    <footer>
        Powered by <a href="https://laravel.com/docs/reverb" target="_blank">Laravel Reverb</a>
    </footer>
</body>
</html>
