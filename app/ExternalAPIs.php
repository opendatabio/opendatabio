<?php

namespace App;
use GuzzleHttp\Client as Guzzle;

class ExternalAPIs
{
	private $proxystring = "";
	public const MOBOT_NOT_FOUND = 1;
	public const MOBOT_MULTIPLE_HITS = 2;
	public const MOBOT_SYNONYM = 4;
	public const MOBOT_MULTIPLE_SYNONYM = 8;
	public const MOBOT_NONE_SYNONYM = 16;

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
		$response = $client->request('GET', 
			"Name/Search?name=$searchstring&type=exact&apikey=$apikey&format=json"
		);
		if ($response->getStatusCode() != 200) 
			return null; # FAILED
		$answer = json_decode($response->getBody());
		if (isset($answer[0]->Error))
			return [ExternalAPIs::MOBOT_NOT_FOUND];
		if ($answer[0]->TotalRows > 1)
			$flags = $flags | ExternalAPIs::MOBOT_MULTIPLE_HITS;
		# Check if this name is accepted
		if ($answer[0]->NomenclatureStatusName == "Legitimate" 
	         or $answer[0]->NomenclatureStatusName == "No opinion")
			return [$flags, $answer[0]];
		## STEP TWO, look for valid synonyms
		$flags = $flags | ExternalAPIs::MOBOT_SYNONYM;
		$response = $client->request('GET', 
			"Name/". $answer[0]->NameId  ."/AcceptedNames?apikey=$apikey&format=json"
		);
		if ($response->getStatusCode() != 200) 
			return null; # FAILED
		$synonym = json_decode($response->getBody());
		if (isset($synonym[0]->Error)) {
			$flags = $flags | ExternalAPIs::MOBOT_NONE_SYNONYM;
			return [$flags, $answer[0]];
		}
//		if (isset($synonym[0]->TotalRows > 1)
//			$flags = $flags | ExternalAPIs::MOBOT_MULTIPLE_SYNONYM;
		return [$flags, $answer[0], $synonym[0]->AcceptedName];
	}
}

