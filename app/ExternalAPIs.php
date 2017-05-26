<?php

namespace App;
use GuzzleHttp\Client as Guzzle;

class ExternalAPIs
{
	private $proxystring = "";

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
}

