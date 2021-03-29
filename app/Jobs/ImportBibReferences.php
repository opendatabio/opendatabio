<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Jobs;

use RenanBr\BibTexParser\Listener;
use RenanBr\BibTexParser\Parser;
use RenanBr\BibTexParser\Processor;
//\NamesProcessor as AuthorProcessor;
use App\Models\BibReference;
use App\Models\ExternalAPIs;

class ImportBibReferences extends AppJob
{
    /**
     * Execute the job.
     */
    public function inner_handle()
    {



        $this->standardize = $this->userjob->data['standardize'];
        $newentries = $this->extractEntrys();

        if (!$this->setProgressMax($newentries)) {
            return;
        }
        foreach ($newentries as $entry) {
            // has this job been cancelled?
            // calls "fresh" to make sure we're not receiving a cached object
            if ($this->isCancelled()) {
                break;
            }
            $this->userjob->tickProgress();
            if (!$this->hasRequiredKeys(['citation-key'], $entry)) {
                continue;
            } elseif ($this->standardize and array_key_exists('title', $entry)
                and array_key_exists('author', $entry)
                and array_key_exists('year', $entry)) {
                $fword = trim(strtolower(strtok($entry['title'], ' ')));
                $fword = preg_replace('/[^a-zA-Z]/', '', $fword);
                while (in_array($fword, ['a', 'an', 'the', 'on', 'of', 'in', 'as', 'at', 'for', 'from', 'where', 'i', 'are', 'is', 'it', 'that', 'this']) or 1 == strlen($fword)) {
                    $fword = strtok(' ');
                }
                // removes all characters that are not strict alpha:
                $author = $entry['author'][0]['von'].
                    $entry['author'][0]['last'];
                $slug = strtolower(
                    $author.
                    $entry['year'].
                    $fword
                );
                $slug = preg_replace('/[^a-zA-Z0-9]/', '', $slug);
                // replaces the citation key with the new slug
                $text = preg_replace('/{(.*?),/', '{'.$slug.',', $entry['_original'], 1);
            } else {
                $slug = $entry['citation-key'];
                $text = $entry['_original'];
            }
            // is there another bibtex with the same
            if (BibReference::whereRaw('odb_bibkey(bibtex) = ?', [$slug])->count() > 0) {
                $this->appendLog('WARNING: key '.$slug.' already imported to database');
            } else {
                $ref = BibReference::create(['bibtex' => $text]);
                // guesses the DOI from the bibtex and saves it on the relevant database column
                $ref->setDoi(null);
                $ref->save();
                $this->affectedId($ref->id);
            }
        }
    }

    public function extractEntrys()
    {
        //if doi has been informed search forthe bibtex record to parse it
        if (isset($this->userjob->data['doi'])) {
          $doi = $this->userjob->data['doi'];
          $contents = ExternalAPIs::getBibtexFromDoi($doi);
        }  else {
          //else assumes the content must be a bibtex file;
          $contents = $this->userjob->data['contents'];
        }
        $listener = new Listener();
        //$listener->setTagNameCase(CASE_LOWER);
        $listener->addProcessor(new Processor\TagNameCaseProcessor(CASE_LOWER));
        //$listener->addTagValueProcessor(new AuthorProcessor());
        $listener->addProcessor(new Processor\NamesProcessor());
        $parser = new Parser();
        $parser->addListener($listener);
        $parser->parseString($contents);
        $newentries = $listener->export();

        return $newentries;
    }
}
