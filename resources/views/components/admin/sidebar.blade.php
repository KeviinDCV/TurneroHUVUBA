@php
    $user = auth()->user();
    $currentRoute = request()->route()?->getName();
    $displayName = $user->nombre_completo ?? $user->nombre_usuario ?? 'Usuario';
    $userInitial = mb_strtoupper(mb_substr($displayName, 0, 1));
    $unidad = config('panel.unidad_nombre', 'Panel administrativo');

    // Íconos de trazo fino (Lucide, licencia ISC), dibujados a 16 px.
    $iconos = [
        'home' => '<path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8"/><path d="M3 10a2 2 0 0 1 .709-1.528l7-5.999a2 2 0 0 1 2.582 0l7 5.999A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
        'ticket' => '<path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/><path d="M13 5v2"/><path d="M13 17v2"/><path d="M13 11v2"/>',
        'modulos' => '<rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/>',
        'servicios' => '<path d="M11 2v2"/><path d="M5 2v2"/><path d="M5 3H4a2 2 0 0 0-2 2v4a6 6 0 0 0 12 0V5a2 2 0 0 0-2-2h-1"/><path d="M8 15a6 6 0 0 0 12 0v-3"/><circle cx="20" cy="10" r="2"/>',
        'asignacion' => '<path d="m3 17 2 2 4-4"/><path d="m3 7 2 2 4-4"/><path d="M13 6h8"/><path d="M13 12h8"/><path d="M13 18h8"/>',
        'chart' => '<path d="M3 3v16a2 2 0 0 0 2 2h16"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/>',
        'report' => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'tv' => '<rect width="20" height="15" x="2" y="7" rx="2" ry="2"/><polyline points="17 2 12 7 7 2"/>',
        'help' => '<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/>',
        'salir' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/>',
        'panel' => '<rect width="18" height="18" x="3" y="3" rx="2"/><path d="M9 3v18"/>',
        'cerrar' => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
    ];
    $icono = fn ($nombre) => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $iconos[$nombre] . '</svg>';

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
                ['label' => 'Módulos', 'route' => 'admin.cajas', 'icon' => 'modulos', 'hint' => 'Puntos de atención'],
                ['label' => 'Servicios', 'route' => 'admin.servicios', 'icon' => 'servicios', 'hint' => 'Catálogo HUV'],
                ['label' => 'Asignación', 'route' => 'admin.asignacion-servicios', 'icon' => 'asignacion', 'hint' => 'Servicios por asesor'],
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
            'title' => 'Gestión',
            'items' => [
                ['label' => 'Usuarios', 'route' => 'admin.users', 'icon' => 'users', 'hint' => 'Roles y accesos'],
                ['label' => 'Config TV', 'route' => 'admin.tv-config', 'icon' => 'tv', 'hint' => 'Pantalla pública'],
                ['label' => 'Soporte', 'route' => 'admin.soporte', 'icon' => 'help', 'hint' => 'Asistencia'],
            ],
        ],
    ];
@endphp

{{-- Menú lateral «Limpio · Toque» (2026-10): claro, letra pequeña, el azul institucional en la opción activa.
     Los estilos están en layouts/admin.blade.php (bloque 8). El modo compacto lo resuelve el CSS con
     body.sidebar-is-collapsed, que se pone antes del primer pintado: el menú no salta al cargar. --}}
<aside id="admin-sidebar" aria-label="Menú lateral" class="sidebar-responsive sidebar-full-height menu-lateral fixed inset-y-0 left-0 z-30 flex flex-col transform md:transform-none transition-transform duration-200 ease-out md:transition-none"
       :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'">

    <div class="menu-cabeza">
        <a href="{{ route('admin.dashboard') }}" class="menu-espacio" title="Turnero HUV · {{ $unidad }}">
            <span class="menu-sello" aria-hidden="true">HUV</span>
            <span class="menu-espacio__texto">
                <span class="menu-espacio__nombre">Turnero HUV</span>
                <span class="menu-espacio__unidad">{{ $unidad }}</span>
            </span>
        </a>
        <button type="button" class="menu-boton menu-boton--plegar"
                @click="sidebarCollapsed = !sidebarCollapsed; localStorage.setItem('huvSidebarCollapsed', sidebarCollapsed ? '1' : '0')"
                :aria-expanded="!sidebarCollapsed" aria-label="Alternar menú compacto" title="Alternar menú compacto">{!! $icono('panel') !!}</button>
        <button type="button" class="menu-boton menu-boton--cerrar" @click="sidebarOpen = false" aria-label="Cerrar menú" title="Cerrar menú">{!! $icono('cerrar') !!}</button>
    </div>

    <nav class="menu-nav" aria-label="Navegación principal">
        @foreach($navSections as $section)
            @if($section['title'])
                <p class="menu-seccion">{{ $section['title'] }}</p>
            @endif
            @foreach($section['items'] as $item)
                @php $isActive = $currentRoute === $item['route']; @endphp
                <a href="{{ route($item['route']) }}" @if($isActive) aria-current="page" @endif
                   title="{{ $item['label'] }}{{ !empty($item['hint']) ? ' — '.$item['hint'] : '' }}"
                   class="menu-item {{ $isActive ? 'is-activo' : '' }}">
                    {!! $icono($item['icon']) !!}
                    <span class="menu-item__texto">{{ $item['label'] }}</span>
                </a>
            @endforeach
        @endforeach
    </nav>

    <div class="menu-pie">
        <div class="menu-usuario" title="{{ $displayName }} · {{ $user->rol ?? 'Sin rol' }}">
            <span class="menu-avatar" aria-hidden="true">{{ $userInitial }}</span>
            <span class="menu-usuario__nombre">{{ $displayName }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="menu-boton" aria-label="Cerrar sesión" title="Cerrar sesión">{!! $icono('salir') !!}</button>
            </form>
        </div>
        <p class="menu-firma">"Evaristo García" E.S.E · Innovación y Desarrollo</p>
    </div>
</aside>
