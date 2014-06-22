<?php
include ('connect.php');
include('/home/gian/proprietarios/htdocs/wp-blog-header.php');
// require('/home/gian/proprietarios/htdocs/wp-includes/taxonomy.php');

$connection = new createConnection(); //i created a new object

$p = new buildStructure();

if( $argv[1] == 'groups' ) {
	$p->insert_from_empresas( $connection->connectToDatabase() );
} elseif( $argv[1] = 'obras' ) {
	$p->complete_obras( $connection->connectToDatabase() );	
} else {
	$p->create_posts_network_power( $connection->connectToDatabase() );
}

class buildStructure {
	
	function create_posts_network_power($conn) {
		$keys = $this->search_keys($conn);
		
		while($row = mysql_fetch_assoc($keys, MYSQL_ASSOC)){
			if( term_exists($row['name']) ) {
				continue;
			}
			
			$category 	= wp_insert_term($row['name'], 'redes-de-poder', array('description' => 'cnpj: '.$row['registry']));
			$companies	= $this->search_by_key($conn, $row['reference_id']);
			
			while($company = mysql_fetch_assoc($companies, MYSQL_ASSOC)) {
				$post = array (
					'post_content' 	=> '',
					'post_title'	=> $company['name'],
					'post_type'		=> 'empresas',
					'post_status'	=> 'publish',
					'tax_input'		=> array('redes-de-poder') 
				);
				
				$post_id = wp_insert_post($post);
				
				if($company['is_controller']){
					$hierarchy = 'controladora';	
				} elseif ($company['is_directed_controlled']) {
					$hierarchy = 'diretamente_controlada';
				} elseif ($company['is_undirected_controlled']) {
					$hierarchy = 'indiretamente_controlada';
				}
				
				wp_set_post_terms( $post_id, array($category['term_id']), 'redes-de-poder', true );
				
				add_post_meta($post_id, 'cnpj', $company['registry']);				 
				add_post_meta($post_id, $hierarchy, 1);
			}
		}
	}
	
	function search_keys($conn) {
		return mysql_db_query('vaimudar', 'select * from custom_networks where is_key = 1', $conn);
	}
	
	function search_by_key($conn, $key_id) {
		return mysql_db_query('vaimudar', 'select * from custom_networks where key_id = '.$key_id.' and is_key = 0', $conn);
	}
	
	function search_by_empresa($conn, $group_id) {
		return mysql_db_query('vaimudar', 'select * from empresa where grupo_id = '.$group_id, $conn);
	}
	
	function insert_from_empresas ($conn) {
		$groups = mysql_db_query('vaimudar', 'select * from grupo_economico', $conn);
		
		while($row = mysql_fetch_assoc($groups, MYSQL_ASSOC)) {
			
			if( term_exists($row['name']) ) {
				continue;
			}
			$category = wp_insert_term( ucwords( str_replace( array('_','-'), ' ', $row['name'] ) ), 'redes-de-poder' );
			
			$empresa = $this->search_by_empresa($conn,$row['id']);
			
			while($r = mysql_fetch_assoc($empresa, MYSQL_ASSOC)) {
				$post = array (
					'post_content' 	=> '',
					'post_title'	=> ucwords( str_replace( array('_','-'), ' ', $r['name'] ) ),
					'post_type'		=> 'empresas',
					'post_status'	=> 'publish',
					'tax_input'		=> array('redes-de-poder') 
				);
				
				$post_id = wp_insert_post($post);
				
				wp_set_post_terms( $post_id, array($category['term_id']), 'redes-de-poder', true );
				add_post_meta($post_id, 'cnpj', $r['cnpj']);
			}
		}
	}
	
	function dedup_terms() {
		
	}
	
	function complete_obras($conn) {
		$result = mysql_db_query('vaimudar', "select * from obras_cgu where slug_wp is not null and slug_wp <> '' ");

		while( $row = mysql_fetch_assoc($result, MYSQL_ASSOC) ) {
			$args = array(
			  'name' 		=> $row['slug_wp'],
			  'post_type' 	=> 'obras',
			);
			
			$post = get_posts($args);
			
			$args = array(
				'valor_previsto_transp' 	=> $row['valor_previsto_transp'],
				'valor_executado_transp' 	=> $row['valor_executado_transp'],
				'valor_executado_transp' 	=> $row['valor_executado_transp'],
				'valor_financiado_transp' 	=> $row['valor_financiado_transp'],
				'progresso_transp' 			=> $row['progresso_transp'],
				'atualizacao_transp' 		=> $row['atualizado_em']
			);
			
			add_post_meta($post[0]->ID, 'valor_previsto_transp', $row['valor_previsto_transp']);
			add_post_meta($post[0]->ID, 'valor_executado_transp', $row['valor_executado_transp']);
			add_post_meta($post[0]->ID, 'valor_financiado_transp', $row['valor_financiado_transp']);
			add_post_meta($post[0]->ID, 'financiador_transp', $row['financiador_transp']);
			add_post_meta($post[0]->ID, 'progresso_transp', $row['progresso_transp']);
			add_post_meta($post[0]->ID, 'atualizacao_transp', $row['atualizado_em']);
		}
	}
	
}
?>