<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') — Product Configurator</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="h-full flex items-center justify-center" style="background:#f1f5f9">
    <div class="w-full max-w-md px-6">
        {{-- Card --}}
        <div class="bg-white rounded-2xl shadow-sm border p-8" style="border-color:#e2e8f0">
            {{-- Logo --}}
            <div class="flex justify-center mb-8">
                <img src="/logo_black.png" alt="Product Configurator" class="h-10 object-contain">
            </div>
            @yield('content')
        </div>
        <p class="text-center text-xs text-slate-400 mt-6">
            &copy; {{ date('Y') }} Hectronic India Pvt Ltd. All rights reserved.
        </p>
    </div>
</body>

</html>
