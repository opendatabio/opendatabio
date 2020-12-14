<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Summary;

class SummaryUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'summary:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command updates the counts for models';

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
        Summary::updateCountChanges();

        $this->info('Successfully updated the counts table');
    }
}
