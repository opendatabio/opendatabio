<?php
if (isset($_GET['pai'])) {
	$pai = $_GET['pai'];
	if (isset($_GET['nome'])) {
		$nome = $_GET['nome'];
		$rank1 = $_GET['rank1'];
		$rank2 = $_GET['rank2'];
		$proxy = 'proxy.inpa.gov.br:3128'; // TIRAR SE ESTIVER FORA DO INPA!!!
		$proxyauth = 'username:password'; // TIRAR SE ESTIVER FORA DO INPA!!!
		$url = "http://www.ipni.org/ipni/advPlantNameSearch.do?";
		//echo "$pai<BR>$nome<BR>$rank1<BR>$rank2<BR>";
		// SE PAI FOR FAMÍLIA E NOME FOR GÊNERO, BUSCAR APENAS POR GÊNERO! (http://www.ipni.org/search_tips.html | Using families)
		switch ($rank1) {
			case 140: $q = 'find_family'; break;
			case 150:
			case 160:
			case 170: $q = 'find_infrafamily'; break;
			case 180: $q = 'find_genus'; break;
			case 190:
			case 200:
			case 210: $q = 'find_infragenus'; break;
			case 220: $q = 'find_species'; break;
			case 230:
			case 240:
			case 250:
			case 260:
			case 270: $q = 'find_infraspecies'; break;
		}
		$url = $url . "$q=$pai";
		switch ($rank2) {
			case 140: $q = 'find_family'; $r = 'fam'; break;
			case 150:
			case 160:
			case 170: $q = 'find_infrafamily'; $r = 'infrafam'; break;
			case 180: $q = 'find_genus'; $r = 'gen'; break;
			case 190:
			case 200:
			case 210: $q = 'find_infragenus'; $r = 'infragen'; break;
			case 220: $q = 'find_species'; $r = 'spec'; break;
			case 230:
			case 240:
			case 250:
			case 260:
			case 270: $q = 'find_infraspecies'; $r = 'infraspec'; break;
		}
/*
"normal" — html
"delimited" — 'classic' delimited data
"delimited-minimal" — minimal delimited data
"delimited-short" — short form delimited data
"delimited-extended" — extended delimited data
"full" — full record
*/
		$url = $url . "&$q=$nome&find_isAPNIRecord=on&find_isGCIRecord=on&find_isIKRecord=on&find_rankToReturn=$r&output_format=delimited-extended";
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_PROXY, $proxy); // TIRAR SE ESTIVER FORA DO INPA!!!
		curl_setopt($curl, CURLOPT_PROXYUSERPWD, $proxyauth); // TIRAR SE ESTIVER FORA DO INPA!!!
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$curl_response = curl_exec($curl);
		if (!$curl_response) {
			die('Erro: "' . curl_error($curl) . '" - Código: ' . curl_errno($curl));
		} else {
			echo nl2br($curl_response);
			/* IMPORTANT: IPNI does not have information on what are currently accepted names
			 * http://www.ipni.org/index.html
			 */
		}
	}
}
?>
