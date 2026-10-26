<!-- Header: blanco y delgado; el azul institucional queda en el menú (estilos: layouts/admin.blade.php, bloque 8) -->
<header class="header-responsive barra-superior px-5 md:px-6 py-3 flex items-center justify-between fixed top-0 left-0 md:left-72 right-0 z-20">
    <div class="flex items-center gap-3 min-w-0">
        <!-- Botón hamburguesa para móviles -->
        <button @click="sidebarOpen = !sidebarOpen" :aria-expanded="sidebarOpen" aria-controls="admin-sidebar" aria-label="Abrir menú de navegación" class="barra-superior__menu md:hidden flex-shrink-0">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>
        <p class="barra-superior__titulo truncate">@yield('title', 'Panel administrativo')</p>
    </div>
    <span class="barra-superior__hospital hidden sm:flex flex-shrink-0">Hospital Universitario del Valle</span>
</header>
