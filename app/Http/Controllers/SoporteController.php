<?php

namespace App\Http\Controllers;

use App\Services\SoporteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SoporteController extends Controller
{
    /** Pantallas por las que se puede reportar (y cómo se reconocen por la página de la que se venía). */
    private const LUGARES = [
        'inicio' => 'Inicio',
        'turnos' => 'Turnos',
        'modulos' => 'Módulos',
        'servicios' => 'Servicios',
        'asignacion' => 'Asignación',
        'graficos' => 'Gráficos',
        'reportes' => 'Reportes',
        'usuarios' => 'Usuarios',
        'tv_config' => 'Config TV',
        'tv' => 'Pantalla del TV',
        'kiosco' => 'Kiosco (sacar turno)',
        'ticket' => 'Ticket impreso',
        'asesor' => 'Panel del asesor',
        'otro' => 'Otro',
    ];

    private const RUTAS = [
        '/dashboard' => 'inicio', '/admin/turnos' => 'turnos', '/cajas' => 'modulos', '/servicios' => 'servicios',
        '/asignacion-servicios' => 'asignacion', '/graficos' => 'graficos', '/reportes' => 'reportes',
        '/admin/usuarios' => 'usuarios', '/tv-config' => 'tv_config', '/tv' => 'tv', '/asesor' => 'asesor', '/turnos' => 'kiosco',
    ];

    /**
     * Soporte: formulario y, al lado, las solicitudes registradas con su estado.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $solicitudes = array_map(fn ($s) => $this->paraVista($s), app(SoporteService::class)->listar());

        return view('admin.soporte', [
            'user' => $user,
            'solicitudes' => $solicitudes,
            'lugares' => self::LUGARES,
            'donde' => $this->dondeDesde((string) $request->headers->get('referer')),
            'contacto' => config('panel.soporte.contacto'),
        ]);
    }

    /**
     * Registra la solicitud (con número y, si hay, la captura) y, si hay un correo configurado, avisa al equipo.
     */
    public function store(Request $request)
    {
        $datos = $request->validate([
            'tipo_solicitud' => 'required|in:error,mejora,nueva_funcionalidad,otro',
            'prioridad' => 'required|in:baja,media,alta,critica',
            'donde' => 'nullable|in:' . implode(',', array_keys(self::LUGARES)),
            'asunto' => 'required|string|max:255',
            'descripcion' => 'required|string|max:2000',
            'pasos_reproducir' => 'nullable|string|max:1000',
            'comportamiento_esperado' => 'nullable|string|max:1000',
            'captura' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
        ], [
            'captura.mimes' => 'La captura debe ser una imagen JPG, PNG o WEBP.',
            'captura.max' => 'La captura pesa más de 5 MB.',
        ], [
            'tipo_solicitud' => 'tipo', 'prioridad' => 'urgencia', 'donde' => 'lugar', 'asunto' => 'asunto',
            'descripcion' => 'descripción', 'pasos_reproducir' => 'pasos', 'comportamiento_esperado' => 'resultado esperado',
        ]);

        $user = Auth::user();
        $soporte = app(SoporteService::class);

        $solicitud = $soporte->crear([
            'usuario' => [
                'id' => $user->id,
                'nombre' => $user->nombre_completo,
                'correo' => $user->correo_electronico,
                'usuario' => $user->nombre_usuario,
            ],
            'tipo' => $datos['tipo_solicitud'] === 'nueva_funcionalidad' ? 'mejora' : $datos['tipo_solicitud'],
            'urgencia' => $datos['prioridad'],
            'donde' => $datos['donde'] ?? null,
            'asunto' => trim($datos['asunto']),
            'descripcion' => trim($datos['descripcion']),
            'pasos' => trim((string) ($datos['pasos_reproducir'] ?? '')) ?: null,
            'esperado' => trim((string) ($datos['comportamiento_esperado'] ?? '')) ?: null,
            'navegador' => Str::limit((string) $request->userAgent(), 250, ''),
        ]);

        // La captura se guarda con el número de la solicitud, fuera de public.
        if ($request->hasFile('captura')) {
            $archivo = $request->file('captura');
            $nombre = $solicitud['numero'] . '.' . strtolower($archivo->getClientOriginalExtension() ?: $archivo->extension());
            File::ensureDirectoryExists($soporte->dir() . '/adjuntos');
            $archivo->move($soporte->dir() . '/adjuntos', $nombre);
            $solicitud = $soporte->actualizar($solicitud['id'], ['adjunto' => $nombre]);
        }

        $avisado = $this->avisarPorCorreo($solicitud);

        Log::info('Solicitud de soporte registrada', ['numero' => $solicitud['numero'], 'urgencia' => $solicitud['urgencia'], 'por' => $user->nombre_usuario]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'solicitud' => $this->paraVista($solicitud), 'avisado' => $avisado]);
        }

        return redirect()->route('admin.soporte')->with('success', 'Solicitud ' . $solicitud['numero'] . ' registrada.');
    }

    /**
     * Cambia el estado de una solicitud (nueva, en curso, resuelta) y deja una nota de respuesta.
     */
    public function estado(Request $request, int $id)
    {
        $datos = $request->validate([
            'estado' => 'required|in:' . implode(',', SoporteService::ESTADOS),
            'nota' => 'nullable|string|max:1000',
        ], [], ['nota' => 'nota']);

        $solicitud = app(SoporteService::class)->actualizar($id, [
            'estado' => $datos['estado'],
            'nota' => trim((string) ($datos['nota'] ?? '')) ?: null,
            'actualizada' => now()->toIso8601String(),
            'actualizada_por' => Auth::user()->nombre_completo,
        ]);

        if (!$solicitud) {
            return response()->json(['success' => false, 'message' => 'La solicitud no existe.'], 404);
        }

        return response()->json(['success' => true, 'solicitud' => $this->paraVista($solicitud)]);
    }

    /**
     * La captura de una solicitud (solo para el panel: no está en public).
     */
    public function adjunto(int $id)
    {
        $soporte = app(SoporteService::class);
        $solicitud = $soporte->buscar($id);
        $ruta = $solicitud && $solicitud['adjunto'] ? $soporte->rutaAdjunto($solicitud['adjunto']) : null;

        abort_unless($ruta && is_file($ruta), 404);

        return response()->file($ruta, ['Content-Disposition' => 'inline; filename="' . basename($ruta) . '"']);
    }

    /** Lo que necesita la vista, con los textos ya resueltos. */
    private function paraVista(array $s): array
    {
        return $s + [
            'donde_texto' => self::LUGARES[$s['donde'] ?? ''] ?? null,
            'url_adjunto' => !empty($s['adjunto']) ? route('admin.soporte.adjunto', $s['id']) : null,
        ];
    }

    /** La pantalla de la que se venía, para no tener que elegirla. */
    private function dondeDesde(string $referer): ?string
    {
        $ruta = parse_url($referer, PHP_URL_PATH) ?: '';
        foreach (self::RUTAS as $prefijo => $lugar) {
            if ($ruta === $prefijo || str_starts_with($ruta, $prefijo . '/')) {
                return $lugar;
            }
        }
        return null;
    }

    /**
     * Correo al equipo si PANEL_SOPORTE_CORREO está definido. Devuelve true solo si de verdad salió
     * (con MAIL_MAILER=log el correo solo se escribe en el log).
     */
    private function avisarPorCorreo(array $s): bool
    {
        $destino = config('panel.soporte.correo');
        if (!$destino) {
            return false;
        }

        $tipos = ['error' => 'Algo falla', 'mejora' => 'Propuesta de cambio', 'otro' => 'Otra consulta'];
        $texto = implode("\n", array_filter([
            $s['numero'] . ' · ' . ($tipos[$s['tipo']] ?? $s['tipo']) . ' · urgencia ' . $s['urgencia'],
            'De: ' . $s['usuario']['nombre'] . ' (' . $s['usuario']['usuario'] . ')' . ($s['usuario']['correo'] ? ' · ' . $s['usuario']['correo'] : ''),
            !empty($s['donde']) ? 'Dónde: ' . (self::LUGARES[$s['donde']] ?? $s['donde']) : null,
            '',
            $s['asunto'],
            '',
            $s['descripcion'],
            $s['pasos'] ? "\nPasos:\n" . $s['pasos'] : null,
            $s['esperado'] ? "\nSe esperaba:\n" . $s['esperado'] : null,
            $s['adjunto'] ? "\nTiene captura: " . route('admin.soporte.adjunto', $s['id']) : null,
            "\nVer en el panel: " . route('admin.soporte'),
        ], fn ($l) => $l !== null));

        try {
            Mail::raw($texto, function ($m) use ($destino, $s) {
                $m->to($destino)->subject('[' . config('panel.unidad', 'Turnero') . '] ' . $s['numero'] . ' · ' . $s['asunto']);
                if (!empty($s['usuario']['correo'])) {
                    $m->replyTo($s['usuario']['correo'], $s['usuario']['nombre']);
                }
            });
            return config('mail.default') !== 'log';
        } catch (\Throwable $e) {
            report($e);
            return false;
        }
    }
}
