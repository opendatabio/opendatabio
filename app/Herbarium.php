<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Person;
use App\Voucher;

class Herbarium extends Model
{
	protected $fillable = ['name', 'acronym', 'irn'];
    public function persons()
    {
        return $this->hasMany(Person::class);
    }
    public function vouchers() {
        return $this->belongsToMany(Voucher::class)->withPivot('herbarium_number');
    }
}
