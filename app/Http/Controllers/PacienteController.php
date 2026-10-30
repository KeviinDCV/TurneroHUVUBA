<?php

namespace App\Http\Controllers;

use App\Models\Paciente;
use App\Models\PacienteCarga;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Carga de la base de pacientes desde Servicios. El archivo lo lee el navegador (public/js/lector-pacientes.js) y aquí
 * llegan, por tandas, solo documento y nombre: el Excel, con teléfonos y observaciones médicas, nunca sale del computador.
 *
 * Las tandas se guardan en una tabla de paso (pacientes_nuevos) que nadie consulta. Solo al terminar, si llegó todo, esa
 * tabla pasa a ser la base de una sola vez. Mientras tanto el atril sigue con la base de siempre, y una carga cancelada
 * o interrumpida no cambia nada.
 */
class PacienteController extends Controller
{
    private const MAX_POR_TANDA = 1000;
    private const TABLA_DE_PASO = 'pacientes_nuevos';

    /**
     * Empezar una carga. Si había otra sin terminar, esa deja de valer.
     */
    public function iniciar(Request $request): JsonResponse
    {
        if ($sinCifrar = $this->exigirConexionCifrada($request)) {
            return $sinCifrar;
        }

        $datos = $request->validate([
            'archivo' => 'nullable|string|max:190',
            'filas' => 'nullable|integer|min:0|max:4000000000',
        ]);

        $carga = PacienteCarga::create([
            'archivo' => $datos['archivo'] ?? null,
            'filas' => $datos['filas'] ?? 0,
            'estado' => 'en_curso',
            'cargado_por' => Auth::user()->nombre_completo ?? null,
        ]);
        DB::table(self::TABLA_DE_PASO)->truncate();

        return response()->json(['success' => true, 'carga' => $carga->id]);
    }

    /**
     * Una tanda: [[documento, nombre], ...]. Repetirla no cambia nada, así el navegador puede reintentar.
     */
    public function tanda(Request $request, int $carga): JsonResponse
    {
        if ($sinCifrar = $this->exigirConexionCifrada($request)) {
            return $sinCifrar;
        }
        if (!$this->esVigente($carga)) {
            return $this->cargaNoVigente();
        }

        $request->validate(['filas' => 'required|array|min:1|max:' . self::MAX_POR_TANDA]);

        $filas = [];
        $rechazadas = 0;
        foreach ($request->input('filas') as $fila) {
            $documento = is_array($fila) ? Paciente::claveDocumento($fila[0] ?? null) : null;
            $nombre = is_array($fila) ? Paciente::limpiarNombre($fila[1] ?? null) : null;

            if ($documento === null || $nombre === null) {
                $rechazadas++;
                continue;
            }
            $filas[$documento] = ['documento' => $documento, 'nombre' => $nombre, 'carga_id' => $carga];
        }

        if ($filas) {
            try {
                DB::table(self::TABLA_DE_PASO)->upsert(array_values($filas), ['documento'], ['nombre', 'carga_id']);
            } catch (QueryException $e) {
                // El mensaje de la excepción trae el SQL con los documentos y nombres: no va al log. 503: el navegador reintenta.
                Log::error('Tanda de pacientes no guardada', ['carga' => $carga, 'codigo' => $e->errorInfo[1] ?? $e->getCode()]);

                return response()->json(['success' => false, 'message' => 'El servidor no pudo guardar los datos.'], 503);
            }
        }

        return response()->json(['success' => true, 'guardadas' => count($filas), 'rechazadas' => $rechazadas]);
    }

    /**
     * Cerrar la carga: si llegó todo, la tabla de paso pasa a ser la base, de una sola vez.
     */
    public function terminar(Request $request, int $carga): JsonResponse
    {
        $esperados = (int) $request->validate(['total' => 'required|integer|min:1'])['total'];

        // Ya terminó y se perdió la respuesta: el navegador reintentó
        $ultima = PacienteCarga::latest('id')->first();
        if ($ultima && (int) $ultima->id === $carga && $ultima->estado === 'lista' && (int) $ultima->total === $esperados) {
            return response()->json(['success' => true, 'total' => $esperados]);
        }

        if (!$this->esVigente($carga)) {
            return $this->cargaNoVigente();
        }

        $guardados = DB::table(self::TABLA_DE_PASO)->where('carga_id', $carga)->count();
        if ($guardados !== $esperados) {
            return response()->json([
                'success' => false,
                'message' => "Llegaron {$guardados} de {$esperados} pacientes. La base no cambió; vuelve a cargar el archivo.",
            ], 409);
        }

        DB::table(self::TABLA_DE_PASO)->where('carga_id', '<>', $carga)->delete();

        // RENAME cambia las dos tablas a la vez: el atril nunca ve una base a medias
        DB::statement('RENAME TABLE pacientes TO pacientes_viejos, pacientes_nuevos TO pacientes, pacientes_viejos TO pacientes_nuevos');
        PacienteCarga::whereKey($carga)->update(['estado' => 'lista', 'total' => $guardados]);
        DB::table(self::TABLA_DE_PASO)->truncate();

        Log::info('Base de pacientes del atril cargada', ['carga' => $carga, 'total' => $guardados, 'usuario' => Auth::id()]);

        return response()->json(['success' => true, 'total' => $guardados]);
    }

    /**
     * Cancelar: lo que alcanzó a llegar se borra de una vez, para que no quede guardado sin uso.
     */
    public function cancelar(int $carga): JsonResponse
    {
        if ($this->esVigente($carga)) {
            PacienteCarga::whereKey($carga)->update(['estado' => 'cancelada']);
            DB::table(self::TABLA_DE_PASO)->truncate();
        }

        return response()->json(['success' => true]);
    }

    /**
     * Eliminar la base. El atril vuelve a pedir la cédula sin comprobarla, hasta que se cargue otra.
     */
    public function eliminar(): JsonResponse
    {
        $total = Paciente::count();

        PacienteCarga::create([   // primero: una tanda en curso ya no pasa de aquí
            'estado' => 'eliminada',
            'total' => $total,
            'cargado_por' => Auth::user()->nombre_completo ?? null,
        ]);
        DB::table('pacientes')->truncate();
        DB::table(self::TABLA_DE_PASO)->truncate();

        Log::info('Base de pacientes del atril eliminada', ['total' => $total, 'usuario' => Auth::id()]);

        return response()->json(['success' => true]);
    }

    // Solo vale la carga más reciente y sin terminar: dos cargas a la vez no se pisan.
    private function esVigente(int $carga): bool
    {
        $ultima = PacienteCarga::latest('id')->first();

        return $ultima && (int) $ultima->id === $carga && $ultima->estado === 'en_curso';
    }

    private function cargaNoVigente(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Esta carga ya no es válida: se empezó otra o la base cambió. Vuelve a cargar el archivo.',
        ], 409);
    }

    // En producción los datos de los pacientes no cruzan Internet sin cifrar
    private function exigirConexionCifrada(Request $request): ?JsonResponse
    {
        if (!app()->isProduction() || $request->secure()) {
            return null;
        }

        return response()->json([
            'success' => false,
            'message' => 'Por seguridad, la base de pacientes solo se carga por https. Abre esta página con https:// e inténtalo de nuevo.',
        ], 403);
    }
}
