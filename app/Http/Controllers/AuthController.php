<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse|JsonResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        return $request->expectsJson() ? response()->json(['message' => 'Sesión iniciada.', 'user' => $request->user()]) : redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse|JsonResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $request->expectsJson() ? response()->json(['message' => 'Sesión cerrada.']) : redirect()->route('login')->with('success', 'Sesión cerrada correctamente.');
    }
}
