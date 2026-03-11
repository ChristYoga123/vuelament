<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- [FIX] CSRF token untuk Axios / fetch manual jika dibutuhkan --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- [FIX] Referrer Policy — jangan kirim URL lengkap ke third-party --}}
    <meta name="referrer" content="strict-origin-when-cross-origin">

    {{-- [FIX] X-Content-Type-Options via meta (browser hints) --}}
    <meta http-equiv="X-Content-Type-Options" content="nosniff">

    {{-- [FIX] Hindari caching halaman auth di browser --}}
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">

    {{--
        [FIX] Security headers berikut HARUS diset via HTTP response headers (bukan meta tag)
        karena meta tag tidak cukup untuk perlindungan penuh.

        Tambahkan middleware di bootstrap/app.php atau Kernel.php:

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        Atau gunakan package: https://github.com/bepsvpt/secure-headers
    --}}

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>

<body>
    @inertia
</body>

</html>
