<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Models;

use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Str;
use Log;
use App\Models\BibReference;

class ExternalAPIs
{
    private $proxystring = '';

    const NOT_FOUND = 1;
    const MULTIPLE_HITS = 2;
    const NONE_SYNONYM = 4;

    public function __construct()
    {
        // Only generates proxyed requests if proxy_url is present
        if ('' != config('app.proxy_url')) {
            if ('' != config('app.proxy_user')) {
                $this->proxystring = config('app.proxy_user').':'.config('app.proxy_password').'@';
            }
            $this->proxystring = $this->proxystring.config('app.proxy_url').':'.config('app.proxy_port');
        }
    }


    public function getIndexHerbariorum($acronym)
    {
        $base_uri = "http://sweetgum.nybg.org/science/api/v1/institutions/";
        //$client = new Guzzle();
        $client = new Guzzle(['base_uri' => $base_uri, 'proxy' => $this->proxystring]);
        try {
            $response = $client->request('GET', 'search?code='.$acronym);
        } catch (ClientException $e) {
            return null; //FAILED
        }
        if (200 != $response->getStatusCode()) {
            return null;
        } // FAILED
        $answer = json_decode($response->getBody());
        //Log::warning("".serialize($answer));
        if ($answer->meta->hits == 1) {
          $IRN = $answer->data[0]->irn;
          $name = $answer->data[0]->organization;
          return [$IRN, $name];
        } else {
          return null;
        }
        //
    }


    public static function getBibtexFromDoi($doi)
    {
        if (null != $doi and BibReference::isValidDoi($doi)) {
        $uri = "https://api.crossref.org/works/".$doi;
        //$client = new Guzzle(['base_uri' => $base_uri, 'proxy' => $this->proxystring]);
        $client = new Guzzle(); // 'proxy' => $this->proxystring]);
          try {
            $response = $client->request('GET', $uri);
          } catch (ClientException $e) {
              return null; //FAILED
          }
          if (200 != $response->getStatusCode()) {
              return null;
          } // FAILED
          $answer = json_decode($response->getBody());
          $answer_arr = json_decode(json_encode($answer->message),true);
          if (is_array($answer_arr['author'])) {
              $author = $answer_arr['author'];
          } else {
              $author = explode(' and ',$answer_arr['author']);
          }
          $authors = [];
          $family = [];
          foreach($author as $au) {
            if (isset($au['given'])) {
              $authors[] = $au['given']." ".$au['family'];
              $family[] = $au['family'];
            } else {
              $authors[] = $au;
              $aut = explode(" ",$au);
              $family[] = $aut[(count($aut)-1)];
            }
          }
          $family = $family[0];
          $author = implode(" and ",$authors);

          $year = null;
          if ($year == null and  isset($answer_arr['year'])) {
            $year = $answer_arr['year'];
          }
          if (isset($answer_arr['license']) and $year == null) {
              $datetime = $answer_arr['license'];
              if (isset($datetime[0]['start']['date-time'])) {
                $dtime = $datetime[0]['start']['date-time'];
                $year = date('Y',strtotime($dtime));
              }
          }
          if (isset($answer_arr['published-print']) and $year == null) {
              $datetime = $answer_arr['published-print'];
              $year='here';
              if (isset($datetime['date-time'])) {
                $dtime = $datetime['date-time'];
                $year = date('Y',strtotime($dtime));
              } elseif (isset($datetime['date-parts'])) {
                $dtp =  $datetime['date-parts'];
                if (isset($dtp['year'])) {
                  $year = $dtp['year'];
                } elseif (!is_array($dtp[0])) {
                    $year = $dtp[0];
                } else {
                  $year =$dtp[0][0];
                }
              }
          }
          $bibkey = $family."_".$year;
          $result = [
          'author' => $author,
          'year' => $year,
          'title' => isset($answer_arr['title']) ? implode(' ',$answer_arr['title']) : null,
          'issn' => isset($answer_arr['ISSN']) ? implode(" | ",$answer_arr['ISSN']) : null,
          'issue' => isset($answer_arr['issue']) ? $answer_arr['issue'] : null,
          'url' => isset($answer_arr['URL']) ? $answer_arr['URL'] : null,
          'doi'  => $doi,
          'volume' => isset($answer_arr['volume']) ? $answer_arr['volume'] : null,
          'page' => isset($answer_arr['page']) ? $answer_arr['page'] : null,
          'journal' => isset($answer_arr['container-title']) ? implode(" ",$answer_arr['container-title']) : null,
          'journal_short' => isset($answer_arr['short-container-title']) ? implode(" ",$answer_arr['short-container-title']) : null,
          'published' => isset($answer->message->publisher) ? $answer->message->publisher : null,
          ];
          $bibtex = "@article{".$bibkey;
          foreach($result as $key => $value) {
            if (is_array($value)) {
              $value = implode(' ',$value);
            }
            $bibtex .= ",\n     ".$key." = {".$value."}";
          }
          $bibtex .= "\n}";
          //return json_encode($answer_arr);
          return $bibtex;
        }
        return null;
    }

