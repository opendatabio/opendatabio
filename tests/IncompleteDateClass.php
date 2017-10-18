<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace Tests;

use App\IncompleteDate;
use Illuminate\Database\Eloquent\Model;

class IncompleteDateClass extends Model
{
    public $date;

    use IncompleteDate;
}
