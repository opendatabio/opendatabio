<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Translatable;
use App\Dataset;

class Tag extends Model
{
    use Translatable;
    public function datasets() {
        return $this->belongsToMany(Dataset::class);
    }
}
