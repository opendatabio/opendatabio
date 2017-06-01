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
	    $this->userjob->setProcessing();
	    $listener = new Listener;
	    $parser = new Parser;
	    $parser->addListener($listener);
	    $parser->parseString($this->contents);
	    $newentries = $listener->export();
	    foreach($newentries as $entry) {
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
			    BibReference::create(['bibtex' => $bibtex->bibTex()]);
		    } else {
			    BibReference::create(['bibtex' => $entry['_original']]);
		    }
	    }
    }
}
