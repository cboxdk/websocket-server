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
            margin-bottom: 1.5rem;
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
            background: var(--success);
            border-radius: 50%;
            box-shadow: 0 0 12px var(--success-glow);
            animation: pulse 2s ease-in-out infinite;
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

        /* API Section */
        .api-section {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            backdrop-filter: blur(10px);
        }

        .api-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .api-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .api-badge {
            font-size: 0.7rem;
            background: rgba(139, 92, 246, 0.2);
            color: #a78bfa;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-weight: 500;
        }

        .endpoint-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .endpoint {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            transition: background 0.2s;
        }

        .endpoint:hover {
            background: rgba(0, 0, 0, 0.3);
        }

        .method {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.7rem;
            font-weight: 600;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            min-width: 52px;
            text-align: center;
        }

        .method-get { background: rgba(16, 185, 129, 0.2); color: #34d399; }
        .method-post { background: rgba(59, 130, 246, 0.2); color: #60a5fa; }
        .method-put { background: rgba(251, 191, 36, 0.2); color: #fbbf24; }
        .method-delete { background: rgba(239, 68, 68, 0.2); color: #f87171; }

        .endpoint-path {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.813rem;
            color: var(--text-secondary);
            flex: 1;
        }

        .endpoint-desc {
            font-size: 0.75rem;
            color: var(--text-muted);
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

        /* Code block */
        .code-example {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
            overflow-x: auto;
        }

        .code-example code {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.75rem;
            color: var(--text-secondary);
            white-space: pre;
        }

        .code-example .comment { color: var(--text-muted); }
        .code-example .string { color: #34d399; }
        .code-example .key { color: #60a5fa; }
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

            <div class="status-badge">
                <span class="status-dot"></span>
                <span class="status-text">Online</span>
            </div>
        </header>

        <div class="cards-grid">
            <div class="card">
                <div class="card-label">WebSocket Endpoint</div>
                <div class="card-value">ws://{{ request()->getHost() }}:8080/app/{key}</div>
            </div>
            <div class="card">
                <div class="card-label">Protocol</div>
                <div class="card-value">Pusher Protocol v7</div>
            </div>
            <div class="card">
                <div class="card-label">REST API</div>
                <div class="card-value">/api/apps</div>
            </div>
            <div class="card">
                <div class="card-label">Metrics</div>
                <div class="card-value">/api/metrics</div>
            </div>
        </div>

        <section class="api-section">
            <div class="api-header">
                <a href="/docs" class="api-title" style="text-decoration: none; color: inherit;">API Reference</a>
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <span class="api-badge">Bearer Token Auth</span>
                    <a href="/docs" class="api-badge" style="background: rgba(6, 182, 212, 0.2); color: #22d3ee; text-decoration: none;">Open Docs →</a>
                </div>
            </div>
            <div class="endpoint-list">
                <div class="endpoint">
                    <span class="method method-get">GET</span>
                    <span class="endpoint-path">/api/apps</span>
                    <span class="endpoint-desc">List all applications</span>
                </div>
                <div class="endpoint">
                    <span class="method method-post">POST</span>
                    <span class="endpoint-path">/api/apps</span>
                    <span class="endpoint-desc">Create new application</span>
                </div>
                <div class="endpoint">
                    <span class="method method-get">GET</span>
                    <span class="endpoint-path">/api/apps/{id}</span>
                    <span class="endpoint-desc">Get application details</span>
                </div>
                <div class="endpoint">
                    <span class="method method-put">PUT</span>
                    <span class="endpoint-path">/api/apps/{id}</span>
                    <span class="endpoint-desc">Update application</span>
                </div>
                <div class="endpoint">
                    <span class="method method-delete">DEL</span>
                    <span class="endpoint-path">/api/apps/{id}</span>
                    <span class="endpoint-desc">Delete application</span>
                </div>
                <div class="endpoint">
                    <span class="method method-post">POST</span>
                    <span class="endpoint-path">/api/apps/{id}/regenerate-secret</span>
                    <span class="endpoint-desc">Regenerate app secret</span>
                </div>
                <div class="endpoint">
                    <span class="method method-get">GET</span>
                    <span class="endpoint-path">/api/metrics</span>
                    <span class="endpoint-desc">Prometheus metrics</span>
                </div>
            </div>

            <div class="code-example">
<code><span class="comment"># Create a new application</span>
curl -X POST {{ url('/api/apps') }} \
  -H "<span class="key">Authorization</span>: <span class="string">Bearer YOUR_TOKEN</span>" \
  -H "<span class="key">Content-Type</span>: <span class="string">application/json</span>" \
  -d '{"<span class="key">name</span>": "<span class="string">My App</span>"}'</code>
            </div>
        </section>

        <div class="cta-container">
            <a href="https://cbox.dk" class="cta-button cta-primary" target="_blank">
                Visit Cbox.dk
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6M15 3h6v6M10 14L21 3"/>
                </svg>
            </a>
            <a href="/api/metrics" class="cta-button cta-secondary">
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
</body>
</html>
