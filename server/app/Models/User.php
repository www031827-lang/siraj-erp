<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // اسم الجدول كما في قاعدة البيانات
    protected $table = 'users';

    /**
     * الحقول القابلة للتعبئة (Mass Assignment)
     */
    protected $fillable = [
        'full_name',
        'username',
        'email',
        'password_hash',
        'role_id',
        'status',
        'created_by',
    ];

    /**
     * الحقول المخفية عند التحويل إلى JSON
     */
    protected $hidden = [
        'password_hash',
    ];

    /**
     * تحويلات البيانات (Casts)
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ------------------- العلاقات (Relations) -------------------

    /**
     * العلاقة مع الدور (كل مستخدم له دور واحد)
     */
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * العلاقة مع الصلاحيات عبر الدور (Many-to-Many غير مباشر)
     */
    public function permissions()
    {
        return $this->hasManyThrough(
            Permission::class,
            Role::class,
            'id',                    // مفتاح الدور الأساسي
            'role_id',               // مفتاح الصلاحية المرتبط بالدور
            'role_id',               // مفتاح المستخدم الأجنبي
            'id'                     // مفتاح الدور الأساسي في جدول role_permissions
        );
    }

    /**
     * التحقق من وجود صلاحية معينة للمستخدم
     */
    public function hasPermission($permissionName): bool
    {
        return $this->permissions()->where('permission', $permissionName)->exists();
    }

    /**
     * جلب التطبيقات المسموح بها للمستخدم (للمتجر الخاص)
     */
    public function allowedApps()
    {
        return $this->belongsToMany(Application::class, 'user_apps', 'user_id', 'app_id');
    }

    // ------------------- الملحقات (Accessors) -------------------

    /**
     * إرجاع كلمة المرور المشفرة (للتوافق مع هيكل قاعدة البيانات)
     */
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    /**
     * إرجاع اسم المستخدم (للـ API)
     */
    public function getNameAttribute()
    {
        return $this->full_name;
    }
}
