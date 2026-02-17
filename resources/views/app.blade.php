<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#0f172a">
        <meta name="description" content="ERP Manufacturing - Enterprise Resource Planning for Manufacturing Industry">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title inertia>{{ config('app.name', 'ERP Manufacturing') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800|orbitron:400,500,700,900&display=swap" rel="stylesheet" />

        <!-- Branding & PWA -->
        <link rel="icon" type="image/x-icon" href="/favicon.ico?v=2">
        <link rel="apple-touch-icon" href="/icons/icon-192x192.png">
        <link rel="manifest" href="/manifest.webmanifest">

        <!-- Scripts -->
        @php echo app(\Tighten\Ziggy\BladeRouteGenerator::class)->generate(); @endphp
        @vite(['resources/js/app.js'])
        @inertiaHead
        
        <!-- Theme Flash Prevention -->
        <script>
            if (localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        </script>
    </head>
    <body class="font-sans antialiased bg-white dark:bg-slate-950 text-slate-900 dark:text-slate-100 transition-colors duration-300">
        @inertia
    </body>
</html>
