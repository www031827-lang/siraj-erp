<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * تسجيل الدخول وإنشاء توكن API
     */
    public function login(LoginRequest $request): JsonResponse
    {
        // 1. البحث عن المستخدم باسم المستخدم
        $user = User::with(['role', 'allowedApps'])
            ->where('username', $request->username)
            ->first();

        // 2. التحقق من صحة البيانات
        if (!$user || !Hash::check($request->password, $user->password_hash)) {
            return response()->json([
                'message' => '❌ اسم المستخدم أو كلمة المرور غير صحيحة.'
            ], 401);
        }

        // 3. التحقق من حالة المستخدم
        if ($user->status !== 'active') {
            return response()->json([
                'message' => '⛔ الحساب غير نشط. يرجى التواصل مع المدير العام.'
            ], 403);
        }

        // 4. إنشاء التوكن (مع تحديد صلاحياته وقدرته على الوصول)
        $token = $user->createToken('siraj-mobile-token', ['*'])->plainTextToken;

        // 5. تسجيل آخر دخول (اختياري)
        $user->last_login_at = now();
        $user->save();

        // 6. إرجاع البيانات مع التوكن
        return response()->json([
            'status' => 'success',
            'message' => '✅ تم تسجيل الدخول بنجاح.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'full_name' => $user->full_name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'role' => $user->role->name ?? null,
                    'status' => $user->status,
                ],
                'allowed_apps' => $user->allowedApps->pluck('app_code'), // ['general_manager', 'finance', ...]
                'permissions' => $user->permissions->pluck('permission'), // ['sales.read', 'finance.collect', ...]
                'token' => $token,
            ]
        ], 200);
    }

    /**
     * تسجيل الخروج (حذف التوكن الحالي)
     */
    public function logout(): JsonResponse
    {
        request()->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => '✅ تم تسجيل الخروج بنجاح.'
        ], 200);
    }

    /**
     * جلب بيانات المستخدم الحالي (للتحقق من الجلسة)
     */
    public function me(): JsonResponse
    {
        $user = request()->user()->load(['role', 'allowedApps']);

        return response()->json([
            'data' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role->name ?? null,
                'allowed_apps' => $user->allowedApps->pluck('app_code'),
                'permissions' => $user->permissions->pluck('permission'),
            ]
        ], 200);
    }
}
