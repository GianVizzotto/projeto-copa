<?php

include ('connect.php');

$parse 		= new parseTransparenciaAPI();
$connection = new createConnection();

$parse->get_data($connection->connectToDatabase());

class parseTransparenciaAPI {
	
	function get_data( $conn ) {
		$url_portal = 'http://www.portaltransparencia.gov.br/copa2014/api/rest/empreendimento';
		$result 	= mysql_db_query('vaimudar', "select * from obras_cgu", $conn);
		$api_data 	= array();
		
		while( $row = mysql_fetch_assoc($result, MYSQL_ASSOC) ) {
			$id = explode('=', $row['links']);
			
			if( is_numeric( $id[count($id)-1] ) ) {
				$data 	= $this->get_curl($url_portal.'/'.$id[count($id)-1]);
//				$data 	= $this->get_curl($url_portal.'/1');
				$parser = xml_parser_create();
				
				xml_parse_into_struct($parser, $data, $values, $index);
				xml_parser_free($parser);
				
				$progresso = 'Não informado';
				if( isset( $index['VALORPERCENTUALEXECUCAOFISICA'] ) ) {
					$progresso_id 	= $index['VALORPERCENTUALEXECUCAOFISICA'][0];
					$progresso 		= $values[$progresso_id]['value'] ;	
				}

				$valor_total_previsto = null;
				if( isset( $index['VALORTOTALPREVISTO'] ) ) {
					$valor_total_previsto = $values[$index['VALORTOTALPREVISTO'][count($index['VALORTOTALPREVISTO'])-1]]['value'];
				}
				
				foreach( $index['ATOM:LINK'] as $l ) {
					if( $values[$l]['attributes']['COPA:ENTIDADEREFERENCIADA'] == 'recursoCaptado' ) {
						$data_recurso_captado = $this->parseRecCaptado($values[$l]['attributes']['HREF']);
					}
				}
				
				$executado = $this->parseExecucaoFinanceira($id[count($id)-1]);

				$api_data[] = array(
					'id' 							=> $row['id'],
					'valor_previsto_transp' 		=> $valor_total_previsto,
					'valor_executado_transp' 		=> $executado,
					'progresso_transp'				=> $progresso,
					'financiador_transp'			=> $data_recurso_captado['nome'],
					'valor_financiado_transp'		=> $data_recurso_captado['valor'],
					'id_transp'			 			=> $id[count($id)-1]
				);
				
//				echo "Obra: ".$row['descricao']."\n";
//				echo "Valor previsto: ".$valor_total_previsto."\n";
//				echo "Valor executado: ".$executado."\n";
//				echo "Progresso: ".$progresso."\n";
//				print_r($data_recurso_captado);
				
			}
			else {
				
				file_put_contents('parser_error_log_transparencia', 'ID: '.$row['id']."<br />", FILE_APPEND);
			}
		}
		
		$this->updateObras($conn, $api_data);
	}
	
	function parseRecCaptado($link) {
		$data = $this->get_curl($link);
		
		$parser = xml_parser_create();
				
		xml_parse_into_struct($parser, $data, $values, $index);
		xml_parser_free($parser);
		
		$nome_idx 	= $index['NOME'][0];
		$valor_idx 	= $index['VALORCEDIDO'][count($index['VALORCEDIDO'])-1];
		
		$dados_captado = array(
			'nome' 	=> $values[$nome_idx]['value'],
			'valor'	=> $values[$valor_idx]['value']
		);
		
		return $dados_captado;
	}
	
	function parseExecucaoFinanceira( $id ) {
		$url 	= 'http://www.portaltransparencia.gov.br/copa2014/api/rest/execucaofinanceira?empreendimento='.$id;
		$data 	= $this->get_curl($url);
		
		$parser = xml_parser_create();
				
		xml_parse_into_struct($parser, $data, $values, $index);
		xml_parser_free($parser);
		
		$total_executado = 0;
		for( $i = 0; $i < count($values); $i++ ) {
			if( $values[$i]['tag'] == 'VALORCONTRATO' ) {
				$total_executado += $values[$i]['value'];
			}
		}
		
		return $total_executado;
	}
	
	function get_curl( $url ) {
		$curl = curl_init($url);
		
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		
		return curl_exec($curl);
	}
	
	function updateObras($conn, $data) {
		
		foreach ($data as $d) {
			
			$update = 
			"update 
				obras_cgu 
			set 
				valor_previsto_transp = ".$d['valor_previsto_transp'].", 
				valor_executado_transp = ".$d['valor_executado_transp'].", 
				progresso_transp = '".$d['progresso_transp']."', 
				financiador_transp = '".$d['financiador_transp']."',
				valor_financiado_transp =". $d['valor_financiado_transp'].",
				id_transp =". $d['id_transp']."
			where
				id = ".$d['id'];

			mysql_db_query('vaimudar', trim(preg_replace('/\t+/', '', $update)), $conn);
		}
	}
}