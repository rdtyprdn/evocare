<?php

return [

    /*
     | Proxy yang dipercaya untuk membaca header X-Forwarded-* (skema HTTPS, host, IP asli).
     | Bawaan '*' mempercayai proxy pemanggil, diperlukan saat EvoCare dijalankan lewat
     | Cloudflare Tunnel. Isi daftar IP atau CIDR dipisah koma untuk membatasinya.
     */
    'proxies' => env('TRUSTED_PROXIES', '*'),

];