    public function getMobot($searchstring)
    {
        $searchar = explode(' ', $searchstring);
        // special case! MOBOT treats "forma" abbreviation as f.
        if (4 == sizeof($searchar) and 'f.' == $searchar[2]) {
            return $this->getMobotInner(
                $searchar[0].' '.$searchar[1].' fo. '.$searchar[3]
            );
        }
        // just get MOBOT data if single name or binomial name or a full subsp/var name
        if (3 != sizeof($searchar)) {
            return $this->getMobotInner($searchstring);
        }

        // otherwise... we need to guess if this is subsp, var or f...
        $subname = $searchar[0].' '.$searchar[1].' subsp. '.$searchar[2];
        $try = $this->getMobotInner($subname);
        // if we find something, return it!
        if (!($try[0] & self::NOT_FOUND)) {
            return $try;
        }
        $varname = $searchar[0].' '.$searchar[1].' var. '.$searchar[2];
        $try = $this->getMobotInner($varname);
        if (!($try[0] & self::NOT_FOUND)) {
            return $try;
        }
        $fname = $searchar[0].' '.$searchar[1].' fo. '.$searchar[2];
        // if we arrived here and nothing was found, nothing will.
        return $this->getMobotInner($fname);
    }

    protected function getMobotInner($searchstring)
    {
        // replaces . in "var." or "subsp."
        $searchstring = str_replace('.', '%2e', $searchstring);
        $flags = 0;
        $apikey = config('app.mobot_api_key');
        $base_uri = 'http://services.tropicos.org/';
        $client = new Guzzle(['base_uri' => $base_uri, 'proxy' => $this->proxystring]);
        //# STEP ONE, search for name summary
        try {
            $response = $client->request('GET',
                        "Name/Search?name=$searchstring&type=exact&apikey=$apikey&format=json"
                );
        } catch (ClientException $e) {
            return null; //FAILED
        }
        if (200 != $response->getStatusCode()) {
            return null;
        } // FAILED
        $answer = json_decode($response->getBody());
        if (isset($answer[0]->Error)) {
            return [self::NOT_FOUND];
        }

        if ($answer[0]->TotalRows > 1) {
          //get only valid records
          $newanswer = [];
          foreach($answer as $record) {
              if (in_array($record->NomenclatureStatusName, ['Legitimate', 'No opinion', 'nom. cons.'])) {
                $newanswer[] = $record;
              }
           }
           if (count($newanswer)>1) {
             $flags = $flags | self::MULTIPLE_HITS;
           } elseif (count($newanswer)==1) {
             $answer = $newanswer;
           }
        }

        //get synonyms is is the case
        $senior = null;
        if (!in_array($answer[0]->NomenclatureStatusName, ['Legitimate', 'No opinion', 'nom. cons.'])) {
            //# STEP TWO, look for valid synonyms
            $response = $client->request('GET',
                        'Name/'.$answer[0]->NameId."/AcceptedNames?apikey=$apikey&format=json"
                );
            if (200 != $response->getStatusCode()) {
                return null;
            } // FAILED
            $synonym = json_decode($response->getBody());
            if (isset($synonym[0]->Error)) {
                $flags = $flags | self::NONE_SYNONYM;
            } else {
                $senior = $synonym[0]->AcceptedName->ScientificName;
            }
        }
        return [$flags,
                'rank' => $answer[0]->RankAbbreviation,
                'author' => $answer[0]->Author,
                'valid' => $answer[0]->NomenclatureStatusName,
                'reference' => $answer[0]->DisplayReference.', '.$answer[0]->DisplayDate,
                'parent' => $answer[0]->Family,
                'key' => $answer[0]->NameId,
                'senior' => $senior,
        ];
    }


