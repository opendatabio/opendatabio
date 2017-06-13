<?php

namespace App\Jobs;

use Illuminate\Database\Eloquent\Model;
use RenanBr\BibTexParser\Listener as Listener;
use RenanBr\BibTexParser\Parser as Parser;
use RenanBr\BibTexParser\Processor\AuthorProcessor;
use RenanBr\BibTexParser\ParseException as ParseException;
use App\BibReference;
use App\Jobs\AppJob;
use App\UserJobs;
use Log;

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
	    $listener->setTagNameCase(CASE_LOWER);
	    $listener->addTagValueProcessor(new AuthorProcessor());
	    $parser = new Parser;
	    $parser->addListener($listener);
	    $parser->parseString($this->contents);
	    $newentries = $listener->export();
	    $esize = count($newentries); 
	    $count = 0;
	    $errors = 0;
		    foreach($newentries as $entry) {
			    if (! array_key_exists('citation-key', $entry)) {
				    $this->setError();
				    $this->appendLog ("ERROR: entry needs a valid citation key: " . $entry['_original']);
				    continue;
			    } elseif ($this->standardize and array_key_exists('title', $entry)
			   	 			   and array_key_exists('author', $entry)
							   and array_key_exists('year', $entry)
			    ) {
				    $fword = trim(strtolower(strtok($entry['title'], ' ')));
				    while (in_array($fword, ['a', 'an', 'the', 'on', 'of', 'in', 'as', 'at', 'for', 'from', 'where', 'i', 'are', 'is', 'it', 'that', 'this']) or strlen($fword) == 1)
					    $fword = strtok(' ');
				    // removes all characters that are not strict alpha:
				    $fword = preg_replace('/[^a-zA-Z]/', '', $fword);
				    $author = $entry['author'][0]['von'] .
					      $entry['author'][0]['last'];
				    $author = preg_replace('/[^a-zA-Z]/', '', $author);
				    $slug = strtolower(
					    $author . 
					    $entry['year'] .
					    $fword
				    );
				    // replaces the citation key with the new slug
				    $text = preg_replace('/{(.*?),/','{'.$slug.',', $entry['_original'], 1);
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
