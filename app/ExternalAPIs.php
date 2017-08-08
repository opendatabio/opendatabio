<?php

namespace App;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Exception\ClientException;
use Log;

class ExternalAPIs
{
	private $proxystring = "";

	const NOT_FOUND = 1;
	const MULTIPLE_HITS = 2;
	const NONE_SYNONYM = 4;

	public function __construct() 
	{
		// Only generates proxyed requests if proxy_url is present
		if (config('app.proxy_url') != '') {
			if(config('app.proxy_user') != '') {
				$this->proxystring = config('app.proxy_user') . ":" . config('app.proxy_password')."@";
			}
			$this->proxystring = $this->proxystring . config('app.proxy_url') . ":" . config('app.proxy_port');
		}
	}
	public function getIndexHerbariorum($acronym)
	{
		$base_uri = "http://sweetgum.nybg.org/science/ih/";
		$client = new Guzzle(['base_uri' => $base_uri, 'proxy' => $this->proxystring]);
		## STEP ONE, get to the list of herbaria
		$response = $client->request('GET', 'herbarium_list.php?col_NamOrganisationAcronym='.$acronym);
		if ($response->getStatusCode() != 200) 
			return null; # FAILED
		$body = (string) $response->getBody();
		if( ! preg_match("/herbarium_details.php\?irn=(\d+)/", $body, $matches))
			return null; # NO RESULTS
		$IRN = $matches[1];
		## STEP TWO, get the herbarium details
		$response = $client->request('GET', 'herbarium.txt.php?irn='.$IRN);
		if ($response->getStatusCode() != 200) 
			return null; # FAILED
		$body = explode("\n", (string) $response->getBody());
		$name = $body[0];

		return [$IRN, $name];
	}
	public function getMobot($searchstring)
	{
		$flags = 0;
		$apikey = config('app.mobot_api_key');
		$base_uri = "http://services.tropicos.org/";
		$client = new Guzzle(['base_uri' => $base_uri, 'proxy' => $this->proxystring]);
        ## STEP ONE, search for name summary
        try {
                $response = $client->request('GET', 
                        "Name/Search?name=$searchstring&type=exact&apikey=$apikey&format=json"
                );
        } catch (ClientException $e) {
                return null; #FAILED 
        }
		if ($response->getStatusCode() != 200) 
			return null; # FAILED
		$answer = json_decode($response->getBody());
		if (isset($answer[0]->Error))
			return [ExternalAPIs::NOT_FOUND];
		if ($answer[0]->TotalRows > 1)
			$flags = $flags | ExternalAPIs::MULTIPLE_HITS;
        Log::info([ $answer[0] ] );
		# Check if this name is accepted
        $senior = null;
        if (! in_array($answer[0]->NomenclatureStatusName, ["Legitimate", "No opinion", "nom. cons."])) {
                ## STEP TWO, look for valid synonyms
                $response = $client->request('GET', 
                        "Name/". $answer[0]->NameId  ."/AcceptedNames?apikey=$apikey&format=json"
                );
                if ($response->getStatusCode() != 200) 
                        return null; # FAILED
                $synonym = json_decode($response->getBody());
                if (isset($synonym[0]->Error)) {
                        $flags = $flags | ExternalAPIs::NONE_SYNONYM;
                } else {
                        $senior = $synonym[0]->AcceptedName->ScientificName;
                }
        }
        return [$flags, 
                "rank"   => $answer[0]->RankAbbreviation,
                "author" => $answer[0]->Author,
                "valid"  => $answer[0]->NomenclatureStatusName,
                "reference" => $answer[0]->DisplayReference . ", " . $answer[0]->DisplayDate,
                "parent" => $answer[0]->Family,
                "key" => $answer[0]->NameId,
                "senior" => $senior,
        ];
	}
	public function getIpni($searchstring)
	{
		$flags = 0;
		$apikey = config('app.mobot_api_key');
		$base_uri = "http://www.ipni.org/";
		$client = new Guzzle(['base_uri' => $base_uri, 'proxy' => $this->proxystring]);
        try {
                $response = $client->request('GET', 
                        "ipni/simplePlantNameSearch.do?find_wholeName=$searchstring&output_format=delimited-short"
                );
        } catch (ClientException $e) {
                return null; #FAILED 
        }
		if ($response->getStatusCode() != 200) 
			return null; # FAILED
        $answer = explode("\n", (string) $response->getBody());
        if ($answer[0] === "")
                return [ExternalAPIs::NOT_FOUND];
		if (count($answer) > 2)
                $flags = $flags | ExternalAPIs::MULTIPLE_HITS;
        $ret = explode("%", $answer[1]);
        return [$flags, 
                "rank"   => $ret[10],
                "author" => $ret[11],
                "valid"  => null,
                "reference" => $ret[15] . " " . $ret[16],
                "parent" => $ret[2],
                "key" => $ret[0],
                "senior" => null,
        ];
	}
}

