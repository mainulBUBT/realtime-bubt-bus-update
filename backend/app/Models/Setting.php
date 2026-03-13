<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'group', 'description'];

    /**
     * Get the casted value attribute.
     */
    public function getValueAttribute($value): mixed
    {
        if (is_null($value)) {
            return null;
        }

        return match($this->type) {
            'boolean' => (bool) $value,
            'number' => (int) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Set the value attribute with proper casting.
     */
    public function setValueAttribute($value): void
    {
        $this->attributes['value'] = match($this->type) {
            'boolean' => $value ? '1' : '0',
            'json' => json_encode($value),
            default => (string) $value,
        };
    }

    /**
     * Scope to get settings by group.
     */
    public function scopeGroup($query, string $group)
    {
        return $query->where('group', $group);
    }
}
