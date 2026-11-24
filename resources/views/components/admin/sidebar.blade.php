@php
    $user = auth()->user();
    $currentRoute = request()->route()?->getName();
    $displayName = $user->nombre_completo ?? $user->nombre_usuario ?? 'Usuario';
    $iniciales = collect(preg_split('/\s+/', trim($displayName)))->filter()->take(2)
        ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
    $unidad = config('panel.unidad_nombre', 'Panel administrativo');

    // Contadores del riel: turnos que esperan hoy y solicitudes de soporte nuevas. Si algo falla, el menú sale sin ellos.
    $contadores = [];
    try {
        $contadores['admin.turnos'] = \App\Models\Turno::query()
            ->where('created_at', '>=', today())
            ->whereIn('estado', ['pendiente', 'aplazado'])
            ->count();
    } catch (\Throwable $e) {
    }
    try {
        $contadores['admin.soporte'] = collect(app(\App\Services\SoporteService::class)->listar())
            ->where('estado', 'nueva')->count();
    } catch (\Throwable $e) {
    }

    // Íconos de trazo (Lucide, licencia ISC), a 20 px y trazo 1,5.
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
    ];
    $icono = fn ($nombre) => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $iconos[$nombre] . '</svg>';

    $navSections = [
        ['title' => null, 'items' => [
            ['label' => 'Inicio', 'route' => 'admin.dashboard', 'icon' => 'home', 'hint' => 'La sala en este momento'],
        ]],
        ['title' => 'Operación', 'items' => [
            ['label' => 'Turnos', 'route' => 'admin.turnos', 'icon' => 'ticket', 'hint' => 'Turnos de hoy'],
            ['label' => 'Módulos', 'route' => 'admin.cajas', 'icon' => 'modulos', 'hint' => 'Puntos de atención'],
            ['label' => 'Servicios', 'route' => 'admin.servicios', 'icon' => 'servicios', 'hint' => 'Catálogo de servicios'],
            ['label' => 'Asignación', 'route' => 'admin.asignacion-servicios', 'icon' => 'asignacion', 'hint' => 'Servicios por asesor'],
        ]],
        ['title' => 'Análisis', 'items' => [
            ['label' => 'Gráficos', 'route' => 'admin.graficos', 'icon' => 'chart', 'hint' => 'Indicadores'],
            ['label' => 'Reportes', 'route' => 'admin.reportes', 'icon' => 'report', 'hint' => 'Informes en Excel y PDF'],
        ]],
        ['title' => 'Gestión', 'items' => [
            ['label' => 'Usuarios', 'route' => 'admin.users', 'icon' => 'users', 'hint' => 'Roles y accesos'],
            ['label' => 'Config TV', 'route' => 'admin.tv-config', 'icon' => 'tv', 'hint' => 'Pantalla pública'],
            ['label' => 'Soporte', 'route' => 'admin.soporte', 'icon' => 'help', 'hint' => 'Solicitudes de ayuda'],
        ]],
    ];
@endphp

{{-- Riel flotante «La sala» (2026-11): una pieza blanca separada de los bordes, con íconos y nombres cortos.
     Estilos en layouts/admin.blade.php («Riel»). En el celular se abre encima con el botón de la cabecera. --}}
<aside id="admin-sidebar" aria-label="Menú lateral" class="riel" :class="sidebarOpen ? 'riel--abierto' : ''">
    <a href="{{ route('admin.dashboard') }}" class="riel__marca" title="Turnero HUV · {{ $unidad }}">
        <img src="{{ asset('images/logo.png') }}" alt="Hospital Universitario del Valle" class="riel__logo">
    </a>

    <nav class="riel__nav" aria-label="Navegación principal">
        @foreach($navSections as $i => $section)
            @if($i > 0)
                <span class="riel__separador" role="presentation"></span>
            @endif
            @foreach($section['items'] as $item)
                @php
                    $isActive = $currentRoute === $item['route'] || ($item['route'] === 'admin.tv-config' && $currentRoute === 'voice.admin');
                    $n = $contadores[$item['route']] ?? 0;
                @endphp
                <a href="{{ route($item['route']) }}" @if($isActive) aria-current="page" @endif
                   title="{{ $item['label'] }} — {{ $item['hint'] }}"
                   class="riel__item {{ $isActive ? 'is-activo' : '' }}">
                    <span class="riel__icono">{!! $icono($item['icon']) !!}
                        @if($n > 0)<span class="riel__cuenta" aria-label="{{ $n }} pendientes">{{ $n > 99 ? '99+' : $n }}</span>@endif
                    </span>
                    <span class="riel__texto">{{ $item['label'] }}</span>
                </a>
            @endforeach
        @endforeach
    </nav>

    <div class="riel__pie">
        <span class="riel__avatar" title="{{ $displayName }} · {{ $user->rol ?? 'Sin rol' }}">{{ $iniciales ?: '·' }}</span>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="riel__salir" aria-label="Cerrar sesión" title="Cerrar sesión">{!! $icono('salir') !!}</button>
        </form>
    </div>
</aside>
