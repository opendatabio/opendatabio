<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace Tests;

use App\Translatable;
use Illuminate\Database\Eloquent\Model;

class TranslatableClass extends Model
{
    use Translatable;

    protected $table = 'translatable_test';
}
