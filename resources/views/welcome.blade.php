<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'miam') }} — Suivez vos calories, atteignez vos objectifs</title>

        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-miam-mint font-sans text-miam-ink antialiased">
        <div class="mx-auto flex min-h-screen max-w-6xl flex-col px-6 py-8">
            <header class="flex items-center justify-between">
                <a href="/" class="flex items-center gap-2.5">
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-white shadow-sm ring-1 ring-miam-green/10">
                        <svg viewBox="0 0 64 64" class="h-9 w-9" role="img" aria-label="Logo miam">
                            <path d="M32 22V12" stroke="#2e7d32" stroke-width="2.6" stroke-linecap="round" fill="none"/>
                            <path d="M31 15c-1-4.4-5-7-9.4-6.2.4 4.5 4.4 7.4 9.4 6.2z" fill="#2e7d32"/>
                            <path d="M33 15c1-3.8 4.6-6.1 8.4-5.4-.5 3.9-4 6.3-8.4 5.4z" fill="#8bcf9a"/>
                            <g fill="#66bb6a">
                                <circle cx="25.5" cy="33.5" r="12.8"/>
                                <circle cx="38.5" cy="33.5" r="12.8"/>
                                <rect x="20.5" y="20" width="23" height="26" rx="11.5"/>
                            </g>
                            <g fill="#ffffff">
                                <rect x="25.3" y="25" width="2.2" height="8" rx="1.1"/>
                                <rect x="30.9" y="25" width="2.2" height="8" rx="1.1"/>
                                <rect x="36.5" y="25" width="2.2" height="8" rx="1.1"/>
                                <path d="M25 32.4h14c0 2.9-1.6 5.4-4 6.5l-1 11.8c-.15 1.75-2.7 1.75-2.85 0l-1-11.8c-2.4-1.1-4-3.6-4-6.5z"/>
                            </g>
                        </svg>
                    </span>
                    <span class="font-display text-2xl font-bold text-miam-green">Miam</span>
                </a>

                <nav class="flex items-center gap-3 text-sm">
                    @auth
                        <a href="{{ url('/dashboard') }}"
                           class="inline-flex items-center rounded-xl bg-miam-green px-4 py-2 font-medium text-white transition hover:bg-miam-green/90">
                            Tableau de bord
                        </a>
                    @else
                        @if (Route::has('login'))
                            <a href="{{ route('login') }}"
                               class="inline-flex items-center rounded-xl border border-miam-green bg-white px-4 py-2 font-medium text-miam-green transition hover:bg-white/70">
                                Connexion
                            </a>
                        @endif
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}"
                               class="inline-flex items-center rounded-xl bg-miam-green px-4 py-2 font-medium text-white transition hover:bg-miam-green/90">
                                Créer un compte
                            </a>
                        @endif
                    @endauth
                </nav>
            </header>

            <main class="flex flex-1 items-center py-12">
                <div class="grid w-full items-center gap-10 lg:grid-cols-2 lg:gap-16">
                    <div>
                        <h1 class="font-display text-5xl font-bold leading-tight text-miam-green sm:text-6xl">
                            Miam
                        </h1>
                        <p class="mt-4 max-w-md text-lg leading-relaxed text-miam-ink/80">
                            Suivez vos calories, atteignez vos objectifs. Le suivi du poids et de
                            l'alimentation, simple, sur ordinateur comme sur smartphone.
                        </p>

                        <div class="mt-8 flex flex-wrap items-center gap-4">
                            <a href="{{ route('laraskel.sante') }}"
                               class="inline-flex items-center justify-center rounded-xl bg-miam-green px-6 py-3 font-medium text-white shadow-sm transition hover:bg-miam-green/90">
                                Vérifier l'installation
                            </a>
                        </div>

                        <ul class="mt-10 flex flex-col gap-2.5 text-sm text-miam-ink/70">
                            @foreach (['Naturel, frais et rassurant', 'Équilibre et bien-être', 'Approche positive et motivante'] as $point)
                                <li class="flex items-center gap-2.5">
                                    <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-miam-green-pale/50 text-miam-green">
                                        <svg class="h-3 w-3" viewBox="0 0 12 12" fill="none">
                                            <path d="M2.5 6.5 5 9l4.5-5.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </span>
                                    {{ $point }}
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="order-first lg:order-last">
                        <div class="overflow-hidden rounded-3xl bg-white shadow-xl ring-1 ring-miam-green/10">
                            <img src="{{ asset('images/hero-aliments-frais.png') }}"
                                 alt="Assiette d'aliments frais et sains"
                                 class="w-full object-cover">
                        </div>
                    </div>
                </div>
            </main>

            <footer class="border-t border-miam-green/10 pt-6 text-center text-sm text-miam-ink/60">
                {{ config('app.name', 'miam') }} — suivi de poids &amp; de calories · propulsé par Laraskel
            </footer>
        </div>
    </body>
</html>
