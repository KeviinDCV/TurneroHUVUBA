<?php

namespace App\Http\Controllers;

use App\Models\Multimedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MultimediaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Mostrar la página de gestión multimedia
     */
    public function index()
    {
        $user = Auth::user();
        $multimedia = Multimedia::orderBy('orden')->orderBy('created_at')->get();

        return view('admin.multimedia', compact('user', 'multimedia'));
    }

    /**
     * Subir nuevo archivo multimedia
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'archivo' => 'required|file|mimes:jpg,jpeg,png,gif,mp4,mov,avi|max:51200', // 50MB max
                'nombre' => 'required|string|max:255',
                'duracion' => 'required|integer|min:1|max:300' // 1-300 segundos
            ]);

            $archivo = $request->file('archivo');
            $extension = $archivo->getClientOriginalExtension();
            $tipo = in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif']) ? 'imagen' : 'video';

            // Generar nombre único para el archivo
            $nombreArchivo = Str::uuid() . '.' . $extension;

            // Guardar archivo directamente en public/storage/multimedia
            $destinoPath = public_path('storage/multimedia');
            if (!file_exists($destinoPath)) {
                mkdir($destinoPath, 0755, true);
            }
            $archivo->move($destinoPath, $nombreArchivo);
            $rutaArchivo = 'multimedia/' . $nombreArchivo;

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

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Archivo subido correctamente',
                    'multimedia' => $multimedia
                ]);
            }

            return redirect()->route('admin.multimedia')
                ->with('success', 'Archivo subido correctamente');

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
                    'message' => 'Error al subir el archivo: ' . $e->getMessage()
                ], 500);
            }
            throw $e;
        }
    }

    /**
     * Actualizar orden de multimedia
     */
    public function updateOrder(Request $request)
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
    public function toggleActive(Request $request, $id)
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
    public function destroy($id)
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
}
