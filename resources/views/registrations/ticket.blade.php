@php
    $event = $registration->event;
    $code = $registration->ticketCode();
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Tiket {{ $code }} · EvoCare</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @page { size: A4; margin: 16mm; }
        html, body { background: #f9fafb; }
        .ticket-print-sheet {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }
        @media print {
            html, body {
                background: #fff !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .no-print { display: none !important; }
            .ticket-print-sheet {
                min-height: auto !important;
                padding: 0 !important;
                background: #fff !important;
            }
            .ticket-print-card {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body class="font-sans antialiased text-gray-800"
      x-data
      @if ($autoprint)
      x-init="$nextTick(() => { setTimeout(() => window.print(), 350) })"
      @endif
>
    <div class="ticket-print-sheet min-h-screen flex flex-col items-center justify-start sm:justify-center px-4 py-8 sm:py-12">
        <div class="ticket-print-card w-full max-w-lg space-y-5">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <span class="text-2xl font-bold text-orange-600">Evo<span class="text-gray-900">Care</span></span>
                    <span class="hidden sm:inline text-xs uppercase tracking-wider text-gray-400 font-medium">Tiket Partisipasi</span>
                </div>
                <p class="font-mono text-sm font-semibold text-gray-700">{{ $code }}</p>
            </div>

            <x-participation-ticket :registration="$registration" :show-answers="true" class="shadow-sm" />

            <p class="text-center text-xs text-gray-500">
                Tunjukkan tiket ini saat hadir di kegiatan. Kode: <span class="font-mono font-semibold text-gray-700">{{ $code }}</span>
            </p>

            <div class="no-print flex flex-col-reverse sm:flex-row sm:justify-between gap-2.5 pt-2">
                <a href="{{ route('registrations.index') }}" class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                    Kembali ke Pendaftaran Saya
                </a>
                <button type="button" onclick="window.print()" class="inline-flex items-center justify-center gap-2 rounded-xl bg-orange-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-orange-700 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.244H7.231c-.598 0-1.119-.449-1.121-1.047L6.34 18m11.318 0h1.091c.54 0 .995-.409 1.04-.947l.515-6.188a2.25 2.25 0 0 0-2.239-2.43H7.34a2.25 2.25 0 0 0-2.24 2.43l.515 6.188c.045.538.5.947 1.04.947h1.092M9 11.25h6M9 7.5a3 3 0 0 1 3-3h0a3 3 0 0 1 3 3v.75H9V7.5Z"/>
                    </svg>
                    Cetak / Simpan
                </button>
            </div>
        </div>
    </div>
</body>
</html>
