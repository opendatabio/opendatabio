<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Person;
use App\Project;
use App\Herbarium;
use App\IncompleteDate;
use Lang;
use Auth;

class Voucher extends Model
{
    use IncompleteDate;

    protected $fillable = ['parent_id', 'parent_type', 'person_id', 'number', 'date', 'notes', 'project_id']; 

    // for use when receiving this as part of a morph relation
    public function getTypenameAttribute() { return "vouchers"; }

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('projectScope', function (Builder $builder) {
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
            return $builder->whereRaw('vouchers.id IN
(SELECT p1.id FROM vouchers AS p1
JOIN projects ON (projects.id = p1.project_id)
WHERE projects.privacy > 0
UNION 
SELECT p1.id FROM vouchers AS p1
JOIN projects ON (projects.id = p1.project_id)
JOIN project_user ON (projects.id = project_user.project_id)
WHERE projects.privacy = 0 AND project_user.user_id = ' . Auth::user()->id . '
)');
        });
    }
	public function newQuery($excludeDeleted = true)
	{
        // This uses the explicit list to avoid conflict due to global scope
		return parent::newQuery($excludeDeleted)->addSelect(
            'vouchers.id', 
            'vouchers.number',
            'vouchers.project_id',
            'vouchers.date',    
            'vouchers.notes',    
            'vouchers.person_id',
            'vouchers.parent_id',
            'vouchers.parent_type'
		);
	}
    public function getFullnameAttribute() {
        return $this->person->abbreviation . "-" . $this->number;
    }
    public function person() {
        return $this->belongsTo(Person::class);
    }
    public function project() {
        return $this->belongsTo(Project::class);
    }
    public function parent() {
        return $this->morphTo();
    }
    public function herbaria() {
        return $this->belongsToMany(Herbarium::class)->withPivot('herbarium_number');
    }
    public function setHerbariaNumbers($herbaria) {
        // drop "null" values
        $herbaria = array_filter($herbaria);
        if (empty($herbaria)) {
            $this->herbaria()->detach();
            return;
        }
        // transforms the array to be Laravel-friendly
        foreach ($herbaria as $key => &$value) $value = ['herbarium_number' => $value];
        // syncs the data
        $this->herbaria()->sync($herbaria);
    }
    public function identification() 
    {
        return $this->morphOne(Identification::class, 'object');
    }
    public function collectors() {
        return $this->morphMany(Collector::class, 'object');
    }
}
