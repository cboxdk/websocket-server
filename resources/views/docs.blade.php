<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>API Documentation - Cbox WebSocket Server</title>
    <link rel="icon" type="image/png" href="/assets/cbox-icon.png">
    <style>
        .home-button {
            position: fixed;
            top: 12px;
            right: 12px;
            z-index: 9999;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            background: rgba(30, 30, 30, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: #a0a0a0;
            text-decoration: none;
            font-family: -apple-system, BlinkMacSystemFont, sans-serif;
            font-size: 13px;
            font-weight: 500;
            backdrop-filter: blur(10px);
            transition: all 0.2s ease;
        }
        .home-button:hover {
            background: rgba(50, 50, 50, 0.95);
            color: #fff;
            border-color: rgba(255, 255, 255, 0.2);
        }
        .home-button svg {
            width: 14px;
            height: 14px;
        }
    </style>
</head>
<body>
    <a href="/" class="home-button">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M15 18l-6-6 6-6"/>
        </svg>
        Back
    </a>
    <script id="api-reference" data-url="/openapi.yaml"></script>
    <script>
        var configuration = {
            theme: 'purple',
            darkMode: true,
            hiddenClients: true,
            hideModels: false,
            hideDownloadButton: true,
            showDeveloperTools: 'never',
            metaData: {
                title: 'Cbox WebSocket Server API'
            }
        }
        document.getElementById('api-reference').dataset.configuration = JSON.stringify(configuration)
    </script>
    <script src="https://cdn.jsdelivr.net/npm/@scalar/api-reference"></script>
</body>
</html>
