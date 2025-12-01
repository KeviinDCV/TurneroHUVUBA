@php
    $user = auth()->user();
    $currentRoute = request()->route()->getName();
@endphp

<!-- Sidebar -->
<aside class="sidebar-responsive w-72 bg-hospital-blue text-white shadow-xl flex flex-col sidebar-full-height fixed inset-y-0 left-0 z-30 transform md:transform-none transition-transform duration-300 ease-in-out"
       :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'">

    <!-- Header del Sidebar -->
    <div class="sidebar-header p-6 border-b border-white/20 flex-shrink-0">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center shadow-md p-1">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo HUV" class="w-full h-full object-contain">
                </div>
                <div>
                    <h2 class="text-lg font-bold">HUV Admin</h2>
                    <p class="text-sm text-blue-200">Panel de Control</p>
                </div>
            </div>
            <!-- Botón cerrar para móviles -->
            <button @click="sidebarOpen = false" class="md:hidden text-white hover:bg-white/10 p-2 rounded">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    </div>

    <!-- Información del Usuario -->
    <div class="sidebar-user p-4 border-b border-white/20 bg-white/10">
        <div class="flex items-center space-x-3">
            <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center border border-white/30">
                <span class="text-sm font-medium">{{ substr($user->nombre_completo, 0, 1) }}</span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium truncate">{{ $user->nombre_completo }}</p>
                <p class="text-xs text-blue-200 truncate flex items-center">
                    <span class="w-2 h-2 bg-green-400 rounded-full mr-2"></span>
                    {{ $user->rol }}
                </p>
            </div>
        </div>
    </div>

    <!-- Navegación -->
    <div class="flex-1 overflow-y-auto sidebar-nav">
        <nav class="sidebar-nav space-y-1 p-4 pb-20">
            <!-- Inicio -->
            <a href="{{ route('admin.dashboard') }}" class="sidebar-item group w-full flex items-center justify-start {{ $currentRoute === 'admin.dashboard' ? 'bg-white/20 text-white border-l-4 border-white shadow-md relative' : 'text-blue-200 hover:text-white hover:bg-white/10' }} p-3 rounded-lg transition-all duration-200 hover:translate-x-1">
                <svg class="mr-3 h-5 w-5 {{ $currentRoute === 'admin.dashboard' ? '' : 'group-hover:text-white transition-colors' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                <span class="text-sm font-medium">Inicio</span>
                @if($currentRoute === 'admin.dashboard')
                    <div class="absolute right-3 w-2 h-2 bg-white rounded-full active-indicator"></div>
                @endif
            </a>

            <!-- Separador -->
            <div class="py-2">
                <div class="border-t border-white/20"></div>
                <p class="text-xs text-blue-200 mt-2 px-3 font-medium uppercase tracking-wider">Gestión Principal</p>
            </div>

            <!-- Módulo -->
            <a href="{{ route('admin.cajas') }}" class="sidebar-item group w-full flex items-center justify-start {{ $currentRoute === 'admin.cajas' ? 'bg-white/20 text-white border-l-4 border-white shadow-md relative' : 'text-blue-200 hover:text-white hover:bg-white/10' }} p-3 rounded-lg transition-all duration-200 hover:translate-x-1">
                <svg class="mr-3 h-5 w-5 {{ $currentRoute === 'admin.cajas' ? '' : 'group-hover:text-white transition-colors' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
                <span class="text-sm font-medium">Módulo</span>
                @if($currentRoute === 'admin.cajas')
                    <div class="absolute right-3 w-2 h-2 bg-white rounded-full active-indicator"></div>
                @endif
            </a>

            <!-- Servicios -->
            <a href="{{ route('admin.servicios') }}" class="sidebar-item group w-full flex items-center justify-start {{ $currentRoute === 'admin.servicios' ? 'bg-white/20 text-white border-l-4 border-white shadow-md relative' : 'text-blue-200 hover:text-white hover:bg-white/10' }} p-3 rounded-lg transition-all duration-200 hover:translate-x-1">
                <svg class="mr-3 h-5 w-5 {{ $currentRoute === 'admin.servicios' ? '' : 'group-hover:text-white transition-colors' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                </svg>
                <span class="text-sm font-medium">Servicios</span>
                @if($currentRoute === 'admin.servicios')
                    <div class="absolute right-3 w-2 h-2 bg-white rounded-full active-indicator"></div>
                @endif
            </a>

            <!-- Asignación de Servicios -->
            <a href="{{ route('admin.asignacion-servicios') }}" class="sidebar-item group w-full flex items-center justify-start {{ $currentRoute === 'admin.asignacion-servicios' ? 'bg-white/20 text-white border-l-4 border-white shadow-md relative' : 'text-blue-200 hover:text-white hover:bg-white/10' }} p-3 rounded-lg transition-all duration-200 hover:translate-x-1">
                <svg class="mr-3 h-5 w-5 {{ $currentRoute === 'admin.asignacion-servicios' ? '' : 'group-hover:text-white transition-colors' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                </svg>
                <span class="text-sm font-medium">Asignación de Servicios</span>
                @if($currentRoute === 'admin.asignacion-servicios')
                    <div class="absolute right-3 w-2 h-2 bg-white rounded-full active-indicator"></div>
                @endif
            </a>

            <!-- Turnos -->
            <a href="{{ route('admin.turnos') }}" class="sidebar-item group w-full flex items-center justify-start {{ $currentRoute === 'admin.turnos' ? 'bg-white/20 text-white border-l-4 border-white shadow-md relative' : 'text-blue-200 hover:text-white hover:bg-white/10' }} p-3 rounded-lg transition-all duration-200 hover:translate-x-1">
                <svg class="mr-3 h-5 w-5 {{ $currentRoute === 'admin.turnos' ? '' : 'group-hover:text-white transition-colors' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
                </svg>
                <span class="text-sm font-medium">Turnos</span>
                @if($currentRoute === 'admin.turnos')
                    <div class="absolute right-3 w-2 h-2 bg-white rounded-full active-indicator"></div>
                @endif
            </a>

            <!-- Usuarios -->
            <a href="{{ route('admin.users') }}" class="sidebar-item group w-full flex items-center justify-start {{ $currentRoute === 'admin.users' ? 'bg-white/20 text-white border-l-4 border-white shadow-md relative' : 'text-blue-200 hover:text-white hover:bg-white/10' }} p-3 rounded-lg transition-all duration-200 hover:translate-x-1">
                <svg class="mr-3 h-5 w-5 {{ $currentRoute === 'admin.users' ? '' : 'group-hover:text-white transition-colors' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <span class="text-sm font-medium">Usuarios</span>
                @if($currentRoute === 'admin.users')
                    <div class="absolute right-3 w-2 h-2 bg-white rounded-full active-indicator"></div>
                @endif
            </a>

            <!-- Separador -->
            <div class="py-2">
                <div class="border-t border-white/20"></div>
                <p class="text-xs text-blue-200 mt-2 px-3 font-medium uppercase tracking-wider">Análisis</p>
            </div>

            <!-- Gráficos -->
            <a href="{{ route('admin.graficos') }}" class="sidebar-item group w-full flex items-center justify-start {{ $currentRoute === 'admin.graficos' ? 'bg-white/20 text-white border-l-4 border-white shadow-md relative' : 'text-blue-200 hover:text-white hover:bg-white/10' }} p-3 rounded-lg transition-all duration-200 hover:translate-x-1">
                <svg class="mr-3 h-5 w-5 {{ $currentRoute === 'admin.graficos' ? '' : 'group-hover:text-white transition-colors' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                <span class="text-sm font-medium">Gráficos</span>
                @if($currentRoute === 'admin.graficos')
                    <div class="absolute right-3 w-2 h-2 bg-white rounded-full active-indicator"></div>
                @endif
            </a>

            <!-- Reportes -->
            <a href="{{ route('admin.reportes') }}" class="sidebar-item group w-full flex items-center justify-start {{ $currentRoute === 'admin.reportes' ? 'bg-white/20 text-white border-l-4 border-white shadow-md relative' : 'text-blue-200 hover:text-white hover:bg-white/10' }} p-3 rounded-lg transition-all duration-200 hover:translate-x-1">
                <svg class="mr-3 h-5 w-5 {{ $currentRoute === 'admin.reportes' ? '' : 'group-hover:text-white transition-colors' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <span class="text-sm font-medium">Reportes</span>
                @if($currentRoute === 'admin.reportes')
                    <div class="absolute right-3 w-2 h-2 bg-white rounded-full active-indicator"></div>
                @endif
            </a>

            <!-- Separador -->
            <div class="py-2">
                <div class="border-t border-white/20"></div>
                <p class="text-xs text-blue-200 mt-2 px-3 font-medium uppercase tracking-wider">Configuración</p>
            </div>

            <!-- ConfigTv -->
            <a href="{{ route('admin.tv-config') }}" class="sidebar-item group w-full flex items-center justify-start {{ $currentRoute === 'admin.tv-config' ? 'bg-white/20 text-white border-l-4 border-white shadow-md relative' : 'text-blue-200 hover:text-white hover:bg-white/10' }} p-3 rounded-lg transition-all duration-200 hover:translate-x-1">
                <svg class="mr-3 h-5 w-5 {{ $currentRoute === 'admin.tv-config' ? '' : 'group-hover:text-white transition-colors' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
                <span class="text-sm font-medium">Config TV</span>
                @if($currentRoute === 'admin.tv-config')
                    <div class="absolute right-3 w-2 h-2 bg-white rounded-full active-indicator"></div>
                @endif
            </a>

            <!-- Separador -->
            <div class="py-2">
                <div class="border-t border-white/20"></div>
                <p class="text-xs text-blue-200 mt-2 px-3 font-medium uppercase tracking-wider">Otros</p>
            </div>

            <!-- Clientes -->
            <button class="sidebar-item group w-full flex items-center justify-start text-blue-200 hover:text-white hover:bg-white/10 p-3 rounded-lg transition-all duration-200 hover:translate-x-1">
                <svg class="mr-3 h-5 w-5 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                <span class="text-sm font-medium">Clientes</span>
            </button>

            <!-- Preguntas -->
            <button class="sidebar-item group w-full flex items-center justify-start text-blue-200 hover:text-white hover:bg-white/10 p-3 rounded-lg transition-all duration-200 hover:translate-x-1">
                <svg class="mr-3 h-5 w-5 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <span class="text-sm font-medium">Preguntas</span>
            </button>

            <!-- Horas -->
            <button class="sidebar-item group w-full flex items-center justify-start text-blue-200 hover:text-white hover:bg-white/10 p-3 rounded-lg transition-all duration-200 hover:translate-x-1">
                <svg class="mr-3 h-5 w-5 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="text-sm font-medium">Horas</span>
            </button>

            <!-- Soporte -->
            <a href="{{ route('admin.soporte') }}" class="sidebar-item group w-full flex items-center justify-start {{ $currentRoute === 'admin.soporte' ? 'bg-white/20 text-white border-l-4 border-white shadow-md relative' : 'text-blue-200 hover:text-white hover:bg-white/10' }} p-3 rounded-lg transition-all duration-200 hover:translate-x-1">
                <svg class="mr-3 h-5 w-5 {{ $currentRoute === 'admin.soporte' ? '' : 'group-hover:text-white transition-colors' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="text-sm font-medium">Soporte</span>
                @if($currentRoute === 'admin.soporte')
                    <div class="absolute right-3 w-2 h-2 bg-white rounded-full active-indicator"></div>
                @endif
            </a>
        </nav>
    </div>

    <!-- Footer del Sidebar -->
    <div class="flex-shrink-0 p-4 border-t border-white/20 bg-white/10 mt-auto">
        <div class="text-center">
            <div class="text-sm font-medium text-white">Turnero HUV®</div>
            <div class="text-xs text-blue-200">Hospital Universitario del Valle</div>
            <div class="text-xs text-blue-300 mt-1">"Evaristo García" E.S.E</div>
        </div>

        <!-- Botón de Logout -->
        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button type="submit" class="w-full flex items-center justify-center text-blue-200 hover:text-white hover:bg-white/10 p-2 rounded-lg transition-all duration-200 text-sm">
                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
                Cerrar Sesión
            </button>
        </form>
    </div>
</aside>
