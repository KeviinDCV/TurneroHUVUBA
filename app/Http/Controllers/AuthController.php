<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Mostrar formulario de login
     */
    public function showLogin()
    {
        return view('admin.login');
    }

    /**
     * Procesar login
     */
    public function login(Request $request)
    {
        $request->validate([
            'usuario' => 'required|string',
            'password' => 'required|string',
        ]);

        // Buscar usuario por nombre_usuario
        $user = User::where('nombre_usuario', $request->usuario)->first();

        if ($user && Hash::check($request->password, $user->password)) {
            Auth::login($user);

            // Verificar que la autenticación funcionó
            if (Auth::check()) {
                // Redirigir según el rol
                if ($user->esAdministrador()) {
                    return redirect()->route('admin.dashboard');
                } else {
                    // Para asesores, redirigir a la selección de caja
                    return redirect()->route('asesor.seleccionar-caja');
                }
            } else {
                return back()->withErrors([
                    'usuario' => 'Error al iniciar sesión. Inténtalo de nuevo.',
                ])->withInput();
            }
        }

        return back()->withErrors([
            'usuario' => 'Las credenciales proporcionadas no coinciden con nuestros registros.',
        ])->withInput();
    }

    /**
     * Cerrar sesión
     */
    public function logout()
    {
        Auth::logout();
        return redirect()->route('admin.login');
    }
}
