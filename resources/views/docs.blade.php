<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>API Documentation - Cbox WebSocket Server</title>
    <link rel="icon" type="image/png" href="/assets/cbox-icon.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body { margin: 0; }
    </style>
</head>
<body>
    <redoc
        spec-url="/openapi.yaml"
        hide-hostname
        theme='{
            "colors": {
                "primary": { "main": "#06b6d4" },
                "success": { "main": "#10b981" },
                "warning": { "main": "#f59e0b" },
                "error": { "main": "#ef4444" },
                "text": { "primary": "#f9fafb", "secondary": "#9ca3af" },
                "http": {
                    "get": "#10b981",
                    "post": "#3b82f6",
                    "put": "#f59e0b",
                    "delete": "#ef4444"
                }
            },
            "typography": {
                "fontFamily": "Inter, -apple-system, BlinkMacSystemFont, sans-serif",
                "headings": { "fontFamily": "Inter, -apple-system, sans-serif" },
                "code": { "fontFamily": "JetBrains Mono, monospace" }
            },
            "sidebar": {
                "backgroundColor": "#0a0f1a",
                "textColor": "#9ca3af",
                "activeTextColor": "#06b6d4"
            },
            "rightPanel": {
                "backgroundColor": "#111827"
            }
        }'
    ></redoc>
    <script src="https://cdn.redoc.ly/redoc/latest/bundles/redoc.standalone.js"></script>
</body>
</html>
