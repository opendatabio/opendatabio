<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Models;

use App\Models\BibReference;
use App\Models\Taxon;

use Illuminate\Support\Str;

use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Exception\ClientException;
use CodeInc\StripAccents\StripAccents;

use Log;

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
          $bibkey = StripAccents::strip( (string) $bibkey);
          $bibkey = preg_replace('/[^A-Za-z0-9\-]/','',$bibkey);
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
            return self::getMobotInner(
                $searchar[0].' '.$searchar[1].' fo. '.$searchar[3]
            );
        }
        // just get MOBOT data if single name or binomial name or a full subsp/var name
        if (3 != sizeof($searchar)) {
            return self::getMobotInner($searchstring);
        }

        // otherwise... we need to guess if this is subsp, var or f...
        $subname = $searchar[0].' '.$searchar[1].' subsp. '.$searchar[2];
        $try = self::getMobotInner($subname);
        // if we find something, return it!
        if (!($try[0] & self::NOT_FOUND)) {
            return $try;
        }
        $varname = $searchar[0].' '.$searchar[1].' var. '.$searchar[2];
        $try = self::getMobotInner($varname);
        if (!($try[0] & self::NOT_FOUND)) {
            return $try;
        }
        $fname = $searchar[0].' '.$searchar[1].' fo. '.$searchar[2];
        // if we arrived here and nothing was found, nothing will.
        return self::getMobotInner($fname);
    }

    public static function getMobotInner($searchstring)
    {
        // replaces . in "var." or "subsp."
        $searchstring = str_replace('.', '%2e', $searchstring);
        $flags = 0;
        $apikey = config('app.mobot_api_key');
        $base_uri = 'https://services.tropicos.org/';
        $client = new Guzzle(['base_uri' => $base_uri]); //, 'proxy' => $this->proxystring]);
        //# STEP ONE, search for name summary
        try {
            $response = $client->request('GET',"Name/Search?name=$searchstring&type=exact&apikey=$apikey&format=json");
        } catch (\ClientException $e) {
            return null; //FAILED
        } catch (\Exception $e) {
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

        //get synonyms if is the case
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

        //get higher to get parent
        $response = $client->request('GET','Name/'.$answer[0]->NameId."/HigherTaxa?apikey=$apikey&format=json"
        );
        if (200 != $response->getStatusCode()) {
            return null;
        } // FAILED
        $highertaxa = json_decode($response->getBody());
        $parent = null;
        if (count($highertaxa)>1) {
          $parent_idx = count($highertaxa)-1;
          $parent = $highertaxa[$parent_idx]->ScientificName;
        }
        return [$flags,
                'rank' => $answer[0]->RankAbbreviation,
                'author' => $answer[0]->Author,
                'valid' => $answer[0]->NomenclatureStatusName,
                'reference' => $answer[0]->DisplayReference.', '.$answer[0]->DisplayDate,
                'parent' => $parent,
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
        } catch (\ClientException $e) {
            return null; //FAILED
        } catch (\Exception $e) {
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
      } catch (\ClientException $e) {
          return null; //FAILED
      } catch (\Exception $e) {
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

    //GBIF API //
    //It includes tropicos and ipni, so, try to get all results from this single query
    public function getGBIF($searchstring)
    {
        $gbif_record = null;
        $gbif_senior = null;
        $base_uri = 'https://api.gbif.org/';
        //search locale_canonicalName exact match
        $get_string = "v1/species?name=".$searchstring;
        $results = ExternalAPIs::makeRequest($base_uri,$get_string);
        $isarray = is_array($results);

        $matchtype = "EXACT";
        //try a fuzzy match if not found
        if (is_null($results) or ($isarray and count($results)==0)) {
          $get_string = "v1/species/match?name=".$searchstring;
          $results = ExternalAPIs::makeRequest($base_uri,$get_string);
          if ($results['matchType']=="NONE") {
            return null;
          }
          $matchtype = $results['matchType'];
          $searchstring =$results["canonicalName"];
          $get_string = "v1/species?name=".$searchstring;
          $results = ExternalAPIs::makeRequest($base_uri,$get_string);
        }

        $keys = [];
        $nubkeys = [];
        $years = [];
        foreach($results as $values) {
          $canonicalName = $values['canonicalName'];
          $rank = (isset($values['rank']) and $values['rank'] != "") ? $values['rank'] : null;
          $nubkey = (isset($values['nubKey']) and $values['nubKey'] != "") ? $values['nubKey'] : null;
          $taxonID = (isset($values['taxonID']) and $values['taxonID'] != "") ? $values['taxonID'] : null;
          $is_ipni = preg_match("/lsid:ipni.org/i", $taxonID);
          $is_tropicos = (mb_strtolower(substr($taxonID,0,4))=="tro-" or preg_match("/tropicos/i", $taxonID));

          $publishedIn = (isset($values['publishedIn']) and $values['publishedIn'] != "") ? $values['publishedIn'] : null;
          $year = 99999;
          if ($publishedIn) {
            $year_pattern = '~\b\d{4}\b\*?~';
            if (preg_match_all($year_pattern, $publishedIn, $matches)) {
               $matches = array_unique($matches[0]);
               if (count($matches)==1) {
                 $match = $matches[0];
                  if ($match>1500 and $match<(today()->format("Y"))) {
                    $year = $match;
                  }
               }
            }
          }
          $years[] = $year;
          $parent = (isset($values['parent']) and $values['parent'] != "") ? $values['parent'] : null;
          $nw = explode(" ",$searchstring);
          if (count($nw)>1 and $parent != null) {
            $pattern = "/".$parent."/i";
            $is_parent = preg_match($pattern, $searchstring);
          } else {
            $is_parent = true;
          }
          if($canonicalName==$searchstring and $is_parent) {
              if ($is_ipni) {
                $keys['ipni'] = $taxonID;
              }
              if ($is_tropicos) {
                $keys['tropicos'] = $taxonID;
              }
              if ($nubkey!=null) {
                $keys['gbif'] = $nubkey;
                $nubkeys[] = $nubkey;
              }
          }
        }


        //get record
        if (count(array_unique($nubkeys))>1) {
          $oldest = (min($years)!=99999) ? min($years) : null;
          if ($oldest) {
            $ykeys = array_keys($years,$oldest);
            $nbk = array_intersect_key($nubkeys,$ykeys);
            if (count(array_unique($nbk))==1) {
              $nubkeys = array_unique($nbk);
            }
          } else {
            $nbkcount = array_count_values($nubkeys);
            $ykeys = array_keys($nbkcount, max($nbkcount));
            if (count($ykeys)==1) {
              $nubkeys = $ykeys;
            }
          }
        }

        if (count(array_unique($nubkeys))==1) {
            $get_string = "v1/species/".$nubkeys[0];
            $gbif_record = ExternalAPIs::makeRequest($base_uri,$get_string);
            if ($gbif_record['synonym']) {
              $get_string = "v1/species/".$gbif_record['acceptedKey'];
              $gbif_senior = ExternalAPIs::makeRequest($base_uri,$get_string);
            }
            return ['keys' => $keys, 'gbif_record'=>$gbif_record, 'match_type' => $matchtype, 'gbif_senior' => $gbif_senior ];
        }
        return null;
    }

    public static function getGBIFParentPathData($gbifnubkey,$include_first=false)
    {
        if (!isset($gbifnubkey)) {
          return [];
        }
        $base_uri = 'https://api.gbif.org/';
        $get_string = "v1/species/".$gbifnubkey;
        $firstrecord = ExternalAPIs::makeRequest($base_uri,$get_string);
        if (is_null($firstrecord) or (is_array($firstrecord) and count($firstrecord)==0)) {
          return [];
        }
        $idx = 0;
        $data_array = [];
        if ($include_first) {
          $data_array[$idx] = $firstrecord;
          $idx++;
        }
        //loop from record up to parent root until find registered in ODB
        //$running_key = isset($firstrecord['parentKey']) ? $firstrecord['parentKey'] : null;

        $running_key = isset($firstrecord['parentKey']) ? $firstrecord['parentKey'] : null;

        if (isset($running_key)) {

            /* this is for cases in which the parent is the accepted key */
            $parent = isset($firstrecord['parent']) ? $firstrecord['parent'] : null;
            $name = isset($firstrecord['canonicalName']) ? $firstrecord['canonicalName'] : null;
            $stop =0;
            while($stop == 0) {
                $nw = explode(" ",$name);
                if (count($nw)>1 and $parent != null) {
                  $pattern = "/".$parent."/i";
                  $is_parent = preg_match($pattern, $name);
                  //gbif may report accepted taxon as parent for a synonym
                  if (!$is_parent) {
                    if (count($nw)>=3) {
                      $finalparent = $nw[0]." ".$nw[1];
                    } else {
                      $finalparent = $nw[0];
                    }
                  }
                } else {
                  $is_parent = true;
                }
                if ($is_parent) {
                  $get_string = "v1/species/".$running_key;
                  $gbifdata = ExternalAPIs::makeRequest($base_uri,$get_string);
                } else {
                  $apis = new ExternalAPIs();
                  $gbifdata = $apis->getGBIF($finalparent);
                  $gbifdata = isset($gbifdata['gbif_record']) ? $gbifdata['gbif_record'] : null;
                }
                if (is_null($gbifdata) or (is_array($gbifdata) and count($gbifdata)==0) or !isset($gbifdata['canonicalName'])) {
                  $stop = 1;
                  break;
                }
                $rank = Taxon::getRank($gbifdata['rank']);
                $name = isset($gbifdata["canonicalName"]) ? $gbifdata['canonicalName'] : null;
                $is_registered = Taxon::whereRaw('(odb_txname(name, level, parent_id) LIKE "'.$name.'") AND level='.$rank)->get();
                if ($is_registered->count()>0) {
                  //add this registered parent to previous record
                  if (count($data_array)>0) {
                    $data_array[($idx-1)]['parent_id'] = $is_registered->first()->id;
                  }
                  $stop =1;
                  break;
                }
                $data_array[$idx] = $gbifdata;
                $running_key = isset($gbifdata['parentKey']) ? $gbifdata['parentKey'] : null;
                $parent = isset($gbifdata['parent']) ? $gbifdata['parent'] : null;
                $name = isset($gbifdata['canonicalName']) ? $gbifdata['canonicalName'] : null;
                if ($rank === 0 or !isset($running_key)) {
                  $stop =1;
                  break;
                }
                $idx++;
            }
        }
        //if a synonym has an accepted key as well, so get senior path data if this is the case
        $senior_data = [];
        $idxs = 0;
        $accepted_key = isset($firstrecord['acceptedKey']) ? $firstrecord['acceptedKey'] : null;
        if ($accepted_key !== null) {
            $stop=0;
            while($stop == 0) {
                $get_string = "v1/species/".$accepted_key;
                $gbifdata = self::makeRequest($base_uri,$get_string);
                if (is_null($gbifdata) or (is_array($gbifdata) and count($gbifdata)==0)) {
                  $stop = 1;
                  break;
                }
                $rank = Taxon::getRank($gbifdata['rank']);
                $name = $gbifdata['canonicalName'];
                $is_registered = Taxon::whereRaw('(odb_txname(name, level, parent_id) LIKE "'.$name.'") AND level='.$rank)->get();
                if ($is_registered->count()>0) {
                  //add this registered parent to record
                  if ($idxs==0 and $include_first) { //this will be the senior_id of the first record only, and may break
                    $data_array[0]['senior_id'] = $is_registered->first()->id;
                  } elseif ($idxs>0) {
                    //add to previous record parent_id
                    $senior_data[($idxs-1)]['parent_id'] = $is_registered->first()->id;
                  }
                  $stop =1;
                  break;
                }
                if ($idxs==0 and $include_first) {
                  $data_array[0]['senior'] = $gbifdata['canonicalName'];
                }
                $senior_data[$idxs] = $gbifdata;
                $accepted_key = isset($gbifdata['parentKey']) ? $gbifdata['parentKey'] : null;
                if ($rank === 0 or !isset($accepted_key)) {
                  $stop =1;
                  break;
                }
                $idxs++;
            }
        }
        $data_array = array_merge($senior_data,$data_array);
        $data_array = array_unique($data_array,SORT_REGULAR);
        $final_data = [];
        if (count($data_array)>0) {
            $incr = 1;
            foreach($data_array as $gbif_record) {
                  if (!is_null($gbif_record)) {
                      $taxonID = (isset($gbif_record['taxonID']) and $gbif_record['taxonID'] != "") ? $gbif_record['taxonID'] : null;
                      $ipni = preg_match("/lsid:ipni.org/i", $taxonID) ? $taxonID : null;
                      $rank = isset($gbif_record["rank"]) ? Taxon::getRank($gbif_record['rank']) : null;
                      $publishedin = isset($gbif_record["publishedIn"]) ? $gbif_record['publishedIn'] : null;
                      $author = isset($gbif_record["authorship"]) ? $gbif_record['authorship'] : null;
                      $tropicos = (mb_strtolower(substr($taxonID,0,4))=="tro-" or preg_match("/tropicos/i", $taxonID)) ? $taxonID : null;
                      //try to get a tropicos key from tropicos and more info if present
                      if (null == $tropicos) {
                        $mobotdata = self::getMobotInner($gbif_record['canonicalName']);
                        if ($mobotdata[0]!=self::NOT_FOUND) {
                          $tropicos = $mobotdata['key'];
                          $author = (null != $author) ? $author : $mobotdata['author'];
                          $publishedin = (null != $publishedin) ? $publishedin : $mobotdata['reference'];
                        }
                      }
                      $isvalid = isset($gbif_record["taxonomicStatus"]) ? in_array($gbif_record['taxonomicStatus'],["DOUBTFUL","ACCEPTED"]) : 1;
                      $hasenior = isset($gbif_record["senior_id"]) ? $gbif_record['senior_id'] : (isset($gbif_record["senior"]) ? $gbif_record['senior'] : null);
                      if ($isvalid and null != $hasenior) {
                        $isvalid = 0;
                      }
                      $parent = isset($gbif_record["parent"]) ? $gbif_record['parent'] : null;
                      $name = isset($gbif_record["canonicalName"]) ? $gbif_record['canonicalName'] : null;
                      //gbif may report accepted taxon as parent for a synonym
                      $finalparent = $parent;
                      $finalsenior = $hasenior;
                      if ($parent) {
                        $nw = explode(" ",$name);
                        $senior = ($hasenior) ? explode(" ",$hasenior) : [];
                        if (count($nw)>1 and $parent != null) {
                          $pattern = "/".$parent."/i";
                          $is_parent = preg_match($pattern, $name);
                          if (!$is_parent) {
                            if (count($nw)>=3) {
                              $finalparent = $nw[0]." ".$nw[1];
                              $finalsenior = ($hasenior) ? $senior[0]." ".$senior[1]." ".$senior[2] : null;
                            } else {
                              $finalparent = $nw[0];
                              $finalsenior = ($hasenior) ? $senior[0]." ".$senior[1] : null;
                            }
                          }
                        }
                      }

                      $data = [
                        "name"  => $name,
                        "rank" => $rank,
                        "author" => $author,
                        "valid" => $isvalid,
                        "reference" => $publishedin,
                        "parent" => $finalparent,
                        "senior" => $finalsenior,
                        "mobot" => $tropicos,
                        "ipni" => $ipni,
                        'mycobank' => null,
                        'gbif' => isset($gbif_record["nubKey"]) ? $gbif_record['nubKey'] : $gbif_record['key'],
                        'zoobank' => null,
                        'parent_id' => isset($gbif_record["parent_id"]) ? $gbif_record['parent_id'] : ($rank===0 ? 1 : null),
                    ];
                    //echo $data['name']." included <br>";
                    if (isset($final_data[$rank])) {
                      $rank = $rank+$incr;
                      $incr++;
                    }
                    $final_data[$rank] = $data;
                  }
            }
            ksort($final_data);
        }
        return $final_data;
    }

    public static function makeRequest($base_uri,$get_string)
    {
      $client = new Guzzle(['base_uri' => $base_uri]);// 'proxy' => $this->proxystring]);
      try {
          $response = $client->request('GET',$get_string, ['read_timeout' => 10]);
      } catch (\ClientException $e) {
          return null; //FAILED
      } catch (\Exception $e) {
          return null; //FAILED
      }
      if (200 != $response->getStatusCode()) {
          return null;
      } // FAILED
      $results = json_decode($response->getBody(),true);
      if (isset($results['results']))
      {
        return $results['results'];
      }
      return $results;
    }



    public function oldgetGBIF($searchstring)
    {
      $flags = 0;
      $base_uri = 'https://api.gbif.org/';
      //$searchstring = htmlspecialchars($searchstring);

      //search name and get all results
      $get_string = "v1/species?name=".$searchstring;
      $results = self::makeRequest($base_uri,$get_string);
      if (is_null($results)) {
        return [self::NOT_FOUND];
      }

      //filter valid data
      $result = self::filterGBIF($searchstring,$results);

      if (is_null($result)) {
        return [self::NOT_FOUND];
      }

      //use the nubkey to get the publication info
      $nubkey = $result['nubKey'];
      if (!is_null($nubkey)) {
        try {
            $response = $client->request('GET',"v1/species/".$nubkey);
        } catch (\ClientException $e) {
            return null; //FAILED
        } catch (\Exception $e) {
            return null; //FAILED
        }
        if (200 != $response->getStatusCode()) {
            return null;
        } // FAILED
        $answer = json_decode($response->getBody(),true);
        $pubin = null;
        if (isset($answer['publishedIn'])) {
          $pubin = str_replace($answer['authorship']." In:","",$answer['publishedIn']);
          $pubin = (string) Str::of($pubin)->trim();
        }

        return [$flags,
                'rank' => mb_strtolower($answer['rank']),
                'author' => $answer['authorship'],
                'valid' => null,
                'reference' => $pubin,
                'parent' => isset($answer['parent']) ? $answer['parent'] : null,
                'key' => $nubkey,
                'senior' => null,
        ];
      }
      return [$flags,
              'rank' => mb_strtolower($result['rank']),
              'author' => $result['author'],
              'valid' => null,
              'reference' => null,
              'parent' => isset($result['parent']) ? $result['parent'] : null,
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
        } catch (\ClientException $e) {
            return null; //FAILED
        } catch (\Exception $e) {
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



    public static function getMobotParentPath($searchstring,$include_first=false)
    {
        // replaces . in "var." or "subsp."
        $searchstring = str_replace('.', '%2e', $searchstring);
        $flags = 0;
        $apikey = config('app.mobot_api_key');
        if (!$apikey) {
            return [self::NOT_FOUND];
        }

        $base_uri = 'https://services.tropicos.org/';
        $client = new Guzzle(['base_uri' => $base_uri]); //, 'proxy' => $this->proxystring]);
        //# STEP ONE, search for name summary
        try {
            $response = $client->request('GET',"Name/Search?name=$searchstring&type=exact&apikey=$apikey&format=json");
        } catch (\ClientException $e) {
            return null; //FAILED
        } catch (\Exception $e) {
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

        //get synonyms if is the case
        $senior = null;
        $senior_path = [];
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
                //get higher to get parent
                $response = $client->request('GET','Name/'.$synonym[0]->AcceptedName->NameId."/HigherTaxa?apikey=$apikey&format=json"
                );
                if (200 == $response->getStatusCode()) {
                    $senior_path = json_decode($response->getBody());
                }
            }
        }
        //define first records
        $rank = Taxon::getRank($answer[0]->RankAbbreviation);
        $firstrecord = [
              'name' => $answer[0]->ScientificName,
              'rank' => $rank,
              'author' => $answer[0]->Author,
              'valid' => in_array($answer[0]->NomenclatureStatusName, ['Legitimate', 'No opinion', 'nom. cons.','']),
              'reference' => $answer[0]->DisplayReference.', '.$answer[0]->DisplayDate,
              'mobot' => $answer[0]->NameId,
        ];



        //get higher to get parent
        $response = $client->request('GET','Name/'.$answer[0]->NameId."/HigherTaxa?apikey=$apikey&format=json"
        );
        if (200 != $response->getStatusCode()) {
            return null;
        } // FAILED
        $highertaxa = array_reverse(json_decode($response->getBody()));
        $finaldata = [];
        $previous = null;
        $idx = 0;
        foreach($highertaxa as $parent) {
          $rank = Taxon::getRank($parent->RankAbbreviation);
          $name = $parent->ScientificName;
          if ($idx>0) {
            $finaldata[$idx-1]['parent'] = $name;
          } else {
            $firstrecord['parent'] = $name;
          }
          // Is this taxon already imported?
          $hadtaxon = Taxon::whereRaw('odb_txname(name, level, parent_id) = ? AND level = ?', [$name, $rank])->get();
          if ($hadtaxon->count()==1) {
            if ($idx>0) {
              $finaldata[$idx-1]['parent_id'] = $hadtaxon[0]->id;
            } else {
              $firstrecord['parent_id'] = $hadtaxon[0]->id;
            }
            break;
          }
          $data = [
                'name' => $parent->ScientificName,
                'rank' => $rank,
                'author' => $parent->Author,
                'valid' => in_array($parent->NomenclatureStatusName, ['Legitimate', 'No opinion', 'nom. cons.','']),
                'reference' => $parent->DisplayReference.', '.$parent->DisplayDate,
                'parent' => $previous,
                'mobot' => $parent->NameId,
          ];
          $finaldata[$idx] = $data;
          $previous = $parent->ScientificName;
          $idx++;
        }

        $final_senior = [];
        $previous = null;
        $idx = 0;
        foreach($senior_path as $record) {
          $rank = Taxon::getRank($record->RankAbbreviation);
          $name = $record->ScientificName;
          if ($idx>0) {
            $final_senior[$idx-1]['parent'] = $name;
          }
          // Is this taxon already imported?
          $hadtaxon = Taxon::whereRaw('odb_txname(name, level, parent_id) = ? AND level = ?', [$name, $rank])->get();
          if ($hadtaxon->count()==1) {
            $final_senior[$idx-1]['parent_id'] = $hadtaxon[0]->id;
            break;
          }
          $data = [
                'name' => $record->ScientificName,
                'rank' => $rank,
                'author' => $record->Author,
                'valid' => in_array($record->NomenclatureStatusName, ['Legitimate', 'No opinion', 'nom. cons.','']),
                'reference' => $record->DisplayReference.', '.$record->DisplayDate,
                'parent' => $previous,
                'mobot' => $record->NameId,
          ];
          $final_senior[$idx] = $data;
          $previous = $record->ScientificName;
          $idx++;
        }
        $finaldata = array_merge($finaldata,$final_senior);
        $finaldata = array_unique($finaldata,SORT_REGULAR);
        $final = [];
        $rx = 1;
        foreach($finaldata as $data) {
          $rank = $data[ 'rank'];
          if (isset($final[$rank])) {
            $rank = $rank+$rx;
            $rx++;
          }
          $final[$rank] = $data;
        }
        if ($include_first) {
          $rank = Taxon::getRank($answer[0]->RankAbbreviation);
          if (isset($final[$rank])) {
            $rank = $rank+1000;
          }
          $final[$rank] = $firstrecord;
        }
        ksort($final);
        return $final;
    }

    /* to get country iso code */
    public static function getWorldBankCountry()
    {
      $base_uri = 'http://api.worldbank.org/';
      $client = new Guzzle(['base_uri' => $base_uri]); //, 'proxy' => $this->proxystring]);
      //# STEP ONE, search for name summary
      try {
          $response = $client->request('GET',"v2/country?format=json&per_page=310");
      } catch (\ClientException $e) {
          return null; //FAILED
      } catch (\Exception $e) {
          return null; //FAILED
      }
      if (200 != $response->getStatusCode()) {
          return null;
      } // FAILED
      $answer = json_decode($response->getBody());
      return $answer[1];
    }

    public static function getCountryISOCode($country)
    {
      $countries = self::getWorldBankCountry();
      $filtered = array_filter($countries,function($f) use($country) {
          $name = $f->name;
          if (mb_strtolower($name)==mb_strtolower($country)) {
            return true;
          }
          return false;
      });
      if (count($filtered)==1) {
        $filtered = array_values($filtered)[0];
        return isset($filtered->iso2Code) ? $filtered->iso2Code : null;
      }
      return null;
    }


}
