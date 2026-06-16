@php
    $user = auth()->user();
    $currentRoute = request()->route()?->getName();
    $displayName = $user->nombre_completo ?? $user->nombre_usuario ?? 'Usuario';
    $userInitial = mb_strtoupper(mb_substr($displayName, 0, 1));

    $navSections = [
        [
            'title' => null,
            'items' => [
                ['label' => 'Inicio', 'route' => 'admin.dashboard', 'icon' => 'home', 'hint' => 'Vista general'],
            ],
        ],
        [
            'title' => 'Operación',
            'items' => [
                ['label' => 'Turnos', 'route' => 'admin.turnos', 'icon' => 'ticket', 'hint' => 'Control diario'],
                ['label' => 'Módulos', 'route' => 'admin.cajas', 'icon' => 'building', 'hint' => 'Puntos de atención'],
                ['label' => 'Servicios', 'route' => 'admin.servicios', 'icon' => 'heart', 'hint' => 'Catálogo HUV'],
                ['label' => 'Asignación', 'route' => 'admin.asignacion-servicios', 'icon' => 'link', 'hint' => 'Servicios por asesor'],
            ],
        ],
        [
            'title' => 'Administración',
            'items' => [
                ['label' => 'Usuarios', 'route' => 'admin.users', 'icon' => 'users', 'hint' => 'Roles y accesos'],
            ],
        ],
        [
            'title' => 'Análisis',
            'items' => [
                ['label' => 'Gráficos', 'route' => 'admin.graficos', 'icon' => 'chart', 'hint' => 'Indicadores'],
                ['label' => 'Reportes', 'route' => 'admin.reportes', 'icon' => 'report', 'hint' => 'Exportaciones'],
            ],
        ],
        [
            'title' => 'Pantallas',
            'items' => [
                ['label' => 'Config TV', 'route' => 'admin.tv-config', 'icon' => 'monitor', 'hint' => 'Pantalla pública'],
            ],
        ],
        [
            'title' => 'Ayuda',
            'items' => [
                ['label' => 'Soporte', 'route' => 'admin.soporte', 'icon' => 'help', 'hint' => 'Asistencia'],
            ],
        ],
    ];
@endphp

