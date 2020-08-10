<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App;

use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Exception\ClientException;
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
            $flags = $flags | self::MULTIPLE_HITS;
        }
        // Check if this name is accepted
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

    public function getIpni($searchstring)
    {
        // transform names with 3 components to genus epithet subsp. subepithet for IPNI compatibility
        $searchar = explode(' ', $searchstring);
        if (3 == sizeof($searchar)) {
            // otherwise... we need to guess if this is subsp, var or f...
            $searchstring = $searchar[0].' '.$searchar[1].' subsp. '.$searchar[2];
        }

        $flags = 0;
        $base_uri = 'http://www.ipni.org/';
        $client = new Guzzle(['base_uri' => $base_uri, 'proxy' => $this->proxystring]);
        try {
            $response = $client->request('GET',
                        "ipni/simplePlantNameSearch.do?find_wholeName=$searchstring&output_format=delimited-short"
                );
        } catch (ClientException $e) {
            return null; //FAILED
        }
        if (200 != $response->getStatusCode()) {
            return null;
        } // FAILED
        $answer = explode("\n", (string) $response->getBody());
        if ('' === $answer[0]) {
            return [self::NOT_FOUND];
        }
        if ('<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">' === $answer[0]) {
            return [self::NOT_FOUND];
        } // search error
        if (count($answer) > 2) {
            $flags = $flags | self::MULTIPLE_HITS;
        }
        $ret = explode('%', $answer[1]);
        if ($searchstring != $ret[14]) {
            // bogus hit, like matching genus for species name
            return [self::NOT_FOUND];
        }

        return [$flags,
                'rank' => $ret[10],
                'author' => $ret[11],
                'valid' => null,
                'reference' => $ret[15].' '.$ret[16],
                'parent' => $ret[2],
                'key' => $ret[0],
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
            $response = $client->request('GET',
                        "Services/Generic/SearchService.svc/rest/xml?layout=14682616000000161&filter=name%3D%22$searchstring%22"
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
