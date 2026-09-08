<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * التحقق من وجود صلاحية معينة في الطلب
     */
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        $user = $request->user();

        // إذا كان الطلب غير محدد بصلاحية معينة، نمرره
        if (empty($permissions)) {
            return $next($request);
        }

        // التحقق من أن المستخدم لديه واحدة على الأقل من الصلاحيات المطلوبة
        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                return $next($request);
            }
        }

        // إذا لم يمتلك أي من الصلاحيات، نرفض الطلب
        return response()->json([
            'message' => '⛔ غير مصرح لك بتنفيذ هذا الإجراء.'
        ], 403);
    }
}
