<?php
function getsimplexml_load_file($url,$proxy,$proxyauth) {
	$curl = curl_init($url);
	if (!empty($proxy)) {
		curl_setopt($curl, CURLOPT_PROXY, $proxy); // TIRAR SE ESTIVER FORA DO INPA!!!
		curl_setopt($curl, CURLOPT_PROXYUSERPWD, $proxyauth); // TIRAR SE ESTIVER FORA DO INPA!!!
	}
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	$curl_response = curl_exec($curl);
	$sxml = simplexml_load_string($curl_response);
	$json = json_encode($sxml);
	$array = json_decode($json,TRUE);
	return($array);
}
include_once '../../includes_pl/db_connect.php';
include_once '../../includes_pl/functions.php'; // inclui definições de proxy
sec_session_start();

//INTEGRA BUSCA DE NOME VALIDO QUANDO O NOME NAO FOR LEGITIMO

if (isset($_GET['pai'])) {
	$pai = $_GET['pai'];
	if (isset($_GET['nome'])) {
		$nome = str_replace(' ','+',$_GET['nome']);
		//$rank1 = $_GET['rank1'];
		$rank2 = $_GET['rank2'];

		//QUAL O RANK DO NOME
		$q = "select nome from ranks where id=$rank2";
		$res = pg_query($conn,$q);
		$row = pg_fetch_array($res,NULL,PGSQL_ASSOC);
		$rankNome = $row['nome'];
		
		//QUAL O RANK DO PAI
		$rankpai_id = explode("件",$_GET['rank1']);
		$rankpai = $rankpai_id[1];
		$q = "select nome from ranks where id=$rankpai";
		$res = pg_query($conn,$q);
		$row = pg_fetch_array($res,NULL,PGSQL_ASSOC);
		$rankis = $row['nome'];

		//PAI ESPECIE, BUSCANDO INFRAESPECIFICO
		if ($rankis=='Species') {
			$epiteto= $nome;
			$namesearch1 = $pai." ".$nome;
		} 
		else {
			//PAI GENERO BUSCANDO ESPECIFICO
			if ($rankis=='Genus') {
				$epiteto= $nome;
				$namesearch1 = $pai." ".$nome;
			} 
			else {
					//PAI MAIOR QUE GENERO BUSCANDO FAMILIA OU ORDEM (HABILITADO NO JAVASCRIPT)
					$epiteto= $nome;
					$fieldname = "Genero ou maior";
					$namesearch1 = $nome;
					//$namesearch = "name CONTAINS \"".$namesearch1."\"";
			}
		}
		//CONSTROI A QUERY E BAIXA OS DADOS
		$moboturl = "http://services.tropicos.org/Name/Search?name=$namesearch1&type=exact&apikey=b2c88eaf-66a8-42e0-9931-8c619f4eac1d&format=xml";
		$array = getsimplexml_load_file($moboturl,$proxy,$proxyauth);  //funcao em functions.php
		//echo "<pre>";
		//print_r($array);
		//echo "</pre>";
		$aa = $array['Name'];
		$erro = @$aa['erro'];
		if (!empty($erro)) {
			echo "Erro: nome não encontrado.[$pai][$nome][$rank1][$rank2]";
		} else { 
			//CHECA SE O NOME É LEGÍTIMO. SE NAO FOR PEGA O NOME VALIDO
			if ($aa['NomenclatureStatusName']!="Legitimate" && $aa['NomenclatureStatusName']!="No opinion") {
				//SE FOR INVALIDO PEGA O NOME ACEITO
				$id = $aa['NameId'];
				$url = "http://services.tropicos.org/Name/$id/AcceptedNames?apikey=b2c88eaf-66a8-42e0-9931-8c619f4eac1d&format=xml";
				//echo $url;
				$array = getsimplexml_load_file($url,$proxy,$proxyauth);
				$sinonimos = $array["Synonym"];
				//print_r($sinonimos);
				$synids = array();
				foreach($sinonimos as $kk=>$vv) {
					$synids[] = $vv["AcceptedName"]["NameId"];
				}
				$synids = array_unique($synids);
				if (count($synids)==1) {
					$id2 = $synids[0];
					$url ="http://services.tropicos.org/Name/Search?nameid=$id2&type=exact&apikey=b2c88eaf-66a8-42e0-9931-8c619f4eac1d&format=xml";
					$array = getsimplexml_load_file($url,$proxy,$proxyauth);
					//print_r($array);
					$aa = $array['Name'];
					$nome = $aa['ScientificName'];
				} else {
					echo "Erro: nome ilegítimo mas sinonímia ambígua[$pai][$nome][$rank1][$rank2]";
				}
			}
			if ($aa['NomenclatureStatusName']=="Legitimate" || $aa['NomenclatureStatusName']=="No opinion") {
				echo "{" . $aa['NameId'] . "}";
				echo "{" . $aa['NomenclatureStatusName']  . "}";
				echo "{" . $aa['Author'] . "}";
				echo "{" . $aa['DisplayReference'] . "}";
				echo "{" . $aa['DisplayDate'] . "}";
				echo "{" . $aa['RankAbbreviation'] . "}[$pai][$nome][$rank1][$rank2]";
			}
		}
	}
}
	
?>
