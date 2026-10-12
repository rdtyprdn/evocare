<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ isset($title) ? $title.' · ' : '' }}EvoCare – Event of Care</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-gray-800">
        <div class="min-h-screen bg-gray-50 flex flex-col">
            @include('layouts.navigation')

            @isset($header)
                <header class="bg-white shadow-sm">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">{{ $header }}</div>
                </header>
            @endisset

            <div class="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8">
                @if (session('success'))
                    <div class="mt-4 rounded-md bg-green-50 border border-green-200 p-3 text-green-800">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="mt-4 rounded-md bg-red-50 border border-red-200 p-3 text-red-800">{{ session('error') }}</div>
                @endif
            </div>

            <main class="flex-1">{{ $slot }}</main>

            <footer class="bg-white border-t mt-12">
                <div class="max-w-7xl mx-auto px-4 py-6 text-sm text-gray-500">© {{ date('Y') }} EvoCare – Event of Care. Ekosistem digital untuk kegiatan pengabdian masyarakat.</div>
            </footer>
        </div>
    </body>
</html>
