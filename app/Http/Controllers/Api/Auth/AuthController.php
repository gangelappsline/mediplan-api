<?php

namespace App\Http\Controllers\Api\Auth;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Token;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;

class AuthController extends Controller
{
    /**
     * Register a new user and issue an access token.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        /** @var User $user */
        $user = DB::transaction(function () use ($data) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            /** @var RoleName $role */
            $role = RoleName::coerce($data['role']);
            $user->assignRole($role);

            return $user;
        });

        $user->load('roles');

        $token = $user->createToken('auth_token')->accessToken;

        return response()->json([
            'message' => 'Usuario registrado correctamente.',
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ], 201);
    }

    /**
     * Authenticate a user and issue an access token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        /** @var User|null $user */
        $user = User::query()->where('email', $data['email'])->first();

        if ($user === null || ! Hash::check($data['password'], $user->password)) {
            return response()->json([
                'message' => 'Las credenciales proporcionadas son incorrectas.',
            ], 401);
        }

        $user->load('roles');

        $token = $user->createToken('auth_token')->accessToken;

        return response()->json([
            'message' => 'Sesión iniciada correctamente.',
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    /**
     * Revoke the current access token (logout).
     */
    public function logout(Request $request): JsonResponse
    {
        $accessToken = $request->user()->currentAccessToken();

        // The guard assigns an OAuth2 entity built from the JWT (not the
        // Eloquent model), so its identifier is used to revoke the token.
        $tokenId = match (true) {
            $accessToken instanceof Token => $accessToken->getKey(),
            $accessToken instanceof AccessTokenEntityInterface => $accessToken->getIdentifier(),
            default => null,
        };

        if (is_string($tokenId) && $tokenId !== '') {
            Token::query()->whereKey($tokenId)->update(['revoked' => true]);
        }

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }

    /**
     * Return the authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->load('roles');

        return response()->json([
            'message' => 'Usuario autenticado.',
            'data' => [
                'user' => new UserResource($user),
            ],
        ]);
    }
}
