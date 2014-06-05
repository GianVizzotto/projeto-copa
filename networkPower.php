<?php

include ('connect.php');
// include('/home/gian/proprietarios/htdocs/wp-blog-header.php');
// require('/home/gian/proprietarios/htdocs/wp-includes/taxonomy.php');

$p 			= new powerNetwork();
$connection = new createConnection();

if( $argv[1] == 'id' && $argv[2] ) {

	$p->search_by_id( $connection->connectToDatabase(), $argv[2] );
} elseif ( $argv[1] == 'check_list_of_companies' ) {
	
	$p->check_list_of_companies( $connection->connectToDatabase() );
} elseif ( $argv[1]) {
	
	$p->build( $connection->connectToDatabase(), $argv[1] );
} else {
	die('Parâmetros incorretos');
}

class powerNetwork {
	var $glob;
	
	/**
	 * 
	 * Retorna um array com os registros encontrados a partir do nome passado em argv
	 * @param resource $conn
	 * @param string $name
	 */
	function build($conn, $name) {
		$result = mysql_db_query('proprietarios_redes', "select * from assoc where lower(nome) like lower(('%".trim($name)."%'))", $conn);

		if( !$result ) {
 			echo ("Rede de poder não encontrada\n");
 			
			return false;
		}

		$power = array();
 		while( $row = mysql_fetch_array($result, MYSQL_ASSOC) ) {
			$power[] = $row;
  		}
  		
   		print_r($power);

  		return $power;
	}
	
	/**
	 * 
	 * A partir de um id do objeto em evidência, encontra a empresa controladora última
	 * a empresa que são diretamente controladas e faz a chamada para construção da rede
	 * de poder
	 * @param resource $conn
	 * @param int $id
	 */
	function search_by_id($conn,$id) {
		$result = mysql_db_query('proprietarios_redes', "select * from assoc where id = $id", $conn);
		
		if(!$result) {
			die("Rede de poder não encontrada\n");
		}

		$controlled = array();
		$parent 	= array();
		$top_level	= false;
		$row 		= mysql_fetch_array($result, MYSQL_ASSOC);
		$first_node = $row;
		
		if( $row['id_empresaControladora'] ) {
			$has_controlled = 1;
		
			while($has_controlled) {
				$result 	= mysql_db_query('proprietarios_redes', "select * from assoc where id = ". $row['id_empresaControladora'], $conn);
				$row 		= mysql_fetch_array($result, MYSQL_ASSOC);
				$parent [] 	= $row;
				if($row['id_empresaControladora']) {
					continue;
				} else {
					$has_controlled = 0;
				}
			}
		} else {
			$parent[0] = $row;
			$top_level = true;
		}
//		print_r($parent); die;
		$total = count($parent)-1;
		
		if($parent[$total]['ehControladoraUltima'] || $top_level) {
			$direct_controlled 	= array();
			if( strpos($parent[$total]['id_empresasControladas'], "|") ) {
				$controlled_ids = explode('|', $parent[$total]['id_empresasControladas']);
			} else {
				$controlled_ids[0] = $parent[$total]['id_empresasControladas'];
			}
 			
			for($i = 0; $i < count($controlled_ids); $i++) {
				$reg = $this->search_unique($conn, $controlled_ids[$i]);
				$direct_controlled[] = array(
					'nome' 						=> $reg['nome'],
					'registro' 					=> $reg['registro'], //cnpj ou cpf
					'id_empresasControladas' 	=> $reg['id_empresasControladas'],
					'id'						=> $reg['id'],
				);
			}
			
		}

		if(isset($direct_controlled)) {
 			$this->undirected_controlled($conn, $direct_controlled);
		}
 		
 		$network = array(
 			'company_key'	=> array(
 				'nome' 		=> $first_node['nome'],
 				'registro' 	=> $first_node['registro'],
 				'id' 		=> $first_node['id'],
 			),
 			'controller'	=> array(
 				'nome' 		=> $parent[count($parent)-1]['nome'],
 				'registro' 	=> $parent[count($parent)-1]['registro'],
 				'id' 		=> $parent[count($parent)-1]['id'],
 			),
 			'directed' 		=> isset($direct_controlled) ? $direct_controlled : array(),
 			'undirected' 	=> $this->glob 
 		);
 		
 		unset($this->glob);
 		
 		$this->save_network_power($conn, $network);
 
		die;
	}
	
	/**
	 * 
	 * A partir das empresas diretamente controladas,
	 * busca todas as que fazem parte da rede de poder, recursivamente
	 * @param $conn
	 * @param $companies
	 * @param $general
	 */
	
	function undirected_controlled($conn, $companies, $general = false) {
		foreach($companies as $d) {
		
			if(!$d['id_empresasControladas']) {
				continue;
			}
			
			if(strpos($d['id_empresasControladas'], "|")) {
				$controlled_ids 	= explode('|', $d['id_empresasControladas']);
			} else {
				$controlled_ids[0] 	= $d['id_empresasControladas'];
			}

			for($i = 0; $i < count($controlled_ids); $i++) {
				$reg = $this->search_unique($conn, $controlled_ids[$i]);
				
				$undirected_controlled[] = array(
					'nome' 						=> $reg['nome'],
					'registro' 					=> $reg['registro'], //cnpj ou cpf
					'id_empresasControladas' 	=> $reg['id_empresasControladas'],
					'id'						=> $reg['id'],
				);
			}
			
			if(!$general) {
				$total = $undirected_controlled;
			} else {
				$aux 	= $undirected_controlled;
				$total 	= array_merge ($aux, $general);
			}
			
			$this->glob = $total;
			
			$this->undirected_controlled($conn, $undirected_controlled, $total);
		}
	}
	
