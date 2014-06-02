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
	
	function build($conn, $name) {
// 		$n_aux = explode($name, ' ');
// 		if(count($n_aux) > 1 ) {
// 			
// 		}
		
 		$result = mysql_db_query('proprietarios_redes', "select * from assoc where lower(nome) like lower(('%".trim($name)."%'))", $conn);
//  		echo "select * from assoc where lower(nome) like lower(('%".$name."%'))";
		if(!$result) {
 			echo ("Rede de poder não encontrada\n");
 			
			return false;
		}

		$power = [];
 		while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$power[] = $row;
  		}
  		
   		print_r($power);
//   		die('Achou');
  		return $power;
	}
	
	function search_by_id($conn,$id) {
		$result = mysql_db_query('proprietarios_redes', "select * from assoc where id = $id", $conn);
		
		if(!$result) {
			die("Rede de poder não encontrada\n");
		}

		$controlled = [];
		$parent 	= [];
		
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		
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
		}
		
		$total = count($parent)-1;
		
		if($parent[$total]['ehControladoraUltima']) {
			$direct_controlled 	= [];
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
			
 		$total_result = $this->undirected_controlled($conn, $direct_controlled);
 		print_r($total_result);
// 		print_r($direct_controlled);
// 		print_r($parent); 
		die;
	}
	
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
			
			if($undirected_controlled) {
  				print_r($total);
//  				print_r($undirected_controlled);
				$this->undirected_controlled($conn, $undirected_controlled, $total);
			} else {
				
				echo ('acabou');
// 				return 0;
			}

		}
	}
	
	function search_unique($conn, $id) {
		$result = mysql_db_query('proprietarios_redes', "select * from assoc where id = $id", $conn);
		
		return mysql_fetch_assoc($result, MYSQL_ASSOC);
	}
	
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
// 			if($i == 4){
// 				die;
// 			}
			file_put_contents('/home/gian/Documents/aware/projeto-copa/found-companies', $title.$text."\n", FILE_APPEND );
		}
		
		return false;
	}
	
}


?>