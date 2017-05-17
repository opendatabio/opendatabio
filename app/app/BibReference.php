<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use RenanBr\BibTexParser\Listener as Listener;
use RenanBr\BibTexParser\Parser as Parser;


use Illuminate\Support\Facades\Log;

class BibReference extends Model
{
	// "cached" entries, so we don't need to parse the bibtex for every call
	protected $entries = null;
	protected $appends = ['author', 'title', 'year', 'bibkey'];

	private function parseBibtex() {
		$listener = new Listener;
		$parser = new Parser;
		$parser->addListener($listener);
		$parser->parseString($this->bibtex);
		$this->entries = $listener->export();
			Log::error ("BLAH");
	}

	public function getAuthorAttribute() {
		if (is_null($this->entries)) 
			$this->parseBibtex();
		if(count($this->entries) > 0) {
			return $this->entries[0]['author'];
		} else {
			return '';
		}
	}
	public function getTitleAttribute() {
		if (is_null($this->entries)) 
			$this->parseBibtex();
		if(count($this->entries) > 0) {
			return $this->entries[0]['title'];
		} else {
			return '';
		}
	}
	public function getYearAttribute() {
		if (is_null($this->entries)) 
			$this->parseBibtex();
		if(count($this->entries) > 0) {
			return $this->entries[0]['year'];
		} else {
			return '';
		}
	}
	public function getBibkeyAttribute() {
		if (is_null($this->entries)) 
			$this->parseBibtex();
		if(count($this->entries) > 0) {
			return $this->entries[0]['citation-key'];
		} else {
			return '';
		}
	}

}
