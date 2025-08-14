<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class SoporteController extends Controller
{
    /**
     * Mostrar la página de soporte
     */
    public function index()
    {
        $user = Auth::user();
        return view('admin.soporte', compact('user'));
    }

    /**
     * Procesar el formulario de soporte
     */
    public function store(Request $request)
    {
        $request->validate([
            'tipo_solicitud' => 'required|in:error,mejora,nueva_funcionalidad,otro',
            'prioridad' => 'required|in:baja,media,alta,critica',
            'asunto' => 'required|string|max:255',
            'descripcion' => 'required|string|max:2000',
            'pasos_reproducir' => 'nullable|string|max:1000',
            'comportamiento_esperado' => 'nullable|string|max:1000',
        ]);

        $user = Auth::user();

        // Por ahora solo guardamos en logs, más adelante se puede implementar envío por email
        // o guardar en base de datos
        \Log::info('Solicitud de soporte recibida', [
            'usuario' => $user->nombre_completo,
            'email' => $user->correo_electronico,
            'tipo' => $request->tipo_solicitud,
            'prioridad' => $request->prioridad,
            'asunto' => $request->asunto,
            'descripcion' => $request->descripcion,
            'pasos_reproducir' => $request->pasos_reproducir,
            'comportamiento_esperado' => $request->comportamiento_esperado,
            'fecha' => now(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return redirect()->route('admin.soporte')
            ->with('success', 'Su solicitud de soporte ha sido enviada correctamente. Nos pondremos en contacto con usted pronto.');
    }
}
