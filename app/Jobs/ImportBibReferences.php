<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use Illuminate\Database\Eloquent\Model;
use RenanBr\BibTexParser\Listener as Listener;
use RenanBr\BibTexParser\Parser as Parser;
use RenanBr\BibTexParser\ParseException as ParseException;
use App\Structures_BibTex;
use App\BibReference;
use App\UserJobs;
use DB;
use Log;

class ImportBibReferences implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $contents, $standardize, $userjob;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($contents, $standardize, UserJobs $userjob)
    {
	    $this->contents = $contents;
	    $this->standardize = $standardize;
	    $this->userjob = $userjob;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
	    $this->userjob->setProcessing(0);
	    try {
	    DB::transaction(function () {
	    $listener = new Listener;
	    $parser = new Parser;
	    $parser->addListener($listener);
	    $parser->parseString($this->contents);
	    $newentries = $listener->export();
	    $esize = count($newentries); 
	    $count = 0;
	    $errors = 0;
		    foreach($newentries as $entry) {
			    $count++;
			    $this->userjob->setProcessing( 100 * $count / $esize);
			    if ($this->standardize) {
				    $bibtex = new Structures_BibTex;
				    $bibtex->parse_string($entry['_original']);
				    $fword = trim(strtolower(strtok($bibtex->data[0]['title'], ' ')));
				    while (in_array($fword, ['a', 'an', 'the', 'on', 'of', 'in', 'as', 'at', 'for', 'from', 'where', 'i', 'are', 'is', 'it', 'that', 'this']))
					    $fword = strtok(' ');
				    $slug = strtolower(
					    $bibtex->data[0]['author'][0]['von'] .
					    $bibtex->data[0]['author'][0]['last'] .
					    $bibtex->data[0]['year'] .
					    $fword
				    );
				    $bibtex->data[0]['cite'] = $slug;
				    $text = $bibtex->bibTex();
			    } else {
				    $slug = $entry['citation-key'];
				    $text = $entry['_original'];
			    }
			    // is there another bibtex with the same 
			    $this->userjob->sendLog('slug: \"' . $slug . '\"' );
			    $x = BibReference::whereRaw('odb_bibkey(bibtex) = ?', [$slug])->toSql();
			    $this->userjob->sendLog('SQL:' . $x);

			    if(BibReference::whereRaw('odb_bibkey(bibtex) = ?', [$slug])->count() > 0) {
				    $errors ++;
				    $this->userjob->sendLog('ERROR: key ' . $slug . ' already imported to database');
			    } else {
				    BibReference::create(['bibtex' => $text]);
			    }
		    }
		    if ($errors > 0) {
			    $this->userjob->setFailed();
			    throw new \Exception('Errors processing job');
		    }
		    });
		   	    
		    $this->userjob->setSuccess();
	    } catch (\Exception $e) {
		    $this->userjob->sendLog('Caught exception! ' . $e->getMessage());
		    Log::info ("WHY DOES THIS NEVER REACH?");
		    $this->userjob->setFailed();
	    }
    }
}
