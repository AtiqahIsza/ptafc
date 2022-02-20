<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\RouteMap
 *
 * @property int $id
 * @property string $latitude
 * @property string $longitude
 * @property int $sequence
 * @property int $route_id
 * @method static Builder|RouteMap newModelQuery()
 * @method static Builder|RouteMap newQuery()
 * @method static Builder|RouteMap query()
 * @method static Builder|RouteMap whereCreatedAt($value)
 * @method static Builder|RouteMap whereId($value)
 * @method static Builder|RouteMap whereLatitude($value)
 * @method static Builder|RouteMap whereLongitude($value)
 * @method static Builder|RouteMap whereSequence($value)
 * @method static Builder|RouteMap whereRouteId($value)
 */
class RouteMap extends Model
{
    use HasFactory;

    protected $table = 'route_map';
    public $timestamps = false;

    protected $fillable = [
        'latitude',
        'longitude',
        'sequence',
        'route_id',
    ];

    function Route() {
        return $this->belongsTo(Route::class, 'route_id', 'id');
    }
}
