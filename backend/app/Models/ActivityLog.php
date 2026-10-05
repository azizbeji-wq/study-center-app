<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'user_name',
        'user_role',
        'type',
        'action',
        'message',
        'details',
        'ip_address',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Enregistre une activité avec l'utilisateur responsable (nom + rôle).
     *
     * @param mixed $user User instance, or user array, or string name
     * @param string $type payment|student|daycare|attendance|expense|teacher|schedule|account|general
     * @param string $action created|updated|deleted|recorded|login|etc.
     * @param string $message Description lisible de l'activité
     * @param array|null $details Données contextuelles facultatives
     * @param string|null $ip Adresse IP
     */
    public static function record($user, string $type, string $action, string $message, ?array $details = null, ?string $ip = null): ?self
    {
        try {
            $userId = null;
            $userName = 'Directeur';
            $userRole = 'director';

            if (is_object($user)) {
                $userId = $user->id ?? null;
                $userName = $user->name ?? 'Utilisateur';
                $userRole = $user->role ?? 'director';
            } elseif (is_string($user) && !empty($user)) {
                $userName = $user;
            }

            return self::create([
                'user_id'    => $userId,
                'user_name'  => $userName,
                'user_role'  => $userRole,
                'type'       => $type,
                'action'     => $action,
                'message'    => $message,
                'details'    => $details,
                'ip_address' => $ip,
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur enregistrement ActivityLog: ' . $e->getMessage());
            return null;
        }
    }
}
