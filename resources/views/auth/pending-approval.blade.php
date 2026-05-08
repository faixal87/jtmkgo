<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Pending Approval - JTMK Go!</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-white font-sans text-zinc-950 antialiased">
        <main class="flex min-h-screen items-center justify-center px-4 py-10 sm:px-6 lg:px-8">
            <section class="w-full max-w-lg rounded-xl border border-zinc-200 bg-white p-8 text-center shadow-sm">
                <div class="flex justify-center">
                    <x-application-logo class="h-14 w-auto opacity-70 grayscale" />
                </div>

                <p class="mt-8 text-sm font-medium text-zinc-500">JTMK Go! - pulut-sekaya</p>
                <h1 class="mt-3 text-2xl font-semibold tracking-tight text-zinc-950">Your registration has been received.</h1>

                <p class="mt-4 text-sm leading-6 text-zinc-500">
                    Your account is waiting for administrator review. You will be able to access JTMK Go! after approval.
                </p>

                <div class="mt-6 rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-600">
                    Please contact the administrator if your account needs urgent approval.
                </div>

                <form method="POST" action="{{ route('logout') }}" class="mt-8">
                    @csrf
                    <button type="submit" class="inline-flex items-center rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 shadow-sm transition hover:bg-zinc-50 focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2">
                        Log Out
                    </button>
                </form>
            </section>
        </main>
    </body>
</html>
