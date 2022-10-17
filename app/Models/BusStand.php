<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
/**
 * App\Models\BusStand
 *
 * @property int $id
 * @property string $latitude
 * @property string $longitude
 * @property int $sequence
 * @property int $route_id
 * @property int $radius
 * @method static Builder|RouteMap newModelQuery()
 * @method static Builder|RouteMap newQuery()
 * @method static Builder|RouteMap query()
 * @method static Builder|RouteMap whereCreatedAt($value)
 * @method static Builder|RouteMap whereId($value)
 * @method static Builder|RouteMap whereLatitude($value)
 * @method static Builder|RouteMap whereLongitude($value)
 * @method static Builder|RouteMap whereSequence($value)
 * @method static Builder|RouteMap whereRouteId($value)
 * @method static Builder|RouteMap whereRadius($value)
 */
class BusStand extends Model
{
    use HasFactory;

    protected $table = 'bus_stand';
    //public $timestamps = false;

    protected $fillable = [
        'latitude',
        'longitude',
        'route_id',
        'radius',
        'sequence',
        'description',
        'updated_by',
        'created_by'
    ];

    function Route() {
        return $this->belongsTo(Route::class, 'route_id', 'id');
    }
    function UpdatedBy() {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }
    function CreatedBy() {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
