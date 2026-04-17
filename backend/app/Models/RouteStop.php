<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouteStop extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'route_id',
        'name',
        'lat',
        'lng',
        'sequence',
        'distance_along_route_m',
        'shape_index',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'distance_along_route_m' => 'decimal:2',
        ];
    }

    /**
     * Relationships
     */
    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }
}
