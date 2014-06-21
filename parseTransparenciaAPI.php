<?php

include ('connect.php');

$parse 		= new parseTransparenciaAPI();
$connection = new createConnection();

$parse->get_data($connection->connectToDatabase());

class parseTransparenciaAPI {
	
	function get_data( $conn ) {
		$url_portal = 'http://www.portaltransparencia.gov.br/copa2014/api/rest/empreendimento';
		$result 	= mysql_db_query('vaimudar', "select * from obras_cgu", $conn);
		
		while( $row = mysql_fetch_assoc($result, MYSQL_ASSOC) ) {
			$id = explode('=', $row['links']);
			
			if( is_numeric( $id[count($id)-1] ) ) {
//				$data 	= $this->get_curl($url_portal.'/'.$id[count($id)-1]);
				$data 	= $this->get_curl($url_portal.'/1');
				$parser = xml_parser_create();
				
				xml_parse_into_struct($parser, $data, $values, $index);
				xml_parser_free($parser);
				
				$valor_total_previsto = $values[$index['VALORTOTALPREVISTO'][count($index['VALORTOTALPREVISTO'])-1]]['value'];
				
				foreach( $index['ATOM:LINK'] as $l ) {
					if( $values[$l]['attributes']['COPA:ENTIDADEREFERENCIADA'] == 'recursoCaptado' ) {
						$data_recurso_captado = $this->parseRecCaptado($values[$l]['attributes']['HREF']);
					}
//					if( $values[$l]['attributes']['COPA:ENTIDADEREFERENCIADA'] == 'recursoPrevisto' ) {
//						$data_recurso_previsto = $this->parseRecPrevisto($values[$l]['attributes']['HREF']);
//					}
//					if( $values[$l]['attributes']['COPA:ENTIDADEREFERENCIADA'] == 'recursoPrevisto' ) {
//						$data_recurso_captado = $this->parseRecCaptado($values[$l]['attributes']['HREF']);
//					}
//					print_r($values[$l]);
					
					
					
					
				}
//				$executado = $this->parseExecucaoFinanceira($id[count($id)-1]);
				$executado = $this->parseExecucaoFinanceira(1);
				echo $valor_total_previsto."\n";
				print_r($data_recurso_captado);
			}
			else {
				
				file_put_contents('parser_error_log_transparencia', 'ID: '.$row['id']."<br />", FILE_APPEND);
			}
			
		}
		
	}
	
	function parseRecCaptado($link) {
		$data = $this->get_curl($link);
		
		$parser = xml_parser_create();
				
		xml_parse_into_struct($parser, $data, $values, $index);
		xml_parser_free($parser);
		
//		print_r($index['VALORCEDIDO']);
//		print_r($values[51]);die;
		
		$nome_idx 	= $index['NOME'][0];
		$valor_idx 	= $index['VALORCEDIDO'][count($index['VALORCEDIDO'])-1];
		
//		print_r($values[$nome_idx]);
//		print_r($values[$valor_idx]);die;
		
		$dados_captado = array(
			'nome' 	=> $values[$nome_idx]['value'],
			'valor'	=> $values[$valor_idx]['value']
		);
		
		return $dados_captado;
	}
	
	function parseRecPrevisto($link) {
		$data = $this->get_curl($link);
		
		$parser = xml_parser_create();
				
		xml_parse_into_struct($parser, $data, $values, $index);
		xml_parser_free($parser);
		
		print_r($index);
		print_r($values);die;
	}
	
	function parseExecucaoFinanceira($id) {
		$url = 'http://www.portaltransparencia.gov.br/copa2014/api/rest/execucaofinanceira?empreendimento='.$id;
		$data = $this->get_curl($url);
		
		$parser = xml_parser_create();
				
		xml_parse_into_struct($parser, $data, $values, $index);
		xml_parser_free($parser);
		
//		print_r($index);
//		print_r($values);die;
		
		for($i = 0; $i < count($values); $i++) {
			echo $values[$i]['tag'] == 'VALORCONTRATO' ? $values[$i]['value']."\n" : '' ;
			print_r($values[$i]['FINANCIADOR']);
//			 echo $values[$i]['tag']."\n";
		}
		die;
	}
	
	function get_curl( $url ) {
		$curl = curl_init($url);
		
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		
		return curl_exec($curl);
	}
}