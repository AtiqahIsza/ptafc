<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\StageMap
 *
 * @property int $id
 * @property string $latitude
 * @property string $longitude
 * @property int $sequence
 * @property int $stage_id
 * @method static Builder|RouteMap newModelQuery()
 * @method static Builder|RouteMap newQuery()
 * @method static Builder|RouteMap query()
 * @method static Builder|RouteMap whereCreatedAt($value)
 * @method static Builder|RouteMap whereId($value)
 * @method static Builder|RouteMap whereLatitude($value)
 * @method static Builder|RouteMap whereLongitude($value)
 * @method static Builder|RouteMap whereSequence($value)
 * @method static Builder|RouteMap whereStageId($value)
 */

class StageMap extends Model
{
    use HasFactory;

    protected $table = 'stage_map';
    public $timestamps = false;

    protected $fillable = [
        'latitude',
        'longitude',
        'sequence',
        'stage_id',
    ];

    function Stage() {
        return $this->belongsTo(Stage::class, 'stage_id', 'id');
    }
}
