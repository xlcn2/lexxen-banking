<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/dashboard'; // ou '/home' se preferir
    protected $guard;

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Attempt to log the user into the application.
     */
    protected function attemptLogin(Request $request)
    {
        // Adicione log para debug
        Log::info('Tentativa de login', ['email' => $request->email]);
        
        $credentials = $this->credentials($request);
        $remember = $request->filled('remember');

        // Tenta autenticar como pessoa física
        if (Auth::guard('individual')->attempt($credentials, $remember)) {
            $this->guard = 'individual';
            Log::info('Login bem-sucedido como individual');
            return true;
        }

        // Tenta autenticar como pessoa jurídica
        if (Auth::guard('corporate')->attempt($credentials, $remember)) {
            $this->guard = 'corporate';
            Log::info('Login bem-sucedido como corporate');
            return true;
        }

        Log::info('Falha no login - credenciais não encontradas');
        return false;
    }

    /**
     * Get the guard to be used during authentication.
     */
    protected function guard()
    {
        return Auth::guard($this->guard ?? 'individual');
    }
}