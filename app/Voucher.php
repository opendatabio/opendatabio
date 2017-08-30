<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Person;
use App\Project;
use App\Herbarium;
use App\IncompleteDate;

class Voucher extends Model
{
    use IncompleteDate;

    protected $fillable = ['parent_id', 'parent_type', 'person_id', 'number', 'date', 'notes', 'project_id']; 

    public function person() {
        return $this->belongsTo(Person::class);
    }
    public function project() {
        return $this->belongsTo(Project::class);
    }
    public function parent() {
        return $this->morphTo('parent');
    }
    public function herbaria() {
        return $this->belongsToMany(Herbarium::class)->withPivot('number');
    }
    public function identification() 
    {
        return $this->morphOne(Identification::class, 'object');
    }
    public function collectors() {
        return $this->morphMany(Collector::class, 'object');
    }
}
