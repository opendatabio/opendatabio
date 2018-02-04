<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use App\Location;

// See issue #190 on GitHub 

class CreateWorldLocation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $WORLD = Location::create(['name' => 'World', 'adm_level' => -1]);
        Location::where('adm_level', 0)->each(function ($location) use ($WORLD) {$location->makeChildOf($WORLD);});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $WORLD = Location::world();
        if ($WORLD) {
            $WORLD->children()->each(function ($location) {$location->makeRoot();});
            $WORLD->delete();
        }
    }
}
