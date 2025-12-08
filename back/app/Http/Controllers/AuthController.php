<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ChangePasswordRequest; // Criar este Request
use App\Http\Resources\AuthPayloadResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // [cite: 19]
    public function login(LoginRequest $request)
    {
        $user = User::where('cpf', $request->cpf)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'cpf' => ['CPF ou senha inválidos.'],
            ]);
        }

        // Revoga tokens antigos
        $user->tokens()->delete();

        // [cite: 20]
        $token = $user->createToken('shc-token')->plainTextToken;

        return new AuthPayloadResource($user->load('curso'), $token);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->noContent();
    }

    // [cite: 56]
    public function changePassword(Request $request) 
    {
        $user = $request->user();

        // Validação
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // CORREÇÃO: Adicionado Hash::make() para criptografar a senha
        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return response()->noContent();
    }
}