    //when multiple hits, get only those really matching searchstring
    public static function filterIPNI($searchstring,$answer)
    {
      $keys = explode('%', $answer[0]);
      $rets = array();
      $dates = array();
      for($i=1;$i<count($answer);$i++) {
        $ret = explode('%', $answer[$i]);
        if (count($keys)==count($ret)) {
          $ret = array_combine($keys,$ret);
          $name = mb_strtolower((string)Str::of($ret["Full name without family and authors"])->trim());
          if (mb_strtolower($searchstring) == $name) {
              $rets[] = $ret;
              $dates[] = isset($ret['Publication year full']) ? (int) $ret['Publication year full'] :  (int) $ret['Publication year'];
          }
        }
      }
      if (count($rets) == 1) {
        return $rets[0];
      } elseif (count($rets)>0) {
        #get oldest record if still more than one (priority)
        $key = array_search(min($dates),$dates);
        if (!is_array($key) and $dates[$key] != null) {
          return $rets[$key];
        }
      }
      return null;
    }

    public function getIpni($searchstring)
    {
        // transform names with 3 components to genus epithet subsp. subepithet for IPNI compatibility
        $searchstring = (string)Str::of($searchstring)->trim();
        $searchar = explode(' ', $searchstring);
        if (3 == sizeof($searchar)) {
            // otherwise... we need to guess if this is subsp, var or f...
            $searchstring = $searchar[0].' '.$searchar[1].' subsp. '.$searchar[2];
        }

        $flags = 0;
        $base_uri = 'http://www.ipni.org/';
        $client = new Guzzle(['base_uri' => $base_uri, 'proxy' => $this->proxystring]);
        try {
            $response = $client->request('GET',"ipni/simplePlantNameSearch.do?find_wholeName=$searchstring&output_format=delimited-short");
        } catch (ClientException $e) {
            return null; //FAILED
        }
        if (200 != $response->getStatusCode()) {
            return null;
        } // FAILED
        $answer = array_filter(explode("\n", (string) $response->getBody()));

        //if empty or only 1 line found (is heading) then not found
        if ('' === $answer[0] | count($answer)==1) {
            return [self::NOT_FOUND];
        }
        if ('<!DOCTPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">' === $answer[0]) {
            return [self::NOT_FOUND];
        } // search error

        $ret = null;
        if (count($answer) > 2) {
            $ret = self::filterIPNI($searchstring,$answer);// bogus hit, like matching genus for species name
            if (is_null($ret)) {
              $flags = $flags | self::MULTIPLE_HITS;
              return [self::NOT_FOUND];
            }
        } else {
          $keys = explode('%', $answer[0]);
          $ret = explode('%', $answer[1]);
          $ret = array_combine($keys,$ret);
          if ($searchstring != $ret["Full name without family and authors"]) {
            // bogus hit, like matching genus for species name
            return [self::NOT_FOUND];
          }
        }
        $publication = $ret['Publication'].' '.$ret['Collation'].", ".$ret['Publication year full'];
        $publication = Str::of($publication)->trim();
        $author = isset($ret['Publishing author']) ? $ret['Publishing author'] : $ret['Basionym']." ".$ret['Authors'];
        $author = Str::of($author)->trim();
        $rank = $ret['Rank'];
        switch ($rank) {
          case 'sp.':
          case 'spec.':
          case 'species':
          case 'subg.':
          case 'subgenus':
          case 'sect.':
          case 'section':
                  $parent =  $ret['Genus'];
                  break;

          case 'gen.':
          case 'genus':
          case 'subfam.':
          case 'subfamily':
                  $parent = $ret['Family'];
                  break;

          case 'subsp.':
          case 'subspecies':
          case 'var.':
          case 'variety':
          case 'f.':
          case 'fo.':
          case 'form':
                $parent = explode(" ",$searchstring);
                $parent = $parent[0]." ".$parent[1];
                break;
          default:
                  $parent = null;
            break;
        }

        return [$flags,
                'rank' => $ret['Rank'],
                'author' => (string)$author,
                'valid' => $ret['Name status'],
                'reference' => (string)$publication,
                'parent' => $parent,
                'key' => $ret['Id'],
                'senior' => null,
        ];
    }


