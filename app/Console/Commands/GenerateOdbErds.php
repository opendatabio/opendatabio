<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateOdbErds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'odb:erds {model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate the Entity Relationships Diagrams (ERD) included in the ODB docs';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        /* get base config array */
        $baseConfig = config("erd-generator-base");
        $odbModels = config("erd-generator-odb");

        if ($this->argument('model')) {
          $model = $this->argument('model');
          if (null != $model and $model!='all') {
            $odbModels = isset($odbModels[$model]) ? [$odbModels[$model]] : [];
          }
        }
        foreach ($odbModels as $key => $value) {
          $modelConfig = $baseConfig;
          $filename = $value['filename'];
          $models = $value['models'];
          $use_db_schema = isset($value['use_db_schema']) ? $value['use_db_schema'] : true;
          $rankidr = isset($value['rankdir']) ? $value['rankdir'] : 'LR';
          $ranksep = isset($value['ranksep']) ? $value['ranksep'] : 1;
          $nodesep = isset($value['nodesep']) ? $value['nodesep'] : 2;
          $modelConfig['whitelist'] = $models;
          $modelConfig['use_db_schema'] = $use_db_schema;
          $modelConfig['graph']['rankdir'] = $rankidr;
          $modelConfig['graph']['ranksep'] = $ranksep;
          $modelConfig['graph']['nodesep'] = $nodesep;

          /* write to config */
          $text = "<?php\n\nreturn " . var_export($modelConfig, true) . ";";
          $path = config_path("erd-generator.php");
          $fp = fopen($path,'w');
          fwrite($fp,$text);
          fclose($fp);
          $topath = public_path("images/docs/".$filename);
          exec('php artisan generate:erd '.$topath, $result, $status);
          echo $model." concluded \n\n";
        }
        return ;
    }
}
