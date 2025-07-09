<!-- Header -->
<header class="bg-hospital-blue text-white header-responsive px-6 py-3 flex items-center justify-between fixed top-0 left-0 md:left-72 right-0 z-20">
    <div class="flex items-center space-x-4">
        <!-- Botón hamburguesa para móviles -->
        <button @click="sidebarOpen = !sidebarOpen" class="md:hidden text-white hover:bg-hospital-blue-hover p-2 rounded">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>
        <div class="header-title text-2xl font-bold">
            Turnero<span class="text-gray-300">HUV</span>
        </div>
    </div>
    <div class="flex items-center">
        <!-- Espacio para futuros elementos si es necesario -->
    </div>
</header>