    /* THIS FUNCTION IS HERE FOR FUTURE AS zoobank API IS STILL QUITE INCOMPLETE AND GBIF CAN BE USED UNTIL*/
    public function getZOOBANK($searchstring)
    {
      $flags = 0;
      $base_uri = 'http://zoobank.org/';
      $search_values = explode(' ',$searchstring);
      $searchstring2 = str_replace(" ","_",$searchstring);
      $client = new Guzzle(['base_uri' => $base_uri]);// 'proxy' => $this->proxystring]);
      try {
          $response = $client->request('GET',"NomenclaturalActs.json/".$searchstring2);
      } catch (ClientException $e) {
          return null; //FAILED
      }
      if (200 != $response->getStatusCode()) {
          return null;
      } // FAILED
      $answer = json_decode($response->getBody(),true);

      $result = array();
      foreach($answer as $zoo) {
        $parent = null;
        $rank = strtolower($zoo['rankgroup']);
        $namestring = $zoo['namestring'];

        $author = str_replace($namestring,"",$zoo['value']);
        $author = (string)Str::of($author)->trim();
        $valid = false;
        if (count($search_values)==2 and $namestring == $search_values[1]) {
            $valid=true;
            $parent = $search_values[0];
        }
        if (count($search_values)==3 and $namestring == $search_values[2]) {
            $valid=true;
            $parent = $search_values[0]." ".$search_values[1];
        }
        if (count($search_values)==1 and $namestring == $searchstring) {
            $valid=true;
        }
        if (is_null($parent) and $zoo['parentname'] !== "") {
          $parent = $zoo['parentname'];
        }
        if ($valid) {
          $filtered = [
            'author' => $author,
            'parent' => $parent,
            'rank' => $rank
          ];
          $result[$zoo['tnuuuid']] = $filtered;
        }
      }

      //if there is more than one, get one for which the author pattern is more common
      $counts = array_count_values(array_column($result,'author'));
      if (count($counts)>1) {
         $max_counts = max($counts);
         $filtered_counts = array_filter($counts, function($element) use($max_counts){
            return $element == $max_counts;
          });
         if (count($filtered_counts) == 1) {
              $keys = array_search(array_keys($filtered_counts),array_column($result,'author'));
              $key = array_keys($result)[$keys];
              $result= $result[$key];
              $result = array_merge($result,array('zoobank_key'=> $key));
         } else {
              //STILL MANY, MUST BE CHECKED MANUALLY
              $result = null;
         }
      } else {
        if (count($result)==1) {
          $key = array_keys($result)[0];
          $result = $result[$key];
          $result = array_merge($result,array('zoobank_key' => $key));
        }
      }

      if (is_null($result) or count($result)==0) {
        return [self::NOT_FOUND];
      }

      return [$flags,
              'rank' => $result['rank'],
              'author' => $result['author'],
              'valid' => null,
              'reference' => null,
              'parent' => $result['parent'],
              'key' => $result['zoobank_key'],
              'senior' => null,
      ];
      //return $results;
    }

    //GBIF API IMPLEMENTED FOR ANIMAL NAMES MAINLY //
    //gbif has several LIMITATIONS AS TAXON API,  as it is not really a nomenclature database (IT IS HERE ONLY BECAUSE ZOOBANK DOES NOT HAVE MANY COMMON NAMES
    public static function filterGBIF($searchstring,$results)
    {
      $result = array();
      foreach($results as $values) {
        if ($values['canonicalName']== $searchstring and !$values['synonym'] and "" !== $values['authorship'] and array_key_exists('rank',$values)) {
            $filtered = [
              'author' => $values['authorship'],
              'parent' => isset($values['parent']) ? $values['parent'] : null,
              'rank' => mb_strtolower($values['rank']),
              'scientificName' => $values['scientificName']
            ];
            $result[$values['key']] = $filtered;
        }
      }
      //if there is more than one, get one for which the author pattern is more common
      $counts = array_count_values(array_column($result,'author'));
      if (count($counts)>1) {
         $max_counts = max($counts);
         $filtered_counts = array_filter($counts, function($element) use($max_counts){
            return $element == $max_counts;
          });
         if (count($filtered_counts) == 1) {
              $keys = array_search(array_keys($filtered_counts),array_column($result,'author'));
              $key = array_keys($result)[$keys];
              $result= $result[$key];
              $result = array_merge($result,array('gbif_key'=> $key));
         } else {
              //STILL MANY, MUST BE CHECKED MANUALLY
              $result = null;
         }
      } else {
        if (count($result)==1) {
          $key = array_keys($result)[0];
          $result = $result[$key];
          $result = array_merge($result,array('gbif_key' => $key));
        } else {
          $result = null;
        }
      }

      return $result;
    }


