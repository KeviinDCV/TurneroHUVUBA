<?php

namespace App\Http\Controllers;

use App\Models\Servicio;
use App\Models\Turno;
use App\Models\TurnoHistorial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ServicioController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Catálogo en una sola vista: cada sección del kiosco con sus subservicios debajo (sin paginar: son pocos)
     * y lo que importa en la operación: código del ticket, asesores asignados, cola de hoy, TV y prioridad.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $search = (string) $request->get('search', '');

        $servicios = Servicio::orderBy('orden')->orderBy('nombre')->get();

        // Asesores con el servicio asignado (solo rol Asesor: son los que llaman turnos).
        $asesores = DB::table('user_servicio')
            ->join('users', 'users.id', '=', 'user_servicio.user_id')
            ->where('users.rol', 'Asesor')
            ->when(\App\Models\User::soportaDesactivacion(), fn ($q) => $q->whereNull('users.fecha_desactivacion'))
            ->groupBy('user_servicio.servicio_id')
            ->selectRaw('user_servicio.servicio_id, COUNT(*) AS n')
            ->pluck('n', 'servicio_id');

        // En cola hoy: en espera + aplazados de hoy (por created_at: fecha_creacion se reescribe sola).
        $enCola = Turno::whereIn('estado', ['pendiente', 'aplazado'])
            ->whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])
            ->groupBy('servicio_id')
            ->selectRaw('servicio_id, COUNT(*) AS n')
            ->pluck('n', 'servicio_id');

        $fila = function (Servicio $s) use ($asesores, $enCola) {
            // huvuba imprime en el ticket a dónde ir según el NOMBRE de la sección (Servicio::ubicacionAtencion).
            $ubicacion = null;
            if ($s->nivel === 'servicio' && method_exists($s, 'ubicacionAtencion')) {
                $u = $s->ubicacionAtencion();
                $ubicacion = $u ? trim($u['sitio'] . ' ' . $u['rango']) : null;
            }

            return [
                'id' => (int) $s->id,
                'nombre' => $s->nombre,
                'codigo' => $s->codigo,
                'descripcion' => $s->descripcion,
                'padre_id' => $s->servicio_padre_id ? (int) $s->servicio_padre_id : null,
                'activo' => $s->estado === 'activo',
                'orden' => $s->orden,
                'ocultar_turno' => (bool) $s->ocultar_turno,
                'requiere_priorizacion' => (bool) $s->requiere_priorizacion,
                'asesores' => (int) ($asesores[$s->id] ?? 0),
                'en_cola' => (int) ($enCola[$s->id] ?? 0),
                'ubicacion' => $ubicacion,
            ];
        };

        // Árbol: secciones (nivel servicio) y, debajo, sus subservicios. Un subservicio cuyo padre ya no existe
        // se muestra como sección para que no desaparezca de la lista.
        $existe = $servicios->pluck('id')->flip();
        $hijosDe = $servicios->filter(fn ($s) => $s->nivel === 'subservicio' && $s->servicio_padre_id && isset($existe[$s->servicio_padre_id]))
            ->groupBy('servicio_padre_id');

        $secciones = $servicios
            ->reject(fn ($s) => $s->nivel === 'subservicio' && $s->servicio_padre_id && isset($existe[$s->servicio_padre_id]))
            ->map(function (Servicio $s) use ($fila, $hijosDe) {
                $seccion = $fila($s);
                $seccion['hijos'] = $hijosDe->get($s->id, collect())->map($fila)->values()->all();
                return $seccion;
            })
            ->values()
            ->all();

        return view('admin.servicios', compact('user', 'secciones', 'search'));
    }

    /**
     * Crear un servicio. El nivel sale de "Pertenece a": sin sección es una sección del kiosco; con sección, un subservicio.
     */
    public function store(Request $request)
    {
        $datos = $this->validar($request, null);

        if ($datos['orden'] === null) {
            $datos['orden'] = (Servicio::where('servicio_padre_id', $datos['servicio_padre_id'])->max('orden') ?? 0) + 1;
        }

        Servicio::create($datos);
        $this->quitarPrioridadAlPadre($datos['servicio_padre_id']);

        return $this->respuesta($request, 'Servicio creado');
    }

    /**
     * Actualizar un servicio.
     */
    public function update(Request $request, Servicio $servicio)
    {
        $datos = $this->validar($request, $servicio);

        // Con subservicios activos, la prioridad la piden ellos, no la sección.
        if ($servicio->subservicios()->where('estado', 'activo')->exists()) {
            $datos['requiere_priorizacion'] = false;
        }

        $servicio->update($datos);
        $this->quitarPrioridadAlPadre($datos['servicio_padre_id']);

        return $this->respuesta($request, 'Servicio actualizado');
    }

    /**
     * Eliminar. Los turnos, su historial (base de Reportes y Gráficos) y las asignaciones se borran EN CASCADA
     * con el servicio, así que solo se elimina un servicio sin turnos y sin subservicios. Para el resto: desactivarlo.
     */
    public function destroy(Servicio $servicio)
    {
        $impacto = $this->impacto($servicio);

        if ($impacto['hijos'] > 0) {
            return response()->json([
                'success' => false,
                'message' => "Tiene {$impacto['hijos']} subservicio(s). Elimínalos o muévelos a otra sección primero.",
            ], 422);
        }

        if ($impacto['turnos'] > 0 || $impacto['historial'] > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Tiene turnos registrados: al eliminarlo se borrarían también de Reportes y Gráficos. Desactívalo en su lugar.',
                'puede_desactivar' => true,
            ], 422);
        }

        $servicio->delete();

        return response()->json(['success' => true, 'message' => 'Servicio eliminado']);
    }

    /**
     * Get servicios for AJAX requests
     */
    public function getServicios(Request $request)
    {
        $search = $request->get('search');

        $query = Servicio::with('servicioPadre');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%")
                  ->orWhere('codigo', 'like', "%{$search}%");
            });
        }

        $servicios = $query->orderBy('nivel')
                          ->orderBy('servicio_padre_id')
                          ->orderBy('orden')
                          ->get();

        return response()->json($servicios);
    }

    /**
     * Un servicio con lo que se perdería al eliminarlo (lo usa la confirmación de eliminar).
     */
    public function show(Servicio $servicio)
    {
        $servicio->load('servicioPadre', 'subservicios');
        $servicio->tiene_subservicios = $servicio->subservicios()->where('estado', 'activo')->count() > 0;
        $servicio->impacto = $this->impacto($servicio);

        return response()->json($servicio);
    }

    /**
     * Valida y normaliza el formulario (crear o editar) y devuelve los datos para guardar.
     */
    private function validar(Request $request, ?Servicio $servicio): array
    {
        $codigo = strtoupper(trim((string) $request->input('codigo', '')));
        $request->merge(['codigo' => $codigo === '' ? null : $codigo]);

        // El código es la letra del ticket (C-001) y la voz del TV la deletrea: solo letras. A un código antiguo que
        // no cumpla (p. ej. con guion) no se le exige nada mientras no se cambie, para no bloquear la edición.
        $cambiaCodigo = !$servicio || $codigo !== strtoupper((string) $servicio->codigo);

        $validador = validator($request->all(), [
            'nombre' => 'required|string|max:255',
            'codigo' => $cambiaCodigo
                ? ['required', 'regex:/^[A-Z]{1,10}$/', Rule::unique('servicios', 'codigo')->ignore($servicio?->id)]
                : ['nullable'],
            'servicio_padre_id' => ['nullable', 'integer', Rule::exists('servicios', 'id')->where('nivel', 'servicio')],
            'descripcion' => 'nullable|string|max:500',
            'orden' => 'nullable|integer|min:0|max:9999',
        ], [
            'codigo.required' => 'Escribe el código: son las letras del ticket (la C de C-001).',
            'codigo.regex' => 'Solo letras, sin guiones ni números: la voz del TV las deletrea antes del número.',
            'codigo.unique' => 'Ya hay un servicio con ese código.',
            'servicio_padre_id.exists' => 'Elige una sección de la lista.',
        ], [
            'orden' => 'posición',
            'descripcion' => 'descripción',
        ]);

        $validador->after(function ($v) use ($request, $servicio) {
            $padreId = $request->input('servicio_padre_id');
            if (!$servicio || !$padreId) {
                return;
            }
            if ((int) $padreId === (int) $servicio->id) {
                $v->errors()->add('servicio_padre_id', 'Un servicio no puede estar dentro de sí mismo.');
            } elseif ($servicio->subservicios()->exists()) {
                $v->errors()->add('servicio_padre_id', 'Tiene subservicios: una sección con subservicios no puede ir dentro de otra.');
            }
        });

        $validador->validate();

        $padreId = $request->filled('servicio_padre_id') ? (int) $request->input('servicio_padre_id') : null;

        return [
            'nombre' => trim((string) $request->input('nombre')),
            'codigo' => $cambiaCodigo ? $codigo : $servicio->codigo,
            'nivel' => $padreId ? 'subservicio' : 'servicio',
            'servicio_padre_id' => $padreId,
            'estado' => $request->boolean('activo') ? 'activo' : 'inactivo',
            'descripcion' => trim((string) $request->input('descripcion')) ?: null,
            'orden' => $request->filled('orden') ? (int) $request->input('orden') : $servicio?->orden,
            'ocultar_turno' => $request->boolean('ocultar_turno'),
            'requiere_priorizacion' => $request->boolean('requiere_priorizacion'),
        ];
    }

    /**
     * Una sección con subservicios no pide prioridad en el kiosco: la piden sus subservicios.
     */
    private function quitarPrioridadAlPadre(?int $padreId): void
    {
        if ($padreId) {
            Servicio::where('id', $padreId)->where('requiere_priorizacion', true)->update(['requiere_priorizacion' => false]);
        }
    }

    /**
     * Lo que depende del servicio y se borraría en cascada con él.
     */
    private function impacto(Servicio $servicio): array
    {
        return [
            'hijos' => $servicio->subservicios()->count(),
            'turnos' => Turno::where('servicio_id', $servicio->id)->count(),
            'historial' => TurnoHistorial::where('servicio_id', $servicio->id)->count(),
            'asesores' => DB::table('user_servicio')->where('servicio_id', $servicio->id)->count(),
        ];
    }

    private function respuesta(Request $request, string $mensaje)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $mensaje]);
        }

        return redirect()->route('admin.servicios')->with('success', $mensaje);
    }
}
