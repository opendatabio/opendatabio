<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Location;
use App\Project;
use App\Collector;
use DB;
use Auth;
use App\User;

class Plant extends Model
{
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('projects.id', function (Builder $builder) {
            // first, the easy cases. No logged in user?
            if (is_null(Auth::user())) {
                return $builder->join('projects', 'projects.id', '=', 'project_id')
                    ->where('projects.privacy', '=', Project::PRIVACY_PUBLIC);
            }
            // superadmins see everything
            if (Auth::user()->access_level == User::ADMIN) {
                return $builder;
            }
            // now the complex case: the regular user
            $q1 = $builder->join('projects', 'projects.id', '=', 'project_id')
                    ->where('projects.privacy', '=', Project::PRIVACY_PUBLIC);
            return $builder->join('projects', 'projects.id', '=', 'plants.project_id')
                ->join('project_user', 'projects.id', '=', 'project_user.project_id')
                ->whereRaw('( projects.privacy != ' .  Project::PRIVACY_PUBLIC . ' )
AND
(project_user.user_id = ' . Auth::user()->id . ' )
');
        });
    }
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
        // This uses the explicit list to avoid conflict due to global scope
        // maybe check http://lyften.com/journal/user-settings-using-laravel-5-eloquent-global-scopes.html ???
		return parent::newQuery($excludeDeleted)->addSelect(
            'plants.id', 
            'plants.tag',
            'plants.project_id',
            'plants.date',    
            'plants.notes',
            'plants.location_id',
			DB::raw('AsText(relative_position) as relativePosition')
		);
	}
}
