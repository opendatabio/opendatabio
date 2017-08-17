<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Location;
use App\Project;
use App\Collector;
use DB;
use Auth;

class Plant extends Model
{
//    protected static function boot()
//    {
//        parent::boot();

 //       static::addGlobalScope('id', function (Builder $builder) {
//            $builder->join('projects', 'projects.id', '=', 'project_id')->where('projects.id', '=', 1);
//        });
//    }
    //    maybe http://lyften.com/journal/user-settings-using-laravel-5-eloquent-global-scopes.html ???
    protected $fillable = ['location_id', 'tag', 'date', 'relative_position', 'notes', 'project_id'];
	public function setRelativePositionAttribute($value) {
		if (is_null($value)) {
			$this->attributes['relative_position'] = null;
			return;
		}
		// MariaDB returns 1 for invalid geoms from ST_IsEmpty ref: https://mariadb.com/kb/en/mariadb/st_isempty/
		$invalid = DB::select("SELECT ST_IsEmpty(GeomFromText('$value')) as val")[0]->val;
		if($invalid) { throw new \UnexpectedValueException('Invalid Geometry object'); }
	        $this->attributes['relative_position'] = DB::raw("GeomFromText('$value')");
	}
    public function getFullnameAttribute() {
        return $this->location->name . "-" . $this->tag;
    }
    public function location()
    {
        return $this->belongsTo(Location::class);
    }
    public function project()
    {
        return $this->belongsTo(Project::class);
    }
    public function identification() 
    {
        return $this->morphOne(Identification::class, 'object');
    }
    // NOT a relationship, this returns a collection of persons
    public function get_collectors() {
        $collectors = $this->morphMany(Collector::class, 'object')->get();
        if (! $collectors->count()) return null;
        return collect($collectors)->map(function ($item) {
            return $item->person;
        });
    }
	public function newQuery($excludeDeleted = true)
	{
		return parent::newQuery($excludeDeleted)->addSelect(
			'*', 
			DB::raw('AsText(relative_position) as relativePosition')
		);
	}
}
