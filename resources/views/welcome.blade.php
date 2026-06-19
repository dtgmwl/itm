<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Portal Layanan IT | IT Service Management</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @filamentStyles
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        @keyframes pulse-soft {
            0%, 100% { opacity: .15; }
            50% { opacity: .3; }
        }

        .animate-float {
            animation: float 6s ease-in-out infinite;
        }

        .animate-pulse-soft {
            animation: pulse-soft 4s ease-in-out infinite;
        }

        .bg-grid {
            background-image:
                linear-gradient(rgba(99, 102, 241, .03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(99, 102, 241, .03) 1px, transparent 1px);
            background-size: 60px 60px;
        }
    </style>
</head>
<body class="antialiased bg-gradient-to-br from-slate-50 via-indigo-50/30 to-slate-50 text-slate-900 min-h-screen">
    <div class="relative min-h-screen overflow-hidden">
        {{-- Decorative Background --}}
        <div class="absolute inset-0 bg-grid pointer-events-none"></div>
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-indigo-200/30 rounded-full blur-3xl animate-pulse-soft pointer-events-none"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-sky-200/30 rounded-full blur-3xl animate-pulse-soft pointer-events-none" style="animation-delay: 2s;"></div>
        <div class="absolute top-1/3 left-1/4 w-64 h-64 bg-violet-200/20 rounded-full blur-3xl animate-pulse-soft pointer-events-none" style="animation-delay: 1s;"></div>

        {{-- Floating Decorations --}}
        <div class="hidden lg:block absolute top-20 right-[15%] w-3 h-3 bg-indigo-400/40 rounded-full animate-float pointer-events-none"></div>
        <div class="hidden lg:block absolute top-60 left-[10%] w-2 h-2 bg-sky-400/40 rounded-full animate-float pointer-events-none" style="animation-delay: 1s;"></div>
        <div class="hidden lg:block absolute bottom-40 right-[20%] w-2.5 h-2.5 bg-violet-400/40 rounded-full animate-float pointer-events-none" style="animation-delay: 2s;"></div>

        <div class="relative py-6 px-4 lg:py-10">
            <div class="max-w-5xl mx-auto">

                {{-- Header --}}
                <div class="text-center mb-10 lg:mb-14">
                    <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/70 backdrop-blur-sm border border-indigo-100 text-indigo-600 text-xs font-semibold mb-5 uppercase tracking-wider shadow-sm">
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-pulse"></span>
                        IT Support Center
                    </div>
                    <h1 class="text-4xl lg:text-5xl font-extrabold text-slate-800 tracking-tight leading-tight">
                        Portal Layanan &amp; Helpdesk
                    </h1>
                    <p class="text-slate-500 mt-3 text-lg max-w-xl mx-auto leading-relaxed">
                        Pusat bantuan teknis dan pengajuan layanan Informatika <br> cepat, terpadu, transparan.
                    </p>
                </div>

                {{-- Main Card --}}
                <div class="bg-white/70 backdrop-blur-xl shadow-[0_8px_40px_rgba(0,0,0,.04)] rounded-3xl border border-white/80 overflow-hidden mb-8 transition-all duration-300 hover:shadow-[0_8px_50px_rgba(8,112,184,.07)]">
                    <div class="grid lg:grid-cols-5 divide-y lg:divide-y-0 lg:divide-x divide-slate-100">

                        {{-- Form Pengajuan --}}
                        <div class="lg:col-span-3 p-6 lg:p-10">
                            <div class="flex items-center gap-3 mb-7">
                                <div class="p-2.5 bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl text-white shadow-md shadow-indigo-200">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-xl font-bold text-slate-800">Buat Tiket Baru</h2>
                                    <p class="text-sm text-slate-400">Laporkan kendala atau ajukan permintaan layanan.</p>
                                </div>
                            </div>

                            <livewire:public-service-catalog />
                        </div>

                        {{-- Admin Access / Logged In --}}
                        <div class="lg:col-span-2 bg-gradient-to-b from-indigo-50/40 to-white/40 p-6 lg:p-10">
                            @auth
                                <div class="h-full flex flex-col items-center justify-center text-center py-8">
                                    <div class="w-20 h-20 bg-white shadow-lg border border-slate-100 rounded-2xl flex items-center justify-center mb-5 text-amber-500 ring-4 ring-white">
                                        <svg class="w-9 h-9" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                    </div>
                                    <h3 class="font-bold text-slate-800 text-lg">Halo, {{ auth()->user()->name }}</h3>
                                    <p class="text-sm text-slate-500 mt-1 mb-7">Anda sudah terautentikasi.</p>

                                    <a href="{{ filament()->getUrl() }}"
                                        class="inline-flex items-center justify-center gap-2 py-2.5 px-7 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-700 hover:to-indigo-600 shadow-md shadow-indigo-200/50 hover:shadow-lg hover:shadow-indigo-200/60 transition-all duration-200 active:scale-[.97]">
                                        Ke Dashboard
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                        </svg>
                                    </a>
                                </div>
                            @else
                                <div class="h-full flex flex-col items-center text-center justify-center py-4">
                                    <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-2xl flex items-center justify-center text-white shadow-md shadow-indigo-200 mb-4">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-lg font-bold text-slate-800">Akses Admin</h3>
                                    <p class="text-sm text-slate-500 mt-1 mb-6 leading-relaxed">Khusus staf IT &amp; pengelola sistem.</p>
                                    <livewire:admin-login />
                                </div>
                            @endauth
                        </div>
                    </div>
                </div>

                {{-- Public Task Tracking --}}
                <div class="bg-white/70 backdrop-blur-xl shadow-[0_8px_40px_rgba(0,0,0,.04)] rounded-3xl border border-white/80 overflow-hidden p-6 lg:p-8 transition-all duration-300 hover:shadow-[0_8px_50px_rgba(8,112,184,.07)]">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-2 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-lg text-white shadow-md shadow-emerald-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-800">Lacak Tiket</h2>
                            <p class="text-sm text-slate-400">Pantau status permintaan yang sudah diajukan.</p>
                        </div>
                    </div>
                    <livewire:public-task-list />

                    <div class="mt-6 bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                        @livewire('App\Filament\Widgets\PublicTaskTypeChart')
                    </div>
                </div>

                {{-- Footer --}}
                <div class="mt-12 text-center text-slate-400 text-sm">
                    <p>&copy; {{ date('Y') }} Bidang Informatika v1.0.0</p>
                </div>

            </div>
        </div>
    </div>

    @filamentScripts
    @livewireScripts
    @livewire('notifications')
</body>
</html>
