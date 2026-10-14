<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'permissions',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'permissions' => 'array',
        ];
    }

    /**
     * Enseignant associé à ce compte utilisateur
     */
    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    /**
     * Groupes créés par cet utilisateur.
     */
    public function createdGroups(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Group::class, 'user_id');
    }

    /**
     * Clés de toutes les permissions disponibles dans le système
     */
    public static function allPermissionKeys(): array
    {
        return [
            'dashboard',
            'students',
            'attendance',
            'daycare',
            'teachers',
            'schedule',
            'payments',
            'schoolcalendar',
            'reports',
            'can_delete',
            'teacher_portal',
        ];
    }

    /**
     * Permissions standard par défaut pour le personnel
     */
    public static function defaultPersonnelPermissions(): array
    {
        return [
            'dashboard',
            'students',
            'attendance',
            'daycare',
            'teachers',
            'schedule',
            'payments',
            'schoolcalendar',
        ];
    }

    /**
     * Permissions standard par défaut pour les enseignants
     */
    public static function defaultTeacherPermissions(): array
    {
        return [
            'teacher_portal',
            'schedule',
            'attendance',
        ];
    }

    /**
     * Obtenir la liste effective des permissions
     */
    public function getEffectivePermissions(): array
    {
        if ($this->role === 'director') {
            return self::allPermissionKeys();
        }

        if ($this->role === 'teacher') {
            return is_null($this->permissions) ? self::defaultTeacherPermissions() : (array) $this->permissions;
        }

        if (is_null($this->permissions)) {
            return self::defaultPersonnelPermissions();
        }

        return (array) $this->permissions;
    }

    /**
     * Vérifier si l'utilisateur possède une permission donnée
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->role === 'director') {
            return true;
        }

        $effective = $this->getEffectivePermissions();
        return in_array($permission, $effective, true);
    }
}
