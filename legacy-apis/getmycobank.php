<?php
include_once '../../includes_pl/db_connect.php';
include_once '../../includes_pl/functions.php'; // inclui as definições de proxy
sec_session_start();

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

if (isset($_GET['pai'])) {
	$pai = $_GET['pai'];
	$rank2 = $_GET['rank2'];
	if (isset($_GET['nome'])) {
		$nome = $_GET['nome'];
		$rankpai_id = explode("件",$_GET['rank1']);
		$rankpai = $rankpai_id[1];
		$q = "select nome from ranks where id=$rankpai";
		$res = pg_query($conn,$q);
		$row = pg_fetch_array($res,NULL,PGSQL_ASSOC);
		$rankis = $row['nome'];

		//DEFINE A BUSCA SEGUNDO O TIPO DE TAXA A SER EXTRAÍDO 
		//se estiver buscando infraespecie
		if ($rankis=='Species') {
			$epiteto= $nome;
			$namesearch1 = $pai." ".$nome;
			$fieldname = "InfraEspecie";
			$namesearch = "name CONTAINS \"".$pai."\" AND name CONTAINS \"".$nome."\"";
		} 
		else {
			if ($rankis=='Genus') {
				$epiteto= $nome;
				$namesearch1 = $pai." ".$nome;
				$fieldname = "Especie";
				$namesearch = "name CONTAINS \"".$namesearch1."\"";
			} 
			else {
					$epiteto= $nome;
					$fieldname = "Genero ou maior";
					$namesearch1 = $nome;
					$namesearch = "name CONTAINS \"".$namesearch1."\"";
			}
		}
		
		//CONSTROI A QUERY E BAIXA OS DADOS
		$mycobankurl = "http://www.mycobank.org/Services/Generic/SearchService.svc/rest/xml?layout=14682616000000161&filter=".urlencode($namesearch);
		$array = getsimplexml_load_file($mycobankurl,$proxy,$proxyauth);
		//echo $mycobankurl."<br >";
		//echo "<pre>";
		//print_r($array);
		//echo "</pre>";
		//XML VEM UM POUCO DIFERENTE SE FOR SUBESPECIE
		if ($fieldname=='InfraEspecie') {
			$aa = $array;
		} else {
			$aa = $array['Taxon'];
		}
		
		//CHECA o field ID DO MYCOBANK
		$temid = @$aa["_id"];
		//SE TEM ID O RETORNO TEM APENAS UM TAXA E É LEGITIMO
		if (!empty($temid) && ($temid+0)>0) {
			$otaxon = array($aa);
			$validtaxon = array($aa);
		} 
		else { //SE NAO TEM PRECISA BUSCAR SE TEM O NOME LISTADO NO REGISTRO
			$otaxon = array();
			$valitdtaxon = array();
			foreach($aa as $kk => $vv) {
				$nn = $vv['name'];
				$status = $vv['namestatus_'];
				$opeiteto = $vv['epithet_'];
				if ($fieldname=='InfraEspecie') {
					$nnn = explode(" ",$vv['name']);
					unset($nnn[2]);
					$nn = implode(" ",$nnn);
					$nn = trim($nn);
				}
				//SE O NOME FOR IGUAL AO NOME BUSCADO, ENTAO SE FOR LEGITIMO ACEITA
			    if ($nn==$namesearch1 && $opeiteto==$epiteto) {
					$otaxon[] = $vv;
					if ($status=='Legitimate' || $status=='Valid') {
						$valitdtaxon[]  = $vv;
					}
			    }
			}
		}
		
		//SE HOUVER MAIS DE UM ENTAO PEGA SÓ OS LEGITIMOS
		if (count($otaxon)>1) {
			$otaxon = $valitdtaxon;
		}
		//QUANTOS SOBRARAM
		$quantos = count($otaxon);
		
		//SE FOR 1 ENTAO ENCONTROU ALGO
		if ($quantos==1) {
			$otaxon = $otaxon[0];
			$resposta = "Encontrado um registro do nome em Mycobank";
			$status = $otaxon['namestatus_'];
			
			//PEGA O NOME VÁLIDO SE FOR UM NOME INVÁLIDO
			if ($status=='Illegitimate' || $status=='Invalid') {
					$acceptednameid = $otaxon['currentname_pt_']['TargetRecord']['Id'];
					$mycobankurl2 = "http://www.mycobank.org/Services/Generic/SearchService.svc/rest/xml?layout=14682616000000161&filter=_id=\"".$acceptednameid."\"";
					$array2 = getsimplexml_load_file($mycobankurl2,$proxy,$proxyauth);
					$otaxon = $array2['Taxon'];
					$resposta .= "mas o nome é sinônimo de ".$otaxon['currentname_pt_']['TargetRecord']['Name']." para os quais as informações foram extraídas.";
			}
			//SE A BUSCA FOR DE INFRAESPECIE, PEGA O NIVEL
			if ($fieldname=='InfraEspecie') {
				$ifi = explode(" ",$otaxon['name']);
				$infrasppnivel = $ifi[2];
			} else {
				$infrasppnivel = "";
		   }

		//PEGA A PUBLICACAO DO NOME
		$e3787 = explode(",",$otaxon['e3787']);
		unset($e3787[0]);
		$txt2 = implode(",",$e3787);
		$txt2 = explode("[",$txt2);
		$txt2 = $txt2[0];
		$pubref = $txt2;
	
		//ORGANIZA OS RESULTADOS NECESSÁRIOS
		$resarr = array();
		$resarr[]  = array('name' => 'nome',  'value' => trim($otaxon['epithet_']));
		$resarr[]  = array('name' => 'autor',  'value' => trim($otaxon['authorsabbrev_']));
		$resarr[]  = array('name' => 'pubrevista',  'value' => trim($pubref));
		$resarr[]  = array('name' => 'pubano',  'value' => trim($otaxon['nameyear_']));
		$resarr[]  = array('name' => 'mycobankid',  'value' => trim($otaxon['mycobanknr_']));
		$resarr[]  = array('name' => 'subvar',  'value' => trim($infrasppnivel));
		$resultado = array('resposta' => $resposta, 'dados' => $resarr);
		//echo "<pre>";
		//print_r($resultado);
		//echo "</pre>";
	} 
		else {
			$resultado = array('resposta' => "Não encontrado em Mycobank", 'dados' => array());
		}
} 
	else {
		$resultado = array('resposta' => "Não encontrado em Mycobank", 'dados' => array());
}
} else {
		$resultado = array('resposta' => "Não encontrado em Mycobank", 'dados' => array());
}
$json = json_encode($resultado);
echo $json;

?>
