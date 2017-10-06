<?php

namespace App\Jobs;

use Illuminate\Database\Eloquent\Model;
use RenanBr\BibTexParser\Listener;
use RenanBr\BibTexParser\Parser;
use RenanBr\BibTexParser\Processor\AuthorProcessor;
use RenanBr\BibTexParser\ParseException;
use App\BibReference;
use App\Jobs\AppJob;

use Log;

class ImportBibReferences extends AppJob
{
    /**
     * Execute the job.
     *
     * @return void
     */
    public function inner_handle()
    {
        $this->contents = $this->userjob->data['contents'];
        $this->standardize = $this->userjob->data['standardize'];
	    $listener = new Listener;
	    $listener->setTagNameCase(CASE_LOWER);
	    $listener->addTagValueProcessor(new AuthorProcessor());
	    $parser = new Parser;
	    $parser->addListener($listener);
	    $parser->parseString($this->contents);
	    $newentries = $listener->export();
	    $this->userjob->setProgressMax(count($newentries)); 
		    foreach($newentries as $entry) {
            // has this job been cancelled?
            // calls "fresh" to make sure we're not receiving a cached object
            if ($this->userjob->fresh()->status == "Cancelled") {
                $this->appendLog("WARNING: received CANCEL signal");
                break;
            }
            $this->userjob->tickProgress();
            if (! array_key_exists('citation-key', $entry)) {
                $this->setError();
                $this->appendLog ("ERROR: entry needs a valid citation key: " . $entry['_original']);
                continue;
            } elseif ($this->standardize and array_key_exists('title', $entry)
                and array_key_exists('author', $entry)
                and array_key_exists('year', $entry)) {
                $fword = trim(strtolower(strtok($entry['title'], ' ')));
                $fword = preg_replace('/[^a-zA-Z]/', '', $fword);
                while (in_array($fword, ['a', 'an', 'the', 'on', 'of', 'in', 'as', 'at', 'for', 'from', 'where', 'i', 'are', 'is', 'it', 'that', 'this']) or strlen($fword) == 1)
                    $fword = strtok(' ');
                // removes all characters that are not strict alpha:
                $author = $entry['author'][0]['von'] .
                    $entry['author'][0]['last'];
                $slug = strtolower(
                    $author . 
                    $entry['year'] .
                    $fword
                );
                $slug = preg_replace('/[^a-zA-Z0-9]/', '', $slug);
                // replaces the citation key with the new slug
                $text = preg_replace('/{(.*?),/','{'.$slug.',', $entry['_original'], 1);
            } else {
                $slug = $entry['citation-key'];
                $text = $entry['_original'];
            }
            // is there another bibtex with the same 
            if(BibReference::whereRaw('odb_bibkey(bibtex) = ?', [$slug])->count() > 0) {
                $this->appendLog ("WARNING: key " . $slug . " already imported to database");
            } else {
                BibReference::create(['bibtex' => $text]);
            }
        }
    }
}
