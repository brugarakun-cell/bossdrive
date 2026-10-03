<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'actor_user_id', 'action', 'entity_type', 'entity_id', 'metadata',
        'ip_address', 'user_agent',
    ];

    protected $casts = ['metadata' => 'array'];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public static function record(
        string $action,
        object|string $entity,
        array $metadata = [],
        ?int $actorUserId = null
    ): self {
        $request = request();
        $model = is_object($entity) ? $entity : null;

        return static::create([
            'actor_user_id' => $actorUserId
                ?? ($request->session()->get('is_staff') === true
                    ? $request->session()->get('staff_user_id')
                    : $request->session()->get('admin_user_id')),
            'action' => $action,
            'entity_type' => is_object($entity) ? $entity::class : $entity,
            'entity_id' => $model?->getKey(),
            'metadata' => $metadata,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
