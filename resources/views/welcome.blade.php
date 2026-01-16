<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cbox WebSocket Server</title>
    <link rel="icon" type="image/png" href="/assets/cbox-icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg-primary: #0a0f1a;
            --bg-secondary: #111827;
            --bg-card: rgba(17, 24, 39, 0.8);
            --border-color: rgba(55, 65, 81, 0.5);
            --text-primary: #f9fafb;
            --text-secondary: #9ca3af;
            --text-muted: #6b7280;
            --accent: #06b6d4;
            --accent-glow: rgba(6, 182, 212, 0.4);
            --success: #10b981;
            --success-glow: rgba(16, 185, 129, 0.3);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            background: var(--bg-primary);
            background-image:
                radial-gradient(ellipse 80% 50% at 50% -20%, rgba(6, 182, 212, 0.15), transparent),
                radial-gradient(ellipse 60% 40% at 100% 100%, rgba(139, 92, 246, 0.1), transparent);
            color: var(--text-primary);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2rem;
            line-height: 1.6;
        }

        .container {
            max-width: 900px;
            width: 100%;
        }

        /* Header */
        .header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .logo {
            max-width: 200px;
            margin: 0 auto 1.5rem;
        }

        .logo img {
            width: 100%;
            height: auto;
            filter: drop-shadow(0 0 30px var(--accent-glow));
        }

        .title {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #06b6d4 0%, #3b82f6 50%, #8b5cf6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
            letter-spacing: -0.02em;
        }

        .subtitle {
            font-size: 1.125rem;
            color: var(--text-secondary);
            font-weight: 400;
        }

        /* Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            padding: 0.5rem 1.25rem;
            border-radius: 9999px;
            margin: 2rem 0;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            background: var(--text-muted);
            border-radius: 50%;
        }

        .status-dot.online {
            background: var(--success);
            box-shadow: 0 0 12px var(--success-glow);
            animation: pulse 2s ease-in-out infinite;
        }

        .status-dot.offline {
            background: #ef4444;
            box-shadow: 0 0 12px rgba(239, 68, 68, 0.3);
        }

        .status-badge.offline {
            background: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.3);
        }

        .status-badge.offline .status-text {
            color: #ef4444;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(1.1); }
        }

        .status-text {
            color: var(--success);
            font-weight: 500;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Cards Grid */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }

        @media (max-width: 600px) {
            .cards-grid { grid-template-columns: 1fr; }
            .title { font-size: 1.75rem; }
        }

        .card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.25rem;
            backdrop-filter: blur(10px);
            transition: all 0.2s ease;
        }

        .card:hover {
            border-color: rgba(6, 182, 212, 0.3);
            box-shadow: 0 0 30px rgba(6, 182, 212, 0.1);
        }

        a.card-link {
            text-decoration: none;
            cursor: pointer;
        }

        a.card-link:hover {
            border-color: var(--accent);
            transform: translateY(-2px);
        }

        a.card-link:hover .card-value {
            color: #22d3ee;
        }

        .card-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .card-value {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.875rem;
            color: var(--accent);
            word-break: break-all;
        }

        /* CTA Button */
        .cta-container {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 2rem;
        }

        .cta-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.875rem 1.75rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.938rem;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .cta-primary {
            background: linear-gradient(135deg, #06b6d4 0%, #3b82f6 100%);
            color: white;
            box-shadow: 0 4px 20px rgba(6, 182, 212, 0.3);
        }

        .cta-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 30px rgba(6, 182, 212, 0.4);
        }

        .cta-secondary {
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
        }

        .cta-secondary:hover {
            border-color: var(--accent);
            color: var(--accent);
        }

        .cta-button svg {
            width: 18px;
            height: 18px;
        }

        /* Footer */
        footer {
            text-align: center;
            color: var(--text-muted);
            font-size: 0.813rem;
        }

        footer a {
            color: var(--accent);
            text-decoration: none;
            transition: color 0.2s;
        }

        footer a:hover {
            color: #22d3ee;
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="logo">
                <img src="/assets/cbox-logo-negative.png" alt="Cbox">
            </div>
            <h1 class="title">WebSocket Server</h1>
            <p class="subtitle">Real-time communication powered by Laravel Reverb</p>

            <div class="status-badge" id="status-badge">
                <span class="status-dot" id="status-dot"></span>
                <span class="status-text" id="status-text">Checking...</span>
            </div>
        </header>

        <div class="cards-grid">
            <div class="card">
                <div class="card-label">Protocol</div>
                <div class="card-value">Pusher Protocol v7</div>
            </div>
            <a href="/docs" class="card card-link">
                <div class="card-label">API Docs</div>
                <div class="card-value">/docs</div>
            </a>
            <a href="/metrics" class="card card-link">
                <div class="card-label">Metrics</div>
                <div class="card-value">/metrics</div>
            </a>
            <a href="/health" class="card card-link">
                <div class="card-label">Health Check</div>
                <div class="card-value">/health</div>
            </a>
        </div>

        <div class="cta-container">
            <a href="https://cbox.dk" class="cta-button cta-primary" target="_blank">
                Visit Cbox.dk
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6M15 3h6v6M10 14L21 3"/>
                </svg>
            </a>
            <a href="/metrics" class="cta-button cta-secondary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 3v18h18M9 17V9m4 8v-5m4 5V6"/>
                </svg>
                View Metrics
            </a>
        </div>

        <footer>
            Powered by <a href="https://laravel.com/docs/reverb" target="_blank">Laravel Reverb</a>
            &middot; <a href="https://github.com/cboxdk/websocket-server" target="_blank">GitHub</a>
        </footer>
    </div>

    <script>
        async function checkHealth() {
            const badge = document.getElementById('status-badge');
            const dot = document.getElementById('status-dot');
            const text = document.getElementById('status-text');

            try {
                const response = await fetch('/health', { method: 'GET', timeout: 5000 });
                const data = await response.json();

                if (response.ok && data.status === 'healthy') {
                    badge.classList.remove('offline');
                    dot.classList.add('online');
                    dot.classList.remove('offline');
                    text.textContent = 'Online';
                } else {
                    throw new Error('Unhealthy');
                }
            } catch (e) {
                badge.classList.add('offline');
                dot.classList.add('offline');
                dot.classList.remove('online');
                text.textContent = 'Offline';
            }
        }

        checkHealth();
        setInterval(checkHealth, 30000);
    </script>
</body>
</html>
