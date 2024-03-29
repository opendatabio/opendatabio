<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use RenanBr\BibTexParser\Listener;
use RenanBr\BibTexParser\Parser;
use RenanBr\BibTexParser\ParseException;
use RenanBr\BibTexParser\Processor;
//use RenanBr\BibTexParser\Processor\LatexToUnicodeProcessor as LatexToUnicode;
//use RenanBr\BibTexParser\Exception\ProcessorException;
use Illuminate\Support\Str;
use App\Models\BibLatexTitleToUnicode;
//use Pandoc\Pandoc;
//use Pandoc\PandocException;

use DB;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Traits\LogsActivity;

use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class BibReference extends Model implements HasMedia
{
    use InteractsWithMedia, LogsActivity;

    // "cached" entries, so we don't need to parse the bibtex for every call
    protected $entries = null;
    protected $fillable = ['bibtex', 'doi'];

    //activity log trait
    protected static $logName = 'bibreference';
    protected static $recordEvents = ['updated','deleted'];
    protected static $ignoreChangedAttributes = ['updated_at'];
    protected static $logFillable = true;
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;


    public function rawLink()
    {
        return "<a href='".url('references/'.$this->id)."'>".htmlspecialchars($this->bibkey).'</a>';
    }

    public function datasets()
    {
      return $this->belongsToMany(Dataset::class,'dataset_bibreference')->withPivot(['mandatory']);
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
        /* define modifications to be applied to the original record */
        $listener = new Listener();
        $listener->addProcessor(new Processor\TagNameCaseProcessor(CASE_LOWER));
        //$listener->addProcessor(new Processor\NamesProcessor());
        $listener->addProcessor(new Processor\KeywordsProcessor());
        $listener->addProcessor(new Processor\DateProcessor());
        $listener->addProcessor(new Processor\TrimProcessor());
        $listener->addProcessor(new Processor\UrlFromDoiProcessor());
        $listener->addProcessor(static function (array $entry) {
            $title = Str::title($entry['title']);
            $entry['title'] = $title;
            return $entry;
        });
        //this must be the last processor
        $listener->addProcessor(new BibLatexTitleToUnicode());

        /* apply the modifications */
        $parser = new Parser();
        $parser->addListener($listener);

        try {
            $parser->parseString($this->bibtex);
        } catch (ParseException $e) {
            Log::error('Error handling bibtex:'.$e->getMessage());
            $this->entries[0] = ['author' => null, 'title' => null, 'year' => null, 'citation-key' => 'Invalid'];
            return;
        }
        $this->entries = $listener->export();
    }

    public function getDoiAttribute()
    {
        if (isset($this->attributes['doi'])) {
            return $this->attributes['doi'];
        }

        // falls back to the bibtex "doi" field in case the database column is absent
        if (is_null($this->entries)) {
            $this->parseBibtex();
        }
        if (count($this->entries) > 0 and array_key_exists('doi', $this->entries[0])) {
            return $this->entries[0]['doi'];
        } else {
            return '';
        }
    }

    public function getUrlAttribute()
    {
        // falls back to the bibtex "doi" field in case the database column is absent
        if (is_null($this->entries)) {
            $this->parseBibtex();
        }
        if (count($this->entries) > 0 and array_key_exists('url', $this->entries[0])) {
            return $this->entries[0]['url'];
        } else {
            return '';
        }
    }

    // NOTE, this may be called as "$newDoi = null" from a newly created resource to guess DOI from bibtex
    public function setDoi($newDoi)
    {
        // if receiving a blank and we have attr set, the user is probably trying to remove the information
        $olddoi = array_key_exists('doi', $this->attributes)  ? $this->attributes['doi'] : null;
        if ($olddoi and  null == $newDoi) {
            $this->attributes['doi'] = null;
            return;
        }
        // if we are receiving something for $newDoi, use it
        if (!is_null($newDoi)) {
            $this->attributes['doi'] = $newDoi;
            return;
        }
        // else, guess it from the bibTex and fill it
        if (is_null($this->entries)) {
            $this->parseBibtex();
        }

        $entry = $this->entries[0];
        $hasdoi = array_key_exists('doi',$entry) ? $entry['doi'] : (array_key_exists('DOI',$entry) ? $entry['DOI'] : null );
        if (!is_null($hasdoi)) {
            //$this->attributes['doi'] = $hasdoi;
            $this->doi = $hasdoi;
            //$this->attributes['doi'] = $newDoi;
        }
    }

    public static function isValidDoi($doi)
    {
        // Regular expression adapted from https://www.crossref.org/blog/dois-and-matching-regular-expressions/
        if (1 == preg_match('/^10.\d{4,9}\/[-._;()\/:A-Z0-9]+$/i', $doi)) {
            return true;
        }

        return false;
    }

    public function getAuthorAttribute()
    {
        if (is_null($this->entries)) {
            $this->parseBibtex();
        }
        if (count($this->entries) > 0 and array_key_exists('author', $this->entries[0])) {
            $author = $this->entries[0]['author'];
            $authors =  explode(" and ",mb_strtolower($author));
            $authors = collect($authors)->map(function($word) { return  Str::title($word);})->toArray();
            $authors = implode(" and ",$authors);
            return $authors;
        } else {
            return '';
        }
    }

    public function getFirstAuthorAttribute()
    {
        if (is_null($this->entries)) {
            $this->parseBibtex();
        }
        if (count($this->entries) > 0 and array_key_exists('author', $this->entries[0])) {
            $author = $this->entries[0]['author'];
            $authors =  explode(" and ",$author);
            if (count($authors)>2) {
              return Str::title($authors[0])." et al.";
            } else {
              return Str::title($author);
            }
        } else {
            return '';
        }
    }

    public function identifiableName()
    {
        $name =  $this->getFirstAuthorAttribute()." ".$this->getYearAttribute();
        return "<a href='".url('references/'.$this->id)."'>".htmlspecialchars($name)."</a>";
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

    public function getBibreferenceSimpleAttribute($value='')
    {
      $result = [
        $this->first_author,$this->year,$this->title,(($this->doi!='') ? $this->doi : $this->url)
      ];
      $result = array_filter($result, function($r) { $r!='';});
      if (count($result)==0) {
        return null;
      }
      return implode(". ",$result).".";
    }




}
