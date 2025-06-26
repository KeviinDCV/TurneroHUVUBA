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

Route::get('/', function () {
    return view('welcome');
});

// Rutas de autenticación
Route::get('/admin', [AuthController::class, 'showLogin'])->name('admin.login');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login'); // Ruta requerida por Laravel
Route::post('/admin', [AuthController::class, 'login'])->name('admin.login.post');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// API para verificar estado de autenticación
Route::get('/api/auth-check', [AuthController::class, 'checkAuth'])->name('api.auth-check');

// Rutas protegidas del dashboard administrativo
Route::middleware(['auth', 'admin.role', 'update.user.activity', 'clean.expired.boxes'])->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');

    // Rutas de gestión de usuarios
    Route::get('/users', [AdminController::class, 'users'])->name('admin.users');
    Route::post('/users', [AdminController::class, 'storeUser'])->name('admin.users.store');
    Route::get('/users/{id}', [AdminController::class, 'getUser'])->name('admin.users.get');
    Route::put('/users/{id}', [AdminController::class, 'updateUser'])->name('admin.users.update');
    Route::delete('/users/{id}', [AdminController::class, 'deleteUser'])->name('admin.users.delete');

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

    // Ruta para limpiar sesiones expiradas manualmente
    Route::post('/admin/clean-sessions', [AdminController::class, 'cleanExpiredSessions'])->name('admin.clean-sessions');

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

    // Rutas para reportes
    Route::get('/reportes', [ReportesController::class, 'index'])->name('admin.reportes');
    Route::post('/reportes/generar', [ReportesController::class, 'generarReporte'])->name('admin.reportes.generar');
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

    // API para obtener estadísticas de servicios para el asesor (actualización en tiempo real)
    Route::get('/api/asesor/servicios-estadisticas', [AsesorController::class, 'getServiciosEstadisticas'])->name('api.asesor.servicios-estadisticas');

    // Actualizar estado del asesor
    Route::post('/asesor/actualizar-estado', [AsesorController::class, 'actualizarEstado'])->name('asesor.actualizar-estado');
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
Route::get('/turnos/ticket/{turno}', [TurnoController::class, 'mostrarTicket'])->name('turnos.ticket');

// Ruta para el televisor - visualizador de turnos
Route::get('/tv', [TvConfigController::class, 'show'])->name('tv.display');

// Ruta para la visualización móvil
Route::get('/movil', [TvConfigController::class, 'showMobile'])->name('mobile.display');

// API para obtener configuración del TV
Route::get('/api/tv-config', [TvConfigController::class, 'getConfig'])->name('api.tv-config');

// API para obtener multimedia activa
Route::get('/api/multimedia', [TvConfigController::class, 'getActiveMultimedia'])->name('api.multimedia');

// API para obtener turnos llamados (para TV)
Route::get('/api/turnos-llamados', [TvConfigController::class, 'getTurnosLlamados'])->name('api.turnos-llamados');

// Ruta para el visualizador del atril
Route::get('/atril', function () {
    return view('atril.index');
})->name('atril.index');