<aside class="sidebar-responsive sidebar-shell text-white flex flex-col sidebar-full-height fixed inset-y-0 left-0 z-30 transform md:transform-none transition-transform duration-200 ease-out md:transition-none"
       :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'">

    <!-- Encabezado -->
    <div class="sidebar-header flex-shrink-0 px-4 py-4" style="border-bottom: 1px solid rgba(255,255,255,0.10);">
        <div class="flex items-center justify-between gap-2">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center min-w-0 gap-3" title="Turnero HUV">
                <span class="w-10 h-10 bg-white rounded-lg flex items-center justify-center flex-shrink-0 p-1" style="box-shadow: 0 1px 3px rgba(0,0,0,0.18);">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo Hospital Universitario del Valle" class="w-full h-full object-contain">
                </span>
                <span class="min-w-0 sidebar-label" x-show="!sidebarCollapsed">
                    <span class="block text-base font-semibold leading-tight truncate">Turnero HUV</span>
                    <span class="block text-xs truncate" style="color: #9db8dd;">Panel administrativo</span>
                </span>
            </a>

            <button type="button"
                    @click="sidebarCollapsed = !sidebarCollapsed; localStorage.setItem('huvSidebarCollapsed', sidebarCollapsed ? '1' : '0')"
                    class="hidden md:inline-flex h-8 w-8 items-center justify-center rounded-lg transition-colors flex-shrink-0"
                    style="color: #9db8dd;" onmouseover="this.style.background='rgba(255,255,255,.1)';this.style.color='#fff'" onmouseout="this.style.background='transparent';this.style.color='#9db8dd'"
                    title="Alternar menú compacto">
                <svg class="h-4 w-4 transition-transform duration-200" :class="sidebarCollapsed ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </button>

            <button type="button" @click="sidebarOpen = false" class="md:hidden h-9 w-9 inline-flex items-center justify-center rounded-lg text-white transition-colors flex-shrink-0" onmouseover="this.style.background='rgba(255,255,255,.1)'" onmouseout="this.style.background='transparent'" title="Cerrar menú">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    </div>

    <!-- Usuario conectado -->
    <div class="flex-shrink-0 px-4 py-3" style="border-bottom: 1px solid rgba(255,255,255,0.10);">
        <div class="flex items-center gap-3" :class="sidebarCollapsed ? 'justify-center' : ''">
            <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0" style="background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.16);">
                <span class="text-sm font-semibold text-white">{{ $userInitial }}</span>
            </div>
            <div class="min-w-0 sidebar-label" x-show="!sidebarCollapsed">
                <p class="text-sm font-medium truncate text-white">{{ $displayName }}</p>
                <p class="text-xs truncate flex items-center mt-0.5" style="color: #9db8dd;">
                    <span class="w-1.5 h-1.5 rounded-full mr-1.5 flex-shrink-0" style="background: #34d399;"></span>
                    {{ $user->rol ?? 'Sin rol' }}
                </p>
            </div>
        </div>
    </div>

    <!-- Navegación -->
    <div class="flex-1 overflow-y-auto sidebar-nav">
        <nav class="pl-3 py-3 space-y-4 pb-6">
            @foreach($navSections as $section)
                <div>
                    @if($section['title'])
                        <div class="sidebar-section-title px-3 mb-1.5" x-show="!sidebarCollapsed">{{ $section['title'] }}</div>
                        <div class="hidden md:block mr-3 mb-2 border-t" style="border-color: rgba(255,255,255,0.10);" x-show="sidebarCollapsed"></div>
                    @endif

                    <div class="space-y-0.5">
                        @foreach($section['items'] as $item)
                            @php $isActive = $currentRoute === $item['route']; @endphp
                            <a href="{{ route($item['route']) }}"
                               title="{{ $item['label'] }}{{ !empty($item['hint']) ? ' — '.$item['hint'] : '' }}"
                               class="sidebar-item group relative flex items-center min-h-[42px] transition-colors duration-150 {{ $isActive ? 'sidebar-item-active' : '' }}"
                               :class="sidebarCollapsed ? 'justify-center pr-2 pl-2' : 'justify-start px-3 gap-3'">
                                <span class="flex-shrink-0 flex items-center justify-center">
                                    @switch($item['icon'])
                                        @case('home')
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                                            @break
                                        @case('ticket')
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                                            @break
                                        @case('building')
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                            @break
                                        @case('heart')
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                                            @break
                                        @case('link')
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                                            @break
                                        @case('users')
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                            @break
                                        @case('chart')
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                            @break
                                        @case('report')
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                            @break
                                        @case('monitor')
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                            @break
                                        @default
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    @endswitch
                                </span>
                                <span class="sidebar-label text-sm font-medium truncate" x-show="!sidebarCollapsed">{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </nav>
    </div>

    <!-- Footer -->
    <div class="flex-shrink-0 px-3 py-3" style="border-top: 1px solid rgba(255,255,255,0.10);">
        <div class="sidebar-label px-2 pb-2.5" x-show="!sidebarCollapsed">
            <div class="text-[11px] font-medium text-white">"Evaristo García" E.S.E</div>
            <div class="text-[11px]" style="color: #9db8dd;">Turnero HUV · Innovación y desarrollo</div>
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="sidebar-logout w-full flex items-center min-h-[42px] transition-colors"
                    :class="sidebarCollapsed ? 'justify-center px-2' : 'justify-start px-3 gap-3'"
                    title="Cerrar sesión">
                <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
                <span class="sidebar-label text-sm font-medium" x-show="!sidebarCollapsed">Cerrar sesión</span>
            </button>
        </form>
    </div>
</aside>
