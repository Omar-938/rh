<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- PWA --}}
    <meta name="theme-color" content="#1B4F72">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="SimpliRH">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="application-name" content="SimpliRH">
    <meta name="msapplication-TileColor" content="#1B4F72">
    <meta name="msapplication-tap-highlight" content="no">

    {{-- Icônes --}}
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" href="/icons/favicon.ico" sizes="any">
    <link rel="icon" href="/icons/icon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/icons/icon-152x152.png">
    <link rel="apple-touch-icon" sizes="192x192" href="/icons/icon-192x192.png">

    {{-- Polices (preconnect pour performance) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,300..700;1,14..32,300..400&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">

    @routes
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>
<body class="h-full antialiased">
    @inertia

    {{-- Service Worker PWA --}}
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js', { scope: '/' })
                    .then(reg => {
                        // Vérifier les mises à jour toutes les 60 secondes
                        setInterval(() => reg.update(), 60000);
                    })
                    .catch(() => {});
            });
        }
    </script>
</body>
</html>
