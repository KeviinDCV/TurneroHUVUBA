<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CajaController;
use App\Http\Controllers\ServicioController;
use App\Http\Controllers\TurnoController;
use App\Http\Controllers\TvConfigController;
use App\Http\Controllers\MultimediaController;
use App\Http\Controllers\AsesorController;
use App\Http\Controllers\GraficosController;
use App\Http\Controllers\ReportesController;
use App\Http\Controllers\VoiceController;
use App\Http\Controllers\SoporteController;

Route::get('/', function () {
    return redirect()->route('login');
});

// Rutas de autenticación
Route::get('/admin', [AuthController::class, 'showLogin'])->name('admin.login');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login'); // Ruta requerida por Laravel
Route::post('/admin', [AuthController::class, 'login'])->name('admin.login.post');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// API para verificar estado de autenticación (sin crear sesión automática)
Route::get('/api/auth-check', [AuthController::class, 'checkAuth'])
    ->name('api.auth-check')
    ->middleware(['no.session.api']);

// API para regenerar token CSRF (sin crear sesión automática)
Route::get('/api/csrf-token', function() {
    return response()->json(['csrf_token' => csrf_token()]);
})->name('api.csrf-token')
    ->middleware(['no.session.api']);

// Ruta de debug para CSRF (solo en desarrollo)
Route::get('/debug/csrf', function() {
    if (config('app.env') !== 'local') {
        abort(404);
    }

    return response()->json([
        'csrf_token' => csrf_token(),
        'session_id' => session()->getId(),
        'session_started' => session()->isStarted(),
        'app_url' => config('app.url'),
        'app_env' => config('app.env'),
        'session_config' => [
            'driver' => config('session.driver'),
            'domain' => config('session.domain'),
            'secure' => config('session.secure'),
            'same_site' => config('session.same_site'),
        ]
    ]);
})->name('debug.csrf');

// API para repetir audio del último turno (requiere autenticación)
Route::post('/api/repetir-audio-turno', [TurnoController::class, 'repetirAudioTurno'])
    ->name('api.repetir-audio-turno')
    ->middleware(['auth']);

// Rutas que requieren autenticación pero manejan redirección automática
Route::get('/admin/usuarios', function () {
    if (!Auth::check()) {
        return redirect()->route('admin.login')->with('message', 'Debe iniciar sesión para acceder a la gestión de usuarios.');
    }
    return app(AdminController::class)->users(request());
})->name('admin.usuarios.public');

