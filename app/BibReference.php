<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use RenanBr\BibTexParser\Listener as Listener;
use RenanBr\BibTexParser\Parser as Parser;
use RenanBr\BibTexParser\ParseException as ParseException;
use Pandoc\Pandoc;
use Pandoc\PandocException;

use Illuminate\Support\Facades\Log;

class BibReference extends Model
{
	// "cached" entries, so we don't need to parse the bibtex for every call
	protected $entries = null;
	protected $appends = ['author', 'title', 'year', 'bibkey'];
	protected $fillable = ['bibtex'];

	public function validBibtex($string) {
		$listener = new Listener;
		$parser = new Parser;
		$parser->addListener($listener);
		try {
			$parser->parseString($string);
		} catch (ParseException $e) {
			return false;
		}
		return true;
	}

	private function parseBibtex() {
		$listener = new Listener;
		$listener->setTagNameCase(CASE_LOWER);
		try {
		$pandoc = new Pandoc();
		$listener->setTagValueProcessor(function (&$value, $tag) use ($pandoc) {
			$value = $pandoc->runWith($value, [
				"from" => "latex",
				"to" => "plain" // or "html"
			]);
		});
		} catch (PandocException $e) {} // pandoc is not installed
		$parser = new Parser;
		$parser->addListener($listener);
		try {
			$parser->parseString($this->bibtex);
		} catch (ParseException $e) {
			Log::error("Error handling bibtex:". $e->getMessage());
			$this->entries[0] = ['author' => null, 'title' => null, 'year' => null, 'citation-key' => 'Invalid'];
			return;
		}
		$this->entries = $listener->export();
	}

	public function getAuthorAttribute() {
		if (is_null($this->entries)) 
			$this->parseBibtex();
		if(count($this->entries) > 0 and array_key_exists('author', $this->entries[0])) {
			return $this->entries[0]['author'];
		} else {
			return '';
		}
	}
	public function getTitleAttribute() {
		if (is_null($this->entries)) 
			$this->parseBibtex();
		if(count($this->entries) > 0 and array_key_exists('title', $this->entries[0])) {
			return $this->entries[0]['title'];
		} else {
			return '';
		}
	}
	public function getYearAttribute() {
		if (is_null($this->entries)) 
			$this->parseBibtex();
		if(count($this->entries) > 0 and array_key_exists('year', $this->entries[0])) {
			return $this->entries[0]['year'];
		} else {
			return '';
		}
	}
	public function getBibkeyAttribute() {
		if (is_null($this->entries)) 
			$this->parseBibtex();
		if(count($this->entries) > 0 and array_key_exists('citation-key', $this->entries[0])) {
			return $this->entries[0]['citation-key'];
		} else {
			return '';
		}
	}

}
