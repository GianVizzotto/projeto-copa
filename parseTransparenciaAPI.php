<?php

include ('connect.php');

$parse 		= new parseTransparenciaAPI();
$connection = new createConnection();

$parse->get_data($connection->connectToDatabase());

class parseTransparenciaAPI {
	
	function get_data($conn) {
		$url_portal = 'http://www.portaltransparencia.gov.br/copa2014/api/rest/empreendimento/';
		$result 	= mysql_db_query('vaimudar', "select * from obras_cgu", $conn);
		
		while($row = mysql_fetch_assoc($result, MYSQL_ASSOC)) {
			$id = explode('=', $row['links']);
			if( is_numeric($id[count($id)-1]) ) {
				$curl = curl_init($url_portal.'/'.$id[count($id)-1]);
				echo $url_portal.'/'.$id[count($id)-1]."\n";
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				$data = curl_exec($curl);
				
				$parser = xml_parser_create();
				
				xml_parse_into_struct($parser, $data, $values, $index);
				xml_parser_free($parser);
				
				$valor_total_previsto = $values[$index['VALORTOTALPREVISTO'][count($index['VALORTOTALPREVISTO'])-1]]['value'];
				
//				echo $valor_total_previsto;die;
				
				print_r($index);
				print_r($values);die;
			} else {
				file_put_contents('parser_error_log_transparencia', 'ID: '.$row['id']."<br />", FILE_APPEND);
			}
		}
	}
}