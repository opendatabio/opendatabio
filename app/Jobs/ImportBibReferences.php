<?php

namespace App\Jobs;

use Illuminate\Database\Eloquent\Model;
use RenanBr\BibTexParser\Listener as Listener;
use RenanBr\BibTexParser\Parser as Parser;
use RenanBr\BibTexParser\ParseException as ParseException;
use App\Structures_BibTex;
use App\BibReference;
use App\Jobs\AppJob;
use App\UserJobs;

class ImportBibReferences extends AppJob
{
    protected $contents, $standardize;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($contents, $standardize, UserJobs $userjob)
    {
	    parent::__construct($userjob);
	    $this->contents = $contents;
	    $this->standardize = $standardize;
    }
    /**
     * Execute the job.
     *
     * @return void
     */
    public function inner_handle()
    {
	    $listener = new Listener;
	    $parser = new Parser;
	    $parser->addListener($listener);
	    $parser->parseString($this->contents);
	    $newentries = $listener->export();
	    $esize = count($newentries); 
	    $count = 0;
	    $errors = 0;
		    foreach($newentries as $entry) {
//			    $count++;
//			    $this->userjob->setProcessing( 100 * $count / $esize);
//			    sleep(20);
			    if ($this->standardize) {
				    $bibtex = new Structures_BibTex;
				    $bibtex->parse_string($entry['_original']);
				    $fword = trim(strtolower(strtok($bibtex->data[0]['title'], ' ')));
				    while (in_array($fword, ['a', 'an', 'the', 'on', 'of', 'in', 'as', 'at', 'for', 'from', 'where', 'i', 'are', 'is', 'it', 'that', 'this']) or strlen($fword) == 1)
					    $fword = strtok(' ');
				    // removes all characters that are not strict alpha:
				    $fword = preg_replace('/[^a-zA-Z]/', '', $fword);
				    $author = $bibtex->data[0]['author'][0]['von'] .
					      $bibtex->data[0]['author'][0]['last'];
				    $author = preg_replace('/[^a-zA-Z]/', '', $author);
				    $slug = strtolower(
					    $author . 
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
			    if(BibReference::whereRaw('odb_bibkey(bibtex) = ?', [$slug])->count() > 0) {
				    $this->setError();
				    $this->appendLog ("ERROR: key " . $slug . " already imported to database");
			    } else {
				    BibReference::create(['bibtex' => $text]);
			    }
		    }
    }
}
