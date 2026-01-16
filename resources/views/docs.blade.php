<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>API Documentation - Cbox WebSocket Server</title>
    <link rel="icon" type="image/png" href="/assets/cbox-icon.png">
    <style>
        body { margin: 0; }
    </style>
</head>
<body>
    <script id="api-reference" data-url="/openapi.yaml"></script>
    <script>
        var configuration = {
            theme: 'purple',
            darkMode: true,
            hiddenClients: ['unirest'],
            metaData: {
                title: 'Cbox WebSocket Server API',
                ogImage: '/assets/cbox-logo.png'
            },
            customCss: `
                .darklight { background: #0a0f1a !important; }
                .darklight-reference { background: #0a0f1a !important; }
            `
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/@scalar/api-reference"></script>
</body>
</html>
