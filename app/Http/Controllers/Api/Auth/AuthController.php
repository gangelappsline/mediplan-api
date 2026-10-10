<?php

namespace App\Http\Controllers\Api\Auth;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\OpenApi\Schemas\AuthResponse;
use App\OpenApi\Schemas\InvalidCredentialsError;
use App\OpenApi\Schemas\LoginRequest as LoginRequestSchema;
use App\OpenApi\Schemas\MeResponse;
use App\OpenApi\Schemas\MessageResponse;
use App\OpenApi\Schemas\RegisterRequest as RegisterRequestSchema;
use App\OpenApi\Schemas\UnauthenticatedError;
use App\OpenApi\Schemas\ValidationError;
use App\Services\Businesses\BusinessProvisioner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Token;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    #[OA\Post(
        path: '/register',
        tags: ['Autenticación'],
        summary: 'Registrar un usuario',
        description: 'Crea una cuenta y devuelve inmediatamente un token personal de Laravel Passport. El rol administrador nunca puede asignarse mediante este endpoint. Envía todos los campos en JSON y usa HTTPS en entornos no locales.',
        operationId: 'register',
        parameters: [
            new OA\HeaderParameter(
                name: 'Accept',
                description: 'Solicita una respuesta JSON.',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'application/json'),
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Datos de la nueva cuenta. Todos los campos son obligatorios.',
            content: new OA\JsonContent(ref: RegisterRequestSchema::class),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Usuario creado y token emitido.',
                content: new OA\JsonContent(ref: AuthResponse::class),
            ),
            new OA\Response(
                response: 422,
                description: 'Uno o más campos no cumplen las reglas de validación. El correo duplicado también responde 422.',
                content: new OA\JsonContent(ref: ValidationError::class),
            ),
        ],
    )]
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

            // Todo negocio necesita su ficha y su configuración inicial.
            if ($role === RoleName::Business) {
                app(BusinessProvisioner::class)->provisionFor($user);
            }

            return $user;
        });

        $user->load(['roles', 'business']);

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

    #[OA\Post(
        path: '/login',
        tags: ['Autenticación'],
        summary: 'Iniciar sesión',
        description: 'Comprueba el correo y la contraseña y devuelve un token personal de Laravel Passport. La API no revela si el correo existe cuando las credenciales son incorrectas.',
        operationId: 'login',
        parameters: [
            new OA\HeaderParameter(
                name: 'Accept',
                description: 'Solicita una respuesta JSON.',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'application/json'),
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Credenciales del usuario.',
            content: new OA\JsonContent(ref: LoginRequestSchema::class),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Credenciales correctas y token emitido.',
                content: new OA\JsonContent(ref: AuthResponse::class),
            ),
            new OA\Response(
                response: 401,
                description: 'El correo no existe o la contraseña no coincide.',
                content: new OA\JsonContent(ref: InvalidCredentialsError::class),
            ),
            new OA\Response(
                response: 403,
                description: 'La cuenta existe pero un administrador la desactivó.',
                content: new OA\JsonContent(ref: InvalidCredentialsError::class),
            ),
            new OA\Response(
                response: 422,
                description: 'El cuerpo no contiene un correo válido o falta un campo requerido.',
                content: new OA\JsonContent(ref: ValidationError::class),
            ),
        ],
    )]
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

        if (! $user->is_active) {
            return response()->json([
                'message' => 'Tu cuenta está desactivada. Contacta al administrador de la plataforma.',
            ], 403);
        }

        $user->load(['roles', 'business']);

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

    #[OA\Post(
        path: '/logout',
        tags: ['Autenticación'],
        summary: 'Cerrar sesión',
        description: 'Revoca únicamente el token enviado en la solicitud. Después de una respuesta 200, elimina el token del almacenamiento del frontend y no lo reutilices.',
        operationId: 'logout',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\HeaderParameter(
                name: 'Accept',
                description: 'Solicita una respuesta JSON.',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'application/json'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Token actual revocado.',
                content: new OA\JsonContent(ref: MessageResponse::class),
            ),
            new OA\Response(
                response: 401,
                description: 'La cabecera Authorization falta, está mal formada, el token expiró o ya fue revocado.',
                content: new OA\JsonContent(ref: UnauthenticatedError::class),
            ),
        ],
    )]
    /**
     * Revoke the current access token (logout).
     */
    public function logout(Request $request): JsonResponse
    {
        $accessToken = $request->user()->token();

        // The Passport guard assigns the Eloquent Token model to the user,
        // which exposes revoke(). Transient tokens have nothing to revoke.
        if ($accessToken instanceof Token) {
            $accessToken->revoke();
        }

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }

    #[OA\Get(
        path: '/me',
        tags: ['Autenticación'],
        summary: 'Obtener el usuario autenticado',
        description: 'Devuelve el perfil asociado al token actual. Usa esta respuesta para hidratar el estado de sesión del frontend después de recargar la aplicación.',
        operationId: 'me',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\HeaderParameter(
                name: 'Accept',
                description: 'Solicita una respuesta JSON.',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'application/json'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Usuario asociado al token.',
                content: new OA\JsonContent(ref: MeResponse::class),
            ),
            new OA\Response(
                response: 401,
                description: 'La cabecera Authorization falta, está mal formada, el token expiró o fue revocado.',
                content: new OA\JsonContent(ref: UnauthenticatedError::class),
            ),
        ],
    )]
    /**
     * Return the authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->load(['roles', 'business']);

        return response()->json([
            'message' => 'Usuario autenticado.',
            'data' => [
                'user' => new UserResource($user),
            ],
        ]);
    }
}
