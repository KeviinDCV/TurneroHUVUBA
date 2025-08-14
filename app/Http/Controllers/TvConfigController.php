<?php

namespace App\Http\Controllers;

use App\Models\TvConfig;
use App\Models\Multimedia;
use App\Models\Turno;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TvConfigController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['show', 'showMobile', 'getConfig', 'getActiveMultimedia', 'getTurnosLlamados']);
    }

    /**
     * Mostrar la página de configuración del TV
     */
    public function index()
    {
        $user = Auth::user();
        $tvConfig = TvConfig::getCurrentConfig();
        $multimedia = Multimedia::orderBy('orden')->orderBy('created_at')->get();

        return view('admin.tv-config', compact('user', 'tvConfig', 'multimedia'));
    }

    /**
     * Actualizar la configuración del TV
     */
    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'ticker_message' => 'required|string|max:1000',
                'ticker_speed' => 'required|integer|min:10|max:120',
                'ticker_enabled' => 'boolean'
            ]);

            $tvConfig = TvConfig::getCurrentConfig();

            $tvConfig->update([
                'ticker_message' => $validated['ticker_message'],
                'ticker_speed' => $validated['ticker_speed'],
                'ticker_enabled' => $request->has('ticker_enabled')
            ]);

            // Si es una petición AJAX, devolver JSON
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Configuración del TV actualizada correctamente'
                ]);
            }

            return redirect()->route('admin.tv-config')
                ->with('success', 'Configuración del TV actualizada correctamente');

        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $e->errors()
                ], 422);
            }
            throw $e;
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error interno del servidor'
                ], 500);
            }
            throw $e;
        }
    }

    /**
     * Mostrar la página del TV con configuración
     */
    public function show()
    {
        $tvConfig = TvConfig::getCurrentConfig();
        return view('tv.display', compact('tvConfig'));
    }

    /**
     * Mostrar la página móvil con configuración del TV
     */
    public function showMobile(Request $request)
    {
        $tvConfig = TvConfig::getCurrentConfig();
        $turnoInfo = null;

        // Verificar si se proporcionó un ID de turno
        $turnoId = $request->get('turno');
        if ($turnoId) {
            $turno = \App\Models\Turno::with('servicio')->find($turnoId);

            // Verificar que el turno existe y es del día actual
            if ($turno && $turno->fecha_creacion->isToday()) {
                // Calcular posición en la cola
                $posicionEnCola = $this->calcularPosicionEnCola($turno);

                $turnoInfo = [
                    'turno' => $turno,
                    'posicion' => $posicionEnCola['posicion'],
                    'turnos_adelante' => $posicionEnCola['turnos_adelante'],
                    'tiempo_estimado' => $posicionEnCola['tiempo_estimado']
                ];
            }
        }

        return view('mobile.display', compact('tvConfig', 'turnoInfo'));
    }

    /**
     * Calcular la posición de un turno en la cola
     */
    private function calcularPosicionEnCola($turno)
    {
        // Si el turno ya fue atendido
        if ($turno->estado === 'atendido') {
            return [
                'posicion' => 0,
                'turnos_adelante' => 0,
                'tiempo_estimado' => 0,
                'mensaje' => 'Su turno ya fue atendido'
            ];
        }

        // Si el turno está siendo llamado actualmente
        if ($turno->estado === 'llamado') {
            return [
                'posicion' => 0,
                'turnos_adelante' => 0,
                'tiempo_estimado' => 0,
                'mensaje' => 'Su turno está siendo llamado'
            ];
        }

        // Para turnos pendientes o aplazados, calcular posición
        $turnosAdelante = \App\Models\Turno::where('servicio_id', $turno->servicio_id)
            ->whereDate('fecha_creacion', $turno->fecha_creacion)
            ->whereIn('estado', ['pendiente', 'aplazado'])
            ->where('numero', '<', $turno->numero)
            ->count();

        // Estimar tiempo (asumiendo 3 minutos promedio por turno)
        $tiempoEstimado = $turnosAdelante * 3;

        return [
            'posicion' => $turnosAdelante + 1,
            'turnos_adelante' => $turnosAdelante,
            'tiempo_estimado' => $tiempoEstimado,
            'mensaje' => $turnosAdelante > 0 ?
                "Faltan {$turnosAdelante} turnos para ser atendido" :
                "Su turno será llamado próximamente"
        ];
    }

    /**
     * Obtener el estado actual de un turno específico (API)
     */
    public function getTurnoStatus($turnoId)
    {
        try {
            $turno = Turno::with('servicio')->find($turnoId);

            if (!$turno) {
                return response()->json([
                    'success' => false,
                    'message' => 'Turno no encontrado'
                ], 404);
            }

            // Verificar que el turno sea del día actual
            if (!$turno->fecha_creacion->isToday()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El turno no es del día actual'
                ], 400);
            }

            // Calcular posición en la cola
            $posicionInfo = $this->calcularPosicionEnCola($turno);

            return response()->json([
                'success' => true,
                'turno' => [
                    'id' => $turno->id,
                    'codigo_completo' => $turno->codigo_completo,
                    'estado' => $turno->estado,
                    'numero_caja' => $turno->caja ? $turno->caja->nombre : null,
                    'servicio' => $turno->servicio->nombre,
                    'fecha_creacion' => $turno->fecha_creacion->format('Y-m-d H:i:s'),
                    'fecha_llamado' => $turno->fecha_llamado ? $turno->fecha_llamado->format('Y-m-d H:i:s') : null,
                    'fecha_atencion' => $turno->fecha_atencion ? $turno->fecha_atencion->format('Y-m-d H:i:s') : null
                ],
                'posicion' => $posicionInfo['posicion'],
                'turnos_adelante' => $posicionInfo['turnos_adelante'],
                'tiempo_estimado' => $posicionInfo['tiempo_estimado'],
                'mensaje' => $posicionInfo['mensaje']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estado del turno: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Subir archivo multimedia
     */
    public function storeMultimedia(Request $request)
    {
        // Configurar límites de PHP para archivos grandes
        ini_set('upload_max_filesize', '600M');
        ini_set('post_max_size', '600M');
        ini_set('max_execution_time', '600');
        ini_set('max_input_time', '600');
        ini_set('memory_limit', '512M');

        try {
            // Verificar si el archivo fue subido correctamente
            if (!$request->hasFile('archivo')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se recibió ningún archivo. Puede que el archivo sea demasiado grande para el servidor.'
                ], 400);
            }

            $archivo = $request->file('archivo');

            // Verificar si hubo errores en la subida
            if ($archivo->getError() !== UPLOAD_ERR_OK) {
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido por el servidor.',
                    UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo permitido.',
                    UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente.',
                    UPLOAD_ERR_NO_FILE => 'No se subió ningún archivo.',
                    UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal.',
                    UPLOAD_ERR_CANT_WRITE => 'Error al escribir el archivo en el disco.',
                    UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la subida del archivo.'
                ];

                $errorMessage = $errorMessages[$archivo->getError()] ?? 'Error desconocido al subir el archivo.';

                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 400);
            }

            $request->validate([
                'archivo' => 'required|file|mimes:jpg,jpeg,png,gif,mp4,mov,avi|max:512000', // 500MB max
                'nombre' => 'required|string|max:255',
                'duracion' => 'required|integer|min:1|max:300' // 1-300 segundos
            ]);

            $extension = $archivo->getClientOriginalExtension();
            $tipo = in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif']) ? 'imagen' : 'video';

            // Generar nombre único para el archivo
            $nombreArchivo = Str::uuid() . '.' . $extension;

            // Guardar archivo en storage/app/public/multimedia
            $rutaArchivo = $archivo->storeAs('multimedia', $nombreArchivo, 'public');

            // Crear registro en base de datos
            $multimedia = Multimedia::create([
                'nombre' => $request->nombre,
                'archivo' => $rutaArchivo,
                'tipo' => $tipo,
                'extension' => $extension,
                'orden' => Multimedia::getNextOrder(),
                'duracion' => $request->duracion,
                'activo' => true,
                'tamaño' => $archivo->getSize()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Archivo subido correctamente',
                'multimedia' => $multimedia
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al subir el archivo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar orden de multimedia
     */
    public function updateMultimediaOrder(Request $request)
    {
        try {
            $request->validate([
                'items' => 'required|array',
                'items.*.id' => 'required|integer|exists:multimedia,id',
                'items.*.orden' => 'required|integer|min:1'
            ]);

            foreach ($request->items as $item) {
                Multimedia::where('id', $item['id'])
                    ->update(['orden' => $item['orden']]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Orden actualizado correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el orden'
            ], 500);
        }
    }

    /**
     * Activar/desactivar multimedia
     */
    public function toggleMultimedia(Request $request, $id)
    {
        try {
            $multimedia = Multimedia::findOrFail($id);
            $multimedia->activo = !$multimedia->activo;
            $multimedia->save();

            return response()->json([
                'success' => true,
                'message' => $multimedia->activo ? 'Archivo activado' : 'Archivo desactivado',
                'activo' => $multimedia->activo
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado'
            ], 500);
        }
    }

    /**
     * Eliminar multimedia
     */
    public function destroyMultimedia($id)
    {
        try {
            $multimedia = Multimedia::findOrFail($id);

            // Eliminar archivo del storage
            if (Storage::disk('public')->exists($multimedia->archivo)) {
                Storage::disk('public')->delete($multimedia->archivo);
            }

            // Eliminar registro de la base de datos
            $multimedia->delete();

            return response()->json([
                'success' => true,
                'message' => 'Archivo eliminado correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el archivo'
            ], 500);
        }
    }

    /**
     * Obtener la configuración actual para la página del TV
     */
    public function getConfig()
    {
        $tvConfig = TvConfig::getCurrentConfig();

        return response()->json([
            'ticker_message' => $tvConfig->ticker_message,
            'ticker_speed' => $tvConfig->ticker_speed,
            'ticker_enabled' => $tvConfig->ticker_enabled
        ]);
    }

    /**
     * Obtener multimedia activa para el TV
     */
    public function getActiveMultimedia()
    {
        $multimedia = Multimedia::getActiveOrdered();

        return response()->json([
            'multimedia' => $multimedia->map(function ($item) {
                return [
                    'id' => $item->id,
                    'nombre' => $item->nombre,
                    'url' => $item->url,
                    'tipo' => $item->tipo,
                    'duracion' => $item->duracion,
                    'orden' => $item->orden
                ];
            })
        ]);
    }

    /**
     * Obtener los últimos turnos llamados y atendidos para mostrar en el TV
     */
    public function getTurnosLlamados()
    {
        // Obtener turnos llamados y atendidos recientes (últimas 2 horas)
        // Limitado a 5 turnos para evitar desbordamiento visual en la pantalla TV
        // Excluir turnos de servicios con ocultar_turno = true
        $turnos = \App\Models\Turno::whereIn('estado', ['llamado', 'atendido'])
            ->where('fecha_llamado', '>=', now()->subHours(2))
            ->with(['servicio', 'caja'])
            ->whereHas('servicio', function($query) {
                $query->where('ocultar_turno', false);
            })
            ->orderBy('fecha_llamado', 'desc')
            ->take(5) // Limitado a 5 turnos para la visualización en TV
            ->get();

        return response()->json([
            'turnos' => $turnos->map(function ($turno) {
                return [
                    'id' => $turno->id,
                    'codigo_completo' => $turno->codigo_completo,
                    'caja' => $turno->caja ? $turno->caja->nombre : null,
                    'numero_caja' => $turno->caja ? $turno->caja->numero_caja : null,
                    'servicio' => $turno->servicio ? $turno->servicio->nombre : null,
                    'estado' => $turno->estado,
                    'fecha_llamado' => $turno->fecha_llamado ? $turno->fecha_llamado->format('Y-m-d H:i:s') : null,
                    'fecha_atencion' => $turno->fecha_atencion ? $turno->fecha_atencion->format('Y-m-d H:i:s') : null,
                    'duracion_atencion' => $turno->duracion_atencion
                ];
            })
        ]);
    }
}
