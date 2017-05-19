<?php

include_once '../../includes_pl/db_connect.php';
include_once '../../includes_pl/functions.php'; // inclui definições de proxy
sec_session_start();

$code = $_GET['sigla'];
$url = "http://sweetgum.nybg.org/science/ih/herbarium_list.php?col_NamOrganisationAcronym=".$code;
$curl = curl_init($url);
if (!empty($proxy)) {
	curl_setopt($curl, CURLOPT_PROXY, $proxy); // TIRAR SE ESTIVER FORA DO INPA!!!
	curl_setopt($curl, CURLOPT_PROXYUSERPWD, $proxyauth); // TIRAR SE ESTIVER FORA DO INPA!!!
}
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
$curl_response = curl_exec($curl);
if (!$curl_response) {
	die('Erro: "' . curl_error($curl) . '" - Código: ' . curl_errno($curl));
} 	
$arr = explode("\n",$curl_response);
	//echo "<pre>";
						//print_r($arr);
					//echo "</pre>";
$pattern = "/herbarium_details.php/";
foreach($arr as $val) {
	$res = preg_match ($pattern ,$val);
	if ($res==1) {
		$vv = str_replace("<a href=","",$val);
		$vv = str_replace("</a>","",$vv);
		$vv = explode("'",$vv);
		$vv2 = $vv[1];
		$vv2 = explode("irn=",$vv2);
		$irn = $vv2[1];
		if ($irn>0) {
			//details = http://sweetgum.nybg.org/science/ih/herbarium_details.php?irn=124921
			$url = "http://sweetgum.nybg.org/science/ih/herbarium.txt.php?irn=".$irn;
			$curl = curl_init($url);
			if (!empty($proxy)) {
				curl_setopt($curl, CURLOPT_PROXY, $proxy); // TIRAR SE ESTIVER FORA DO INPA!!!
				curl_setopt($curl, CURLOPT_PROXYUSERPWD, $proxyauth); // TIRAR SE ESTIVER FORA DO INPA!!!
			}
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			$curl_response = curl_exec($curl);
			if ($curl_response) {
				$arr2= explode("\n",$curl_response);
				//echo "<pre>";
					//print_r($arr2);
				//echo "</pre>";
				$kk = array_search("",$arr2);
				$nome = $arr2[0];
				$idx= 2;
				$endereco = array();
				$nc = count($arr2);
				for($i=2;$i<$nc,$i++;) {
					$v = trim($arr2[$i]);
					if (empty($v)) {
						break;
					} else {
						$endereco[] = $v;
					}
				}
				$phone = "";
				$email ="";
				$website="";
				$correspondent = "";
				for($ii=$i;$ii<$nc,$ii++;) {
					$v = trim($arr2[$ii]);
					if (!empty($v)) {
						$ov = explode(":",$v);
						$ov = array_unique($ov);
						$k = trim(strtolower($ov[0]));
						unset($ov[0]);
						$tv = implode(":",$ov);
						if ($k=='phone') {
							$phone = trim($tv);
						}
						if ($k=='email') {
							$email = trim($tv);
						}
						if ($k=='url') {
							$tv = str_replace("URL:","",$tv);
							$website = trim($tv);
						}
						if ($k=='correspondent(s)') {
							$correspondent = trim($tv);
						}
					} else {
						if (!empty($correspondent)) {
							break;
						}
					}
				}
				$endereco = implode("\n",$endereco);
				//echo "nome ".$nome."<br >";
				//echo "endereco ".$endereco."<br >";
				//echo "phone  ".$phone."<br >";
				//echo "email  ".$email."<br >";
				//echo "website  ".$website."<br >";
				//echo "correspondent  ".$correspondent."<br >";
				//ORGANIZA OS RESULTADOS NECESSÁRIOS
				$resarr = array();
				$resarr[]  = array('name' => 'nome',  'value' => trim($nome));
				$resarr[]  = array('name' => 'endereco',  'value' => trim($endereco));
				$resarr[]  = array('name' => 'phone',  'value' => trim($phone));
				$resarr[]  = array('name' => 'email',  'value' => trim($email));
				$resarr[]  = array('name' => 'website',  'value' => trim($website));
				$resarr[]  = array('name' => 'correspondent',  'value' => trim($correspondent));
				$resposta = "Encontrado em IndexHerbariorium";
				$resultado = array('resposta' => $resposta, 'dados' => $resarr);
			}
		} else {
				$resposta = "Encontrado em IndexHerbariorium, mas deu erro. Checar Manualmente";
				$resultado = array('resposta' => $resposta, 'dados' => array());
		}
		break;
	} else {
				$resposta = "Não encontrado em IndexHerbariorium";
				$resultado = array('resposta' => $resposta, 'dados' => array());
	}
}
$json = json_encode($resultado);
echo $json;
?>
