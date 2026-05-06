<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer\Consumer;
use App\Http\Requests\Customer\ConsumerLoginRequest;
use App\Http\Requests\Customer\ConsumerRegisterRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class ConsumerAuthController extends Controller
{
    public function register(ConsumerRegisterRequest $request): JsonResponse
    {
        $consumer = Consumer::create($request->validated());

        $token = $this->issueTokenIfAvailable($consumer);

        return response()->json([
            'message' => 'تم التسجيل بنجاح.',
            'token' => $token,
            'consumer' => $consumer,
        ], 201);
    }

    public function login(ConsumerLoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        $consumer = Consumer::where('phone', $data['phone'])->first();

        if (! $consumer || ! Hash::check($data['password'], $consumer->password)) {
            return response()->json(['message' => 'بيانات الدخول غير صحيحة.'], 422);
        }

        if ($consumer->status !== 'active') {
            return response()->json(['message' => 'الحساب غير نشط.'], 403);
        }

        $token = $this->issueTokenIfAvailable($consumer);

        return response()->json([
            'message' => 'تم تسجيل الدخول بنجاح.',
            'token' => $token,
            'consumer' => $consumer,
        ]);
    }

    public function logout(): JsonResponse
    {
        $consumer = request()->user();

        if ($consumer && method_exists($consumer, 'currentAccessToken')) {
            $consumer->currentAccessToken()?->delete();
        }

        return response()->json(['message' => 'تم تسجيل الخروج بنجاح.']);
    }

    private function issueTokenIfAvailable(Consumer $consumer): ?string
    {
        if (! class_exists(\Laravel\Sanctum\PersonalAccessToken::class)) {
            return null;
        }

        if (! method_exists($consumer, 'createToken')) {
            return null;
        }

        return $consumer->createToken('consumer-token')->plainTextToken;
    }
}