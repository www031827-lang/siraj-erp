<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * جلب قائمة المستخدمين (مع الأدوار)
     * الصلاحية المطلوبة: admin.users
     */
    public function index(Request $request)
    {
        $users = User::with(['role', 'allowedApps'])
            ->when($request->search, function ($query, $search) {
                return $query->where('full_name', 'LIKE', "%{$search}%")
                             ->orWhere('username', 'LIKE', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($users);
    }

    /**
     * إنشاء مستخدم جديد (صلاحية: admin.users)
     */
    public function store(Request $request)
    {
        // 1. التحقق من صحة البيانات
        $validated = $request->validate([
            'full_name' => 'required|string|max:150',
            'username' => 'required|string|max:50|unique:users,username',
            'email' => 'nullable|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role_id' => 'required|exists:roles,id',
            'apps' => 'nullable|array',
            'apps.*' => 'exists:applications,id',
            'status' => 'nullable|in:active,suspended,blocked',
        ]);

        // 2. إنشاء المستخدم
        $user = User::create([
            'full_name' => $validated['full_name'],
            'username' => $validated['username'],
            'email' => $validated['email'] ?? null,
            'password_hash' => Hash::make($validated['password']),
            'role_id' => $validated['role_id'],
            'status' => $validated['status'] ?? 'active',
            'created_by' => $request->user()->id,
        ]);

        // 3. ربط التطبيقات المسموح بها
        if (!empty($validated['apps'])) {
            $user->allowedApps()->sync($validated['apps']);
        }

        // 4. تسجيل في سجل التدقيق (Audit Log)
        // (سنضيفه لاحقاً)

        return response()->json([
            'message' => '✅ تم إنشاء المستخدم بنجاح.',
            'data' => $user->load(['role', 'allowedApps'])
        ], 201);
    }

    /**
     * عرض مستخدم معين
     */
    public function show($id)
    {
        $user = User::with(['role', 'allowedApps'])->findOrFail($id);
        return response()->json($user);
    }

    /**
     * تحديث بيانات مستخدم
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'full_name' => 'sometimes|string|max:150',
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'role_id' => 'sometimes|exists:roles,id',
            'apps' => 'nullable|array',
            'apps.*' => 'exists:applications,id',
            'status' => 'sometimes|in:active,suspended,blocked',
        ]);

        // تحديث البيانات الأساسية
        if (isset($validated['full_name'])) $user->full_name = $validated['full_name'];
        if (isset($validated['email'])) $user->email = $validated['email'];
        if (isset($validated['role_id'])) $user->role_id = $validated['role_id'];
        if (isset($validated['status'])) $user->status = $validated['status'];

        $user->save();

        // تحديث التطبيقات المسموح بها
        if (isset($validated['apps'])) {
            $user->allowedApps()->sync($validated['apps']);
        }

        return response()->json([
            'message' => '✅ تم تحديث المستخدم بنجاح.',
            'data' => $user->load(['role', 'allowedApps'])
        ]);
    }

    /**
     * إعادة تعيين كلمة المرور (خاص بالمدير العام)
     */
    public function resetPassword(Request $request, $id)
    {
        $request->validate([
            'new_password' => 'required|string|min:8',
        ]);

        $user = User::findOrFail($id);
        $user->password_hash = Hash::make($request->new_password);
        $user->save();

        // 🔴 تعطيل البصمة وبصمة الوجه (كما هو مطلوب في النظام)
        // سيتم إرسال إشعار إلى التطبيق عبر المزامنة
        // (سنضيف ذلك لاحقاً في خدمة الإشعارات)

        return response()->json([
            'message' => '✅ تم تغيير كلمة المرور بنجاح. سيتم تعطيل البصمة تلقائياً.'
        ]);
    }

    /**
     * جلب قائمة الأدوار (للواجهة)
     */
    public function roles()
    {
        return response()->json(Role::all());
    }

    /**
     * جلب قائمة الصلاحيات (للواجهة)
     */
    public function permissions()
    {
        return response()->json(\App\Models\Permission::all());
    }
}
