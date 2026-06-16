<!-- Header -->
<header class="header-responsive px-5 md:px-6 py-3 flex items-center justify-between fixed top-0 left-0 md:left-72 right-0 z-20" style="background: #072449;">
    <div class="flex items-center gap-3 min-w-0">
        <!-- Botón hamburguesa para móviles -->
        <button @click="sidebarOpen = !sidebarOpen" class="md:hidden text-white hover:bg-white/10 p-2 -ml-2 rounded-lg transition-colors flex-shrink-0">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>
        <h1 class="text-lg font-semibold text-white truncate">@yield('title', 'Panel administrativo')</h1>
    </div>
    <div class="flex items-center gap-3 flex-shrink-0">
        <span class="hidden sm:flex items-center gap-2 text-xs" style="color: #9db8dd;">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
            Hospital Universitario del Valle
        </span>
    </div>
</header>
