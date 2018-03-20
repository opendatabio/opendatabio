<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use DB;
use Auth;
use Lang;

class Plant extends Model
{
    use IncompleteDate;

    // NOTICE regarding attributes!! relative_position is the name of the database column, so this should be called when writing to database
    // (ie, setRelativePosition), but this is read as position, so this should be called on read context

    protected $fillable = ['location_id', 'tag', 'date', 'relative_position', 'notes', 'project_id'];

    // for use when receiving this as part of a morph relation
    // TODO: maybe can be changed to get_class($p)?
    public function getTypenameAttribute()
    {
        return 'plants';
    }

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('projectScope', function (Builder $builder) {
            // first, the easy cases. No logged in user?
            if (is_null(Auth::user())) {
                return $builder->whereRaw('plants.id IN 
(SELECT p1.id FROM plants AS p1 
JOIN projects ON (projects.id = p1.project_id)
WHERE projects.privacy = 2)');
            }
            // superadmins see everything
            if (User::ADMIN == Auth::user()->access_level) {
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
WHERE projects.privacy = 0 AND project_user.user_id = '.Auth::user()->id.'
)');
        });
    }

    public function setRelativePosition($x, $y = null) // alternative, ($angle, $distance)
    {
        if (is_null($x) and is_null($y)) {
            $this->attributes['relative_position'] = null;

            return;
        }

        $location_type = $this->location->adm_level;
        if (Location::LEVEL_POINT == $location_type) {
            $angle = $x * M_PI / 180;
            $distance = $y;
            // converts the angle and distance to x/y
            $x = $distance * cos($angle);
            $y = $distance * sin($angle);
        }

        // MariaDB returns 1 for invalid geoms from ST_IsEmpty ref: https://mariadb.com/kb/en/mariadb/st_isempty/
        $invalid = DB::select("SELECT ST_IsEmpty(GeomFromText('POINT($y $x)')) as val")[0]->val;
        if ($invalid) {
            throw new \UnexpectedValueException('Invalid Geometry object');
        }
        $this->attributes['relative_position'] = DB::raw("GeomFromText('POINT($y $x)')");
    }

    public function getFullnameAttribute()
    {
        if ($this->location) {
            return $this->location->name.'-'.$this->tag;
        }

        return 'Unknown location-'.$this->tag;
    }

    public function getTaxonNameAttribute()
    {
        if ($this->identification and $this->identification->taxon) {
            return $this->identification->taxon->fullname;
        }

        return Lang::get('messages.unidentified');
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function getLocationNameAttribute()
    {
        if ($this->location) {
            return $this->location->name . ' (' . $this->location->latitudeSimple . ', ' . $this->location->longitudeSimple . ')';
        }
        return 'Unknown location';
    }

    public function getProjectNameAttribute()
    {
        if ($this->project) {
            return $this->project->name;
        }
        return 'Unknown project';
    }

    // with access to the location geom field
    public function getLocationWithGeomAttribute()
    {
        // This is ugly as hell, but simpler alternatives are "intercepted" by Baum, which does not respect the added scope...
        $loc = $this->location;
        if (!$loc) {
            return;
        }

        return Location::withGeom()->addSelect('id', 'name')->find($loc->id);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function vouchers()
    {
        return $this->morphMany(Voucher::class, 'parent');
    }

    public function measurements()
    {
        return $this->morphMany(Measurement::class, 'measured');
    }

    public function identification()
    {
        return $this->morphOne(Identification::class, 'object');
    }

    public function collectors()
    {
        return $this->morphMany(Collector::class, 'object');
    }

    public function newQuery($excludeDeleted = true)
    {
        // This uses the explicit list to avoid conflict due to global scope
        // maybe check http://lyften.com/journal/user-settings-using-laravel-5-eloquent-global-scopes.html ???
        return parent::newQuery($excludeDeleted)->select(
            'plants.id',
            'plants.tag',
            'plants.project_id',
            'plants.date',
            'plants.notes',
            'plants.location_id',
            DB::raw('AsText(relative_position) as relativePosition')
        );
    }

    // getters for the Relative Position
    public function getXAttribute()
    {
        $point = substr($this->attributes['relativePosition'], 6, -1);
        $pos = strpos($point, ' ');

        return substr($point, $pos + 1);
    }

    public function getYAttribute()
    {
        $point = substr($this->attributes['relativePosition'], 6, -1);
        $pos = strpos($point, ' ');

        return substr($point, 0, $pos);
    }

    public function getAngleAttribute()
    {
        $x = $this->getXAttribute();
        if ('' === $x) {
            return '';
        }
        $y = $this->getYAttribute();

        return 180 / M_PI * atan2((float) $y, (float) $x);
    }

    public function getDistanceAttribute()
    {
        $x = $this->getXAttribute();
        if ('' === $x) {
            return '';
        }
        $y = $this->getYAttribute();

        return sqrt((float) $x * (float) $x + (float) $y * (float) $y);
    }

    public function pictures()
    {
        return $this->morphMany(Picture::class, 'object');
    }
}
