<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Jobs;

use RenanBr\BibTexParser\Listener;
use RenanBr\BibTexParser\Parser;
use RenanBr\BibTexParser\Processor;
use RenanBr\BibTexParser\ParseException;
use Illuminate\Support\Str;

//\NamesProcessor as AuthorProcessor;
use App\Models\BibReference;
use App\Models\ExternalAPIs;
use CodeInc\StripAccents\StripAccents;

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

            //jump record if these are not present
            if (!$this->hasRequiredKeys(['citation-key','_type','_original'], $entry)) {
                continue;
            }

            //standardize the key
            if ($this->standardize and  isset($entry['title'])
                and isset($entry['author'])and isset($entry['year']))
                {

                //first title word
                $fword = trim(strtolower(strtok($entry['title'], ' ')));
                //remove special characters
                $fword = preg_replace('/[^a-zA-Z]/', '', $fword);
                while (in_array($fword, ['a', 'an', 'the', 'on', 'of', 'in', 'as', 'at', 'for', 'from', 'where', 'i', 'are', 'is', 'it', 'that', 'this']) or 1 == strlen($fword)) {
                    $fword = strtok(' ');
                }

                //short author
                $author = $entry['author'][0]['von']
                    .$entry['author'][0]['last'];

                //the standardized citation-key
                $citationKey =
                    ucfirst($author).
                    $entry['year'].
                    strtolower($fword)
                ;


            } else {
              $citationKey = $entry['citation-key'];
            }

            //remove any not alpha characters from citation-key
            $citationKey = StripAccents::strip( (string) $citationKey);
            $citationKey = preg_replace('/[^a-zA-Z0-9]/', '', $citationKey);

            //$text = mb_convert_encoding($text,null,'UTF-8');

            // is there another bibtex with the same bibkey
            // TODO: this may require changing to prevent duplicated records
            $bibkeyAlreadyRegistered = BibReference::whereRaw('odb_bibkey(bibtex) = ?', [$citationKey])->count()>0;
            if (isset($entry['doi'])) {
              $doiAlreadyRegistered = BibReference::where('doi',$entry['doi'])->count()>0;
              /* doi is different but key exists, change key */
              if (!$doiAlreadyRegistered and $bibkeyAlreadyRegistered) {
                $citationKey = $citationKey."_a";
                $bibkeyAlreadyRegistered = BibReference::whereRaw('odb_bibkey(bibtex) = ?', [$citationKey])->count()>0;
              }
            }

            //original record with sanitized and/or standardized key
            $text = preg_replace('/{(.*?),/', '{'.$citationKey.',', $entry['_original'], 1);


            if ($bibkeyAlreadyRegistered or $doiAlreadyRegistered) {
                $this->appendLog('WARNING: key '.$citationKey.' or doi already registered in the database');
                //skip this record
                continue;
            }

            $ref = BibReference::create(['bibtex' => $text]);
            // guesses the DOI from the bibtex and saves it on the relevant database column
            $ref->setDoi(null);
            $ref->save();
            $this->affectedId($ref->id);
        }
    }

    public function extractEntrys()
    {
        //if doi has been informed search for the bibtex record to parse it
        if (isset($this->userjob->data['doi'])) {
          $doi = $this->userjob->data['doi'];
          $contents = ExternalAPIs::getBibtexFromDoi($doi);
        }  else {
          //else assumes the content must be a bibtex file;
          $contents = $this->userjob->data['contents'];
        }
        if (null == $contents) {
          return null;
        }

        /*
        *  add to listener just the transformations needed
            to check and standardize keys and tags
        *  original bibtex will be saved
        */
        $listener = new Listener();
        $listener->addProcessor(new Processor\TagNameCaseProcessor(CASE_LOWER));
        $listener->addProcessor(new Processor\NamesProcessor());
        $listener->addProcessor(new Processor\DateProcessor());
        $listener->addProcessor(new Processor\TrimProcessor());
        $parser = new Parser();
        $parser->addListener($listener);
        $parser->parseString($contents);
        $newentries = $listener->export();

        return $newentries;
      }

        //$listener->addProcessor(new Processor\LatexToUnicodeProcessor());
        //add ODB modified unicode processor to tackle only title and authros
        //$listener->addProcessor(new BibLatexTitleToUnicode());
        //$listener->setTagNameCase(CASE_LOWER);
        //$listener->addTagValueProcessor(new AuthorProcessor());



}