// Rutas protegidas del dashboard administrativo
Route::middleware(['auth', 'admin.role', 'update.user.activity', 'clean.expired.boxes'])->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');

    // Rutas de gestión de usuarios
    Route::get('/admin/usuarios', [AdminController::class, 'users'])->name('admin.users');
    Route::post('/admin/usuarios', [AdminController::class, 'storeUser'])->name('admin.users.store');
    Route::get('/admin/usuarios/{id}', [AdminController::class, 'getUser'])->name('admin.users.get');
    Route::put('/admin/usuarios/{id}', [AdminController::class, 'updateUser'])->name('admin.users.update');
    Route::delete('/admin/usuarios/{id}', [AdminController::class, 'deleteUser'])->name('admin.users.delete');

    // Redirecciones para compatibilidad con URLs antiguas
    Route::get('/users', function () {
        return redirect()->route('admin.users');
    });

    // Rutas de gestión de cajas
    Route::get('/cajas', [CajaController::class, 'index'])->name('admin.cajas');
    Route::post('/cajas', [CajaController::class, 'store'])->name('admin.cajas.store');
    Route::put('/cajas/{id}', [CajaController::class, 'update'])->name('admin.cajas.update');
    Route::delete('/cajas/{id}', [CajaController::class, 'destroy'])->name('admin.cajas.delete');

    // Rutas de gestión de servicios
    Route::get('/servicios', [ServicioController::class, 'index'])->name('admin.servicios');
    Route::post('/servicios', [ServicioController::class, 'store'])->name('admin.servicios.store');
    Route::put('/servicios/{servicio}', [ServicioController::class, 'update'])->name('admin.servicios.update');
    Route::delete('/servicios/{servicio}', [ServicioController::class, 'destroy'])->name('admin.servicios.destroy');
    Route::get('/servicios/{servicio}', [ServicioController::class, 'show'])->name('admin.servicios.show');

    // Rutas de asignación de servicios
    Route::get('/asignacion-servicios', [App\Http\Controllers\AsignacionServicioController::class, 'index'])->name('admin.asignacion-servicios');
    Route::get('/asignacion-servicios/usuario/{userId}', [App\Http\Controllers\AsignacionServicioController::class, 'getServiciosUsuario'])->name('admin.asignacion-servicios.usuario');
    Route::post('/asignacion-servicios/asignar', [App\Http\Controllers\AsignacionServicioController::class, 'asignarServicio'])->name('admin.asignacion-servicios.asignar');
    Route::post('/asignacion-servicios/desasignar', [App\Http\Controllers\AsignacionServicioController::class, 'desasignarServicio'])->name('admin.asignacion-servicios.desasignar');
    Route::post('/asignacion-servicios/asignar-multiples', [App\Http\Controllers\AsignacionServicioController::class, 'asignarMultiplesServicios'])->name('admin.asignacion-servicios.asignar-multiples');

    // Rutas de configuración del TV (incluye multimedia)
    Route::get('/tv-config', [TvConfigController::class, 'index'])->name('admin.tv-config');
    Route::post('/tv-config', [TvConfigController::class, 'update'])->name('admin.tv-config.update');
    Route::post('/tv-config/multimedia', [TvConfigController::class, 'storeMultimedia'])->name('admin.tv-config.multimedia.store');
    Route::post('/tv-config/multimedia/order', [TvConfigController::class, 'updateMultimediaOrder'])->name('admin.tv-config.multimedia.order');
    Route::post('/tv-config/multimedia/{id}/toggle', [TvConfigController::class, 'toggleMultimedia'])->name('admin.tv-config.multimedia.toggle');
    Route::delete('/tv-config/multimedia/{id}', [TvConfigController::class, 'destroyMultimedia'])->name('admin.tv-config.multimedia.destroy');

    // Rutas para limpiar sesiones
    Route::post('/admin/clean-sessions', [AdminController::class, 'cleanExpiredSessions'])->name('admin.clean-sessions');
    Route::post('/admin/clean-all-sessions', [AdminController::class, 'cleanAllSessions'])->name('admin.clean-all-sessions');
    Route::post('/admin/clean-user-session', [AdminController::class, 'cleanUserSession'])->name('admin.clean-user-session');

    // Rutas para emergencia de turnos
    Route::post('/admin/emergency-turnos', [AdminController::class, 'emergencyTurnos'])->name('admin.emergency-turnos');
    Route::get('/api/servicios-activos', [AdminController::class, 'getServiciosActivos'])->name('api.servicios-activos');

    // API para obtener usuarios activos con sus estados
    Route::get('/api/admin/usuarios-activos', [AdminController::class, 'getUsuariosActivos'])->name('api.admin.usuarios-activos');

    // API para obtener turnos por servicio
    Route::get('/api/admin/turnos-por-servicio', [AdminController::class, 'getTurnosPorServicio'])->name('api.admin.turnos-por-servicio');

    // API para obtener turnos por asesor
    Route::get('/api/admin/turnos-por-asesor', [AdminController::class, 'getTurnosPorAsesor'])->name('api.admin.turnos-por-asesor');

    // API para obtener turnos en cola por servicio
    Route::get('/api/admin/turnos-en-cola', [AdminController::class, 'getTurnosEnCola'])->name('api.admin.turnos-en-cola');

    // Rutas para gráficos
    Route::get('/graficos', [GraficosController::class, 'index'])->name('admin.graficos');

    // APIs para gráficos
    Route::get('/api/graficos/turnos-por-servicio-semana', [GraficosController::class, 'turnosPorServicioSemana'])->name('api.graficos.turnos-por-servicio-semana');
    Route::get('/api/graficos/turnos-por-estado', [GraficosController::class, 'turnosPorEstado'])->name('api.graficos.turnos-por-estado');
    Route::get('/api/graficos/turnos-por-hora', [GraficosController::class, 'turnosPorHora'])->name('api.graficos.turnos-por-hora');
    Route::get('/api/graficos/rendimiento-asesores', [GraficosController::class, 'rendimientoAsesores'])->name('api.graficos.rendimiento-asesores');
    Route::get('/api/graficos/turnos-por-dia', [GraficosController::class, 'turnosPorDia'])->name('api.graficos.turnos-por-dia');
    Route::get('/api/graficos/tiempo-atencion-por-servicio', [GraficosController::class, 'tiempoAtencionPorServicio'])->name('api.graficos.tiempo-atencion-por-servicio');
    Route::get('/api/graficos/distribucion-prioridades', [GraficosController::class, 'distribucionPrioridades'])->name('api.graficos.distribucion-prioridades');
    Route::get('/api/graficos/estadisticas-generales', [GraficosController::class, 'estadisticasGenerales'])->name('api.graficos.estadisticas-generales');

    // APIs para gráficos históricos
    Route::get('/api/graficos/historial/volumen-por-tiempo', [GraficosController::class, 'historialVolumenPorTiempo'])->name('api.graficos.historial.volumen-por-tiempo');
    Route::get('/api/graficos/historial/distribucion-servicios', [GraficosController::class, 'historialDistribucionServicios'])->name('api.graficos.historial.distribucion-servicios');
    Route::get('/api/graficos/historial/distribucion-estados', [GraficosController::class, 'historialDistribucionEstados'])->name('api.graficos.historial.distribucion-estados');
    Route::get('/api/graficos/historial/horas-pico', [GraficosController::class, 'historialHorasPico'])->name('api.graficos.historial.horas-pico');
    Route::get('/api/graficos/historial/tiempo-atencion', [GraficosController::class, 'historialTiempoAtencion'])->name('api.graficos.historial.tiempo-atencion');
    Route::get('/api/graficos/historial/rendimiento-asesores', [GraficosController::class, 'historialRendimientoAsesores'])->name('api.graficos.historial.rendimiento-asesores');
    Route::get('/api/graficos/historial/estadisticas-generales', [GraficosController::class, 'historialEstadisticasGenerales'])->name('api.graficos.historial.estadisticas-generales');
    Route::get('/api/graficos/historial/patrones-dia-semana', [GraficosController::class, 'historialPatronesDiaSemana'])->name('api.graficos.historial.patrones-dia-semana');
    Route::get('/api/graficos/historial/eficiencia-servicios', [GraficosController::class, 'historialEficienciaServicios'])->name('api.graficos.historial.eficiencia-servicios');
    
    // APIs para canales no presenciales
    Route::get('/api/graficos/canales-no-presenciales/tiempo-por-asesor', [GraficosController::class, 'tiempoCanalesNoPresenciales'])->name('api.graficos.canales.tiempo-por-asesor');
    Route::get('/api/graficos/canales-no-presenciales/distribucion', [GraficosController::class, 'distribucionActividadesCanales'])->name('api.graficos.canales.distribucion');
    Route::get('/api/graficos/canales-no-presenciales/estadisticas', [GraficosController::class, 'estadisticasCanalesNoPresenciales'])->name('api.graficos.canales.estadisticas');

    // Rutas para reportes
    Route::get('/reportes', [ReportesController::class, 'index'])->name('admin.reportes');
    Route::post('/reportes/generar', [ReportesController::class, 'generarReporte'])->name('admin.reportes.generar');
    Route::post('/reportes/dashboard-historico', [ReportesController::class, 'exportarDashboardHistorico'])->name('admin.reportes.dashboard-historico');

    // Rutas para soporte
    Route::get('/soporte', [SoporteController::class, 'index'])->name('admin.soporte');
    Route::post('/soporte', [SoporteController::class, 'store'])->name('admin.soporte.store');

    // Sistema de Voz
    Route::prefix('voice')->name('voice.')->group(function () {
        Route::get('/status', [VoiceController::class, 'getSystemStatus'])->name('status');
        Route::get('/turn-audio', [VoiceController::class, 'getTurnAudio'])->name('turn-audio');
        Route::post('/generate-missing', [VoiceController::class, 'generateMissingFiles'])->name('generate-missing');
        Route::post('/generate-specific', [VoiceController::class, 'generateSpecificAudio'])->name('generate-specific');
        Route::post('/test-audio', [VoiceController::class, 'testAudio'])->name('test-audio');
        Route::get('/admin', [VoiceController::class, 'adminPanel'])->name('admin');
    });
});

