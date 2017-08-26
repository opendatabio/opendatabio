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
use App\IncompleteDate;

class Plant extends Model
{
    use IncompleteDate;

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
            return $builder->whereRaw('plants.id IN
(SELECT p1.id FROM plants AS p1
JOIN projects ON (projects.id = p1.project_id)
WHERE projects.privacy > 0
UNION 
SELECT p1.id FROM plants AS p1
JOIN projects ON (projects.id = p1.project_id)
JOIN project_user ON (projects.id = project_user.project_id)
WHERE projects.privacy = 0 AND project_user.user_id = ' . Auth::user()->id . '
)');
        });
    }
    protected $fillable = ['location_id', 'tag', 'date', 'relative_position', 'notes', 'project_id'];
	public function setRelativePosition($x, $y = null) {
		if (is_null($x) and is_null($y)) {
			$this->attributes['relative_position'] = null;
			return;
		}
		// MariaDB returns 1 for invalid geoms from ST_IsEmpty ref: https://mariadb.com/kb/en/mariadb/st_isempty/
		$invalid = DB::select("SELECT ST_IsEmpty(GeomFromText('POINT($y $x)')) as val")[0]->val;
		if($invalid) { throw new \UnexpectedValueException('Invalid Geometry object'); }
	        $this->attributes['relative_position'] = DB::raw("GeomFromText('POINT($y $x)')");
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
    public function collectors() {
        return $this->morphMany(Collector::class, 'object');
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
	public function getXAttribute() {
		$point = substr($this->relativePosition, 6, -1);
		$pos = strpos($point, ' ');
		return substr($point, $pos+1);
	}
	public function getYAttribute() {
		$point = substr($this->relativePosition, 6, -1);
		$pos = strpos($point, ' ');
		return substr($point,0, $pos);
	}
}
