<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Taxon;

class CreateRootTaxon extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      $hastaxon = Taxon::count();
      $taxon_root  = Taxon::create(['name' => 'Life', 'level' => -1, 'valid' => 1]);
      if ($hastaxon and $taxon_root) {
          $taxons = Taxon::whereNull('parent_id')->get();
          $taxons->each(function ($taxon) use ($taxon_root) {$taxon->makeChildOf($taxon_root); });
      }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $root = Taxon::lifeRoot();
        if (!empty($root) and !empty($root->children())) {
            $root->children()->each(function ($taxon) {$taxon->makeRoot(); });
            Taxon::where('level', -1)->delete();
        }
    }
}