    public function getGBIF($searchstring)
    {
      $flags = 0;
      $base_uri = 'https://api.gbif.org/';
      //$searchstring = htmlspecialchars($searchstring);
      $client = new Guzzle(['base_uri' => $base_uri]);// 'proxy' => $this->proxystring]);
      try {
          $response = $client->request('GET',"v1/species?name=".$searchstring);
      } catch (ClientException $e) {
          return null; //FAILED
      }
      if (200 != $response->getStatusCode()) {
          return null;
      } // FAILED
      $answer = json_decode($response->getBody(),true);

      $results = $answer['results'];

      $result = self::filterGBIF($searchstring,$results);

      if (is_null($result)) {
        return [self::NOT_FOUND];
      }

      return [$flags,
              'rank' => $result['rank'],
              'author' => $result['author'],
              'valid' => null,
              'reference' => null,
              'parent' => $result['parent'],
              'key' => $result['gbif_key'],
              'senior' => null,
      ];
    }


    // small helper for getting nested fields
    protected function getElement($xml, $field)
    {
        if (is_object($xml)) {
            Log::warning('Object received in ExternalAPIs->getElement'.serialize($xml));

            return null;
        }
        $object = simplexml_load_string($xml);

        return (string) $object->{$field};
    }

    public function getMycobank($searchstring)
    {
        $searchar = explode(' ', $searchstring);
        // just get Mycobank data if single name or binomial name or a full subsp/var name
        if (3 != sizeof($searchar)) {
            return $this->getMycobankInner($searchstring);
        }

        // otherwise... we need to guess if this is subsp, var or f...
        $subname = $searchar[0].' '.$searchar[1].' subsp. '.$searchar[2];
        $try = $this->getMycobankInner($subname);
        // if we find something, return it!
        if (!($try[0] & self::NOT_FOUND)) {
            return $try;
        }
        $varname = $searchar[0].' '.$searchar[1].' var. '.$searchar[2];
        $try = $this->getMycobankInner($varname);
        if (!($try[0] & self::NOT_FOUND)) {
            return $try;
        }
        $fname = $searchar[0].' '.$searchar[1].' f. '.$searchar[2];
        // if we arrived here and nothing was found, nothing will.
        return $this->getMycobankInner($fname);
    }


    protected function getMycobankInner($searchstring)
    {
        $flags = 0;
        $base_uri = 'http://www.mycobank.org/';
        $client = new Guzzle(['base_uri' => $base_uri, 'proxy' => $this->proxystring]);
        try {
            $response = $client->request('GET',"Services/Generic/SearchService.svc/rest/xml?layout=14682616000000161&filter=name%3D%22$searchstring%22"
                );
        } catch (ClientException $e) {
            return null; //FAILED
        }
        if (200 != $response->getStatusCode()) {
            return null;
        } // FAILED

        $answer = json_decode(json_encode(simplexml_load_string((string) $response->getBody())));
        if (!isset($answer->Taxon)) {
            return [self::NOT_FOUND];
        }
        if (count($answer->Taxon) > 1) {
            $flags = $flags | self::MULTIPLE_HITS;
            $ret = $answer->Taxon[0];
        } else {
            $ret = $answer->Taxon;
        }
        $parent = null;
        // This is needed because of a bug in Mycobank webservice:
        $parent_x = simplexml_load_string('<xml>'.$ret->classification_.'</xml>');
        $parent_id = (string) $parent_x->ChildrenRecord[count($parent_x) - 1]->Id;
        // now we try to find the id in our database...
        $parent_obj = TaxonExternal::where('name', 'Mycobank')->where('reference', $parent_id)->get();
        if ($parent_obj->count()) {
            $parent = [$parent_obj->first()->taxon_id, $parent_obj->first()->taxon->fullname];
        } else { // not found, so we get the name from Mycobank server
            try {
                $response = $client->request('GET',
                    "Services/Generic/SearchService.svc/rest/xml?layout=14682616000000161&filter=_id%3D%22$parent_id%22"
                );
            } catch (ClientException $e) {
                return null; //FAILED
            }
            if (200 != $response->getStatusCode()) {
                return null;
            } // FAILED
            $answer = json_decode(json_encode(simplexml_load_string((string) $response->getBody())));
            $parent = $answer->Taxon->name;
        }
        $senior = null;
        $to_senior = $this->getElement($ret->currentname_pt_, 'Name');
        if ($to_senior != $searchstring) {
            $senior = $to_senior;
        }

        return [$flags,
                'rank' => $this->getElement($ret->rank_pt_, 'Name'),
                'author' => $ret->authorsabbrev_,
                'valid' => $ret->namestatus_,
                'reference' => $this->getElement($ret->literature_pt_, 'Name'),
                'parent' => $parent,
                'key' => $ret->_id,
                'senior' => $senior,
        ];
    }
}