	/**
	 * 
	 * Retorna um registro, a partir do id
	 * @param $conn
	 * @param $id
	 */
	
	function search_unique($conn, $id) {
		$result = mysql_db_query('proprietarios_redes', "select * from assoc where id = $id", $conn);
		
		return mysql_fetch_assoc($result, MYSQL_ASSOC);
	}
	
	function save_network_power($conn, $network) {
//		print_r($network);die;
		if( $network['company_key']['registro'] == $network['controller']['registro'] ) {
			
			$network['company_key']['is_controller'] = 1;
			unset($network['controller']);
			
		} elseif ( $this->in_multiarray($network['company_key']['registro'], $network['directed'], 'registro') ) {
			
			$network['company_key']['is_directed_controlled'] = 1;
			 
		} elseif ( $this->in_multiarray($network['company_key']['registro'], $network['undirected'], 'registro') ) {
			
			$network['company_key']['is_undirected_controlled'] = 1;
			
		}
		
		$info = array(
			'key_id' 					=> $network['company_key']['id'], 
			'is_controller' 			=> isset($network['company_key']['is_controller']) ? 1 : 0,  
			'is_directed_controlled' 	=> isset($network['company_key']['is_directed_controlled']) ? 1 : 0,
			'is_undirected_controlled' 	=> isset($network['company_key']['is_undirected_controlled']) ? 1 : 0,
			'reference_id'			 	=> $network['company_key']['id'],
			'name' 						=> $network['company_key']['nome'],
			'key_name' 					=> $network['company_key']['nome'],
			'registry' 					=> $network['company_key']['registro'],
			'is_key'					=> 1,
		);
		echo "salvando empresa chave\n";
		$this->populate($conn, $info);
		unset($info);
		
		if( isset($network['controller']) ) {
			$info = array(
				'key_id' 					=> $network['company_key']['id'], 
				'is_controller' 			=> 1,  
				'is_directed_controlled' 	=> 0,
				'is_undirected_controlled' 	=> 0,
				'reference_id'			 	=> $network['controller']['id'],
				'name' 						=> $network['controller']['nome'],
				'registry' 					=> $network['controller']['registro'],
				'key_name' 					=> $network['company_key']['nome'],
				'is_key'					=> 0
			);
			echo "salvando empresa controladora\n";
			$this->populate($conn, $info);
		}
		
		$size 		= count($network['directed'])-1;
		$key_name 	= 'directed';
		$i 			= 0;
		$stop 		= false;
		
		while(!$stop) {
			if(isset($network[$key_name])) {
				foreach ($network[$key_name] as $value) {
					echo "salvando empresa ".$value['nome']."\n";
					$info = array(
						'key_id' 					=> $network['company_key']['id'], 
						'is_controller' 			=> 0,  
						'is_directed_controlled' 	=> $key_name == 'directed' ? 1 : 0,
						'is_undirected_controlled' 	=> $key_name == 'undirected' ? 1 : 0,
						'reference_id'			 	=> $value['id'],
						'name' 						=> $value['nome'],
						'registry' 					=> $value['registro'],
						'key_name' 					=> $network['company_key']['nome'],
						'is_key'					=> 0,
					);
					
					$this->populate($conn, $info);
				}
			}
			$i++;
			
			$key_name = 'undirected';
			if( $i == 2 ) {
				$stop = true;
			}
		}
	}
	
	function populate($conn, $str) {
		$registry = isset($str['registry']) ? $str['registry'] : 'null';
		 
		$insert = 
			"INSERT INTO
			custom_networks (
			id,
			key_id, 
			is_controller, 
			is_directed_controlled,
			is_undirected_controlled, 
			reference_id, 
			name, 
			registry, 
			key_name, 
			is_key)
			VALUES (
			null,".
			$str['key_id'].",".
			$str['is_controller'].",". 
			$str['is_directed_controlled'].",".
			$str['is_undirected_controlled']."," .
			$str['reference_id'].",". 
			"'".$str['name']."',". 
			$registry.",". 
			"'".$str['key_name']."',". 
			$str['is_key'].
			");";
				
		$result = mysql_db_query('vaimudar', trim(preg_replace('/\t+/', '', $insert)), $conn);
	}
	
	/**
	 * 
	 * Essa função é auxiliar
	 * Consome um arquivo de texto, contendo os objetos de pesquisa
	 * verifica sua existência no banco e cria um arquivo de texto 
	 * com os registros encontrados, referentes aos objetos de pesquisa
	 * @param $conn
	 */
	function check_list_of_companies($conn) {
		$comp = file('/home/gian/Documents/aware/projeto-copa/lista-empresas-proprietarias');
		
		for($i=0; $i < count($comp); $i++) {
			$power = $this->build($conn, $comp[$i]);
			
			$title = 'Empresa procurada: '.$comp[$i]."\n Empresas encontradas \n";
			$text = 0;
			
			if($power) {
				$text = '';
				for($j=0; $j < count($power); $j++) {
					$text .= 'Empresa :'.$power[$j]['nome']."\n";
				}
			} else {
				$text = 0;
			}

			file_put_contents('/home/gian/Documents/aware/projeto-copa/found-companies', $title.$text."\n", FILE_APPEND );
		}
		
		return false;
	}
	
	function in_multiarray($elem, $array, $field) {
		if($array) {
			foreach($array as $value) {
		    	if( trim($elem) == $value[trim($field)] ) {
		    		
		    		return true;
		    	}
		    }	
		}

	    return false;
	}
	
}

?>