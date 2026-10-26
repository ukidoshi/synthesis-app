<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chemist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * @OA\Info(
 *     title="API Этапов синтеза",
 *     version="1.0.0",
 *     description="REST API учёта этапов химического синтеза. Публичное чтение без токена; создание и изменение — JWT Bearer."
 * )
 *
 * @OA\Server(
 *     url="/api",
 *     description="API prefix"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="JWT из POST /chemists/login. Заголовок: Authorization: Bearer {access_token}"
 * )
 *
 * @OA\Tag(name="Chemists", description="Регистрация, вход и выход")
 * @OA\Tag(name="Synthesis Stages", description="Этапы синтеза, лента, черновики, лайки")
 *
 * @OA\Schema(
 *     schema="Chemist",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="full_name", type="string", example="Начын Сарыглар"),
 *     @OA\Property(property="email", type="string", format="email", example="saryglar@bmstu.ru"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="LoginResponse",
 *     type="object",
 *     @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGc..."),
 *     @OA\Property(property="token_type", type="string", example="bearer")
 * )
 *
 * @OA\Schema(
 *     schema="SynthesisStage",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=3),
 *     @OA\Property(property="name", type="string", example="Ацетилирование 4-аминофенола"),
 *     @OA\Property(property="brief_description", type="string", example="Краткое описание реакции"),
 *     @OA\Property(property="status", type="string", enum={"draft","published","deleted"}, example="published"),
 *     @OA\Property(property="image_url", type="string", nullable=true, example="http://localhost:9000/bucket/stages/images/abc.jpg"),
 *     @OA\Property(property="video_url", type="string", nullable=true, example="http://localhost:9000/bucket/stages/videos/abc.mp4"),
 *     @OA\Property(property="yield_percentage", type="number", format="float", example=91.0),
 *     @OA\Property(property="temperature_celsius", type="integer", example=100),
 *     @OA\Property(property="likes_count", type="integer", example=2),
 *     @OA\Property(property="is_owner", type="integer", enum={0,1}, description="1 — этап текущего пользователя", example=0),
 *     @OA\Property(property="is_liked", type="integer", enum={0,1}, description="Только в ленте: 1 — текущий химик (из JWT) лайкнул этап, у гостя 0", example=0),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="SynthesisStageResponse",
 *     type="object",
 *     @OA\Property(property="data", ref="#/components/schemas/SynthesisStage")
 * )
 *
 * @OA\Schema(
 *     schema="SynthesisStageCollectionResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/SynthesisStage")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="MessageResponse",
 *     type="object",
 *     @OA\Property(property="message", type="string", example="Операция выполнена")
 * )
 *
 * @OA\Schema(
 *     schema="ValidationError",
 *     type="object",
 *     @OA\Property(property="message", type="string", example="The given data was invalid."),
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *         additionalProperties=@OA\Schema(type="array", @OA\Items(type="string"))
 *     )
 * )
 */
class ChemistAuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/chemists/register",
     *     tags={"Chemists"},
     *     summary="Регистрация химика",
     *     description="Создание учётной записи. Авторизация не требуется.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={"full_name","email","password"},
     *                 @OA\Property(property="full_name", type="string", maxLength=255, example="Начын Сарыглар"),
     *                 @OA\Property(property="email", type="string", format="email", maxLength=255, example="saryglar@bmstu.ru"),
     *                 @OA\Property(property="password", type="string", format="password", minLength=6, example="secret123")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Пользователь создан",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/Chemist")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Ошибка валидации (email занят и т.д.)", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:chemists,email'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        Chemist::create([
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        return response()->json([], 201);
    }

    /**
     * @OA\Post(
     *     path="/chemists/login",
     *     tags={"Chemists"},
     *     summary="Вход (JWT)",
     *     description="Выдаёт access_token для заголовка Authorization. Авторизация не требуется.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={"email","password"},
     *                 @OA\Property(property="email", type="string", format="email", example="saryglar@bmstu.ru"),
     *                 @OA\Property(property="password", type="string", format="password", example="secret123")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Успешная аутентификация", @OA\JsonContent(ref="#/components/schemas/LoginResponse")),
     *     @OA\Response(
     *         response=401,
     *         description="Неверный email или пароль",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Неверный email или пароль")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Ошибка валидации", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // attempt автоматически генерирует JWT, если данные верны
        if (!$token = Auth::guard('api')->attempt($credentials)) {
            return response()->json(['message' => 'Неверный email или пароль'], 401);
        }

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
        ]);
    }

    /**
     * @OA\Post(
     *     path="/chemists/logout",
     *     tags={"Chemists"},
     *     summary="Выход (blacklist JWT)",
     *     description="Инвалидирует текущий токен (blacklist).",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Токен отозван", @OA\JsonContent(type="object")),
     *     @OA\Response(response=401, description="Не авторизован", @OA\JsonContent(ref="#/components/schemas/MessageResponse"))
     * )
     */
    public function logout()
    {
        // Invalidate добавляет текущий токен в Blacklist (в Redis)
        Auth::guard('api')->logout();

        return response()->json();
    }
}
