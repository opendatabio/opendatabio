<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use FuzzyWuzzy\Fuzz;
use App\Collector;
use App\Plant;
use App\Voucher;
use Illuminate\Database\Eloquent\Builder;

class Person extends Model
{
	// Gramatically incorrect, but helps development
	protected $table = 'persons';
	protected $fillable = ['full_name', 'abbreviation', 'email', 'institution', 'herbarium_id'];

    protected static function boot() {
        parent::boot();
        static::addGlobalScope('order', function (Builder $builder) {
            $builder->orderBy('abbreviation', 'asc');
        });
    }

	// Looks for possible duplication of persons. Returns a collection of possible dupes
	public static function duplicates($fullname, $abbreviation) {
		function normalize($text) {
			$text = trim(strtolower($text));
			$text = preg_replace('/[^a-z ]/','',$text);
			$tarr = explode(' ', $text);
			foreach ($tarr as $key => $token)
				if (strlen($token) < 2)
					unset($tarr[$key]); 
			return join(' ', $tarr);

		}
		$fuzz = new Fuzz;
		$fullname = normalize($fullname);
		$abbreviation = normalize($abbreviation);
		$persons = Person::all()->filter(function ($element) use ($fuzz, $fullname, $abbreviation) {
			$fn = normalize($element->full_name); 
			$abb = normalize($element->abbreviation);
			$score = 0;
			return $fuzz->weightedRatio($abb, $abbreviation) > 70 or
			       $fuzz->weightedRatio($fn, $fullname) > 70;
		});
		return $persons;
	}
    public function herbarium()
    {
        return $this->belongsTo('App\Herbarium');
    }
    // for specialist taxons
    public function taxons()
    {
            return $this->belongsToMany('App\Taxon');
    }
    public function collected() {
        return $this->hasMany(Collector::class);
    }
    public function vouchers() {
        return $this->hasMany(Voucher::class);
    }
}
