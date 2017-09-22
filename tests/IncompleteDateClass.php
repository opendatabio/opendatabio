<?php

namespace Tests;
use App\IncompleteDate;
use Illuminate\Database\Eloquent\Model;

class IncompleteDateClass extends Model {
    public $date;

    use IncompleteDate;

}