// Rutas protegidas para asesores
Route::middleware(['auth', 'asesor.role', 'update.user.activity', 'clean.expired.boxes'])->group(function () {
    // Rutas para asesores
    Route::get('/asesor/seleccionar-caja', [AsesorController::class, 'seleccionarCaja'])->name('asesor.seleccionar-caja');
    Route::post('/asesor/seleccionar-caja', [AsesorController::class, 'procesarSeleccionCaja'])->name('asesor.procesar-seleccion-caja');
    Route::get('/asesor/dashboard', [AsesorController::class, 'dashboard'])->name('asesor.dashboard');
    Route::get('/asesor/cambiar-caja', [AsesorController::class, 'cambiarCaja'])->name('asesor.cambiar-caja');

    // Rutas para gestión de turnos por asesores
    Route::post('/asesor/llamar-siguiente-turno', [AsesorController::class, 'llamarSiguienteTurno'])->name('asesor.llamar-siguiente-turno');
    Route::post('/asesor/llamar-turno-especifico', [AsesorController::class, 'llamarTurnoEspecifico'])->name('asesor.llamar-turno-especifico');
    Route::post('/asesor/marcar-atendido', [AsesorController::class, 'marcarAtendido'])->name('asesor.marcar-atendido');
    Route::post('/asesor/aplazar-turno', [AsesorController::class, 'aplazarTurno'])->name('asesor.aplazar-turno');
    Route::get('/asesor/verificar-turno-en-proceso', [AsesorController::class, 'verificarTurnoEnProceso'])->name('asesor.verificar-turno-en-proceso');
    Route::get('/api/turno/{turno}/estado', [AsesorController::class, 'verificarEstadoTurno'])->name('api.turno.estado');
    Route::get('/asesor/historial-turnos', [AsesorController::class, 'historialTurnos'])->name('asesor.historial-turnos');
    Route::post('/asesor/volver-llamar-turno', [AsesorController::class, 'volverLlamarTurno'])->name('asesor.volver-llamar-turno');
    Route::get('/asesor/turnos-aplazados', [AsesorController::class, 'getTurnosAplazados'])->name('asesor.turnos-aplazados');

    // API para obtener estadísticas de servicios para el asesor (actualización en tiempo real)
    Route::get('/api/asesor/servicios-estadisticas', [AsesorController::class, 'getServiciosEstadisticas'])->name('api.asesor.servicios-estadisticas');
    
    // Rutas para canal no presencial
    Route::post('/asesor/iniciar-canal-no-presencial', [AsesorController::class, 'iniciarCanalNoPresencial'])->name('asesor.iniciar-canal-no-presencial');
    Route::post('/asesor/finalizar-canal-no-presencial', [AsesorController::class, 'finalizarCanalNoPresencial'])->name('asesor.finalizar-canal-no-presencial');
});

