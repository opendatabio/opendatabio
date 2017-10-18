<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App;

use Illuminate\Database\Eloquent\Model;
use RenanBr\BibTexParser\Listener;
use RenanBr\BibTexParser\Parser;
use RenanBr\BibTexParser\ParseException;
use Pandoc\Pandoc;
use Pandoc\PandocException;
use Debugbar;
use DB;

use Illuminate\Support\Facades\Log;

class BibReference extends Model
{
    // "cached" entries, so we don't need to parse the bibtex for every call
    protected $entries = null;
    //	protected $appends = ['author', 'title', 'year', 'bibkey'];
    protected $fillable = ['bibtex'];

    public function datasets()
    {
        return $this->hasMany(Dataset::class, 'bibreference_id');
    }

    public function measurements()
    {
        return $this->hasMany(Measurement::class, 'bibreference_id');
    }

    public function taxons()
    {
        return $this->hasMany(Taxon::class, 'bibreference_id');
    }

    public function validBibtex($string)
    {
        $listener = new Listener();
        $parser = new Parser();
        $parser->addListener($listener);
        try {
            $parser->parseString($string);
        } catch (ParseException $e) {
            return false;
        }

        return true;
    }

    private function parseBibtex()
    {
        $listener = new Listener();
        $listener->setTagNameCase(CASE_LOWER);
        try {
            $pandoc = new Pandoc();
            $listener->addTagValueProcessor(function (&$value, $tag) use ($pandoc) {
                if ('author' != $tag and 'title' != $tag) {
                    return;
                }
                $value = $pandoc->runWith($value, [
                'from' => 'latex',
                'to' => 'plain', // or "html"
            ]);
            });
        } catch (PandocException $e) {
        } // pandoc is not installed
        $parser = new Parser();
        $parser->addListener($listener);
        try {
            Debugbar::measure('Parsing', function () use ($parser) {
                $parser->parseString($this->bibtex);
            });
        } catch (ParseException $e) {
            Log::error('Error handling bibtex:'.$e->getMessage());
            $this->entries[0] = ['author' => null, 'title' => null, 'year' => null, 'citation-key' => 'Invalid'];

            return;
        }
        Debugbar::measure('Exporting', function () use ($listener) {
            $this->entries = $listener->export();
        });
    }

    public function getAuthorAttribute()
    {
        if (is_null($this->entries)) {
            $this->parseBibtex();
        }
        if (count($this->entries) > 0 and array_key_exists('author', $this->entries[0])) {
            return $this->entries[0]['author'];
        } else {
            return '';
        }
    }

    public function getTitleAttribute()
    {
        if (is_null($this->entries)) {
            $this->parseBibtex();
        }
        if (count($this->entries) > 0 and array_key_exists('title', $this->entries[0])) {
            return $this->entries[0]['title'];
        } else {
            return '';
        }
    }

    public function getYearAttribute()
    {
        if (is_null($this->entries)) {
            $this->parseBibtex();
        }
        if (count($this->entries) > 0 and array_key_exists('year', $this->entries[0])) {
            return $this->entries[0]['year'];
        } else {
            return '';
        }
    }

    public function getParsedBibkeyAttribute()
    {
        if (is_null($this->entries)) {
            $this->parseBibtex();
        }
        if (count($this->entries) > 0 and array_key_exists('citation-key', $this->entries[0])) {
            return $this->entries[0]['citation-key'];
        } else {
            return '';
        }
    }

    public function newQuery($excludeDeleted = true)
    {
        // includes the full name of a taxon in all queries
        return parent::newQuery($excludeDeleted)->addSelect(
            '*',
            DB::raw('odb_bibkey(bibtex) as bibkey')
        );
    }
}