// Ruta de prueba para verificar autenticación
Route::get('/test-auth', function () {
    if (Auth::check()) {
        $user = Auth::user();
        return "Usuario autenticado: " . $user->nombre_completo . " - Rol: " . $user->rol;
    } else {
        return "No hay usuario autenticado";
    }
});



// Rutas para el visualizador/atril de turnos
Route::get('/turnos', [TurnoController::class, 'inicio'])->name('turnos.inicio');
Route::get('/turnos/menu', [TurnoController::class, 'menu'])->name('turnos.menu');
Route::post('/turnos/seleccionar', [TurnoController::class, 'seleccionarServicio'])->name('turnos.seleccionar');
Route::post('/turnos/crear-con-prioridad', [TurnoController::class, 'crearTurnoConPrioridad'])->name('turnos.crear-con-prioridad');
Route::get('/turnos/ticket/{turno}', [TurnoController::class, 'mostrarTicket'])->name('turnos.ticket');

// Ruta para el televisor - visualizador de turnos
Route::get('/tv', [TvConfigController::class, 'show'])->name('tv.display');

// Ruta para la visualización móvil
Route::get('/movil', [TvConfigController::class, 'showMobile'])->name('mobile.display');

// Ruta pública para servir archivos multimedia (cuando el symlink no funciona)
// Necesario para cPanel donde el symlink puede no funcionar correctamente
Route::get('/multimedia/serve/{encodedPath}', function ($encodedPath) {
    try {
        $filePath = base64_decode($encodedPath);
        $fullPath = storage_path('app/public/' . $filePath);
        
        // Validar que el archivo existe y está dentro del directorio permitido
        if (!file_exists($fullPath) || !str_starts_with(realpath($fullPath), realpath(storage_path('app/public')))) {
            abort(404);
        }
        
        // Determinar el tipo MIME
        $mimeType = mime_content_type($fullPath);
        
        // Devolver el archivo con caché para mejorar rendimiento
        return response()->file($fullPath, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=31536000', // Cache por 1 año
        ]);
    } catch (\Exception $e) {
        abort(404);
    }
})->name('multimedia.serve');

// APIs públicas sin creación automática de sesiones
Route::middleware(['no.session.api'])->group(function () {
    // API para obtener configuración del TV
    Route::get('/api/tv-config', [TvConfigController::class, 'getConfig'])->name('api.tv-config');

    // API para obtener multimedia activa
    Route::get('/api/multimedia', [TvConfigController::class, 'getActiveMultimedia'])->name('api.multimedia');

    // API para obtener turnos llamados (para TV)
    Route::get('/api/turnos-llamados', [TvConfigController::class, 'getTurnosLlamados'])->name('api.turnos-llamados');

    // API para obtener estado de un turno específico
    Route::get('/api/turno-status/{turno}', [TvConfigController::class, 'getTurnoStatus'])->name('api.turno-status');
});

// Ruta para el visualizador del atril
Route::get('/atril', function () {
    return view('atril.index');
})->name('atril.index');
