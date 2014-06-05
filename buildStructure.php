<?php
include ('connect.php');
include('/home/gian/proprietarios/htdocs/wp-blog-header.php');
// require('/home/gian/proprietarios/htdocs/wp-includes/taxonomy.php');

$connection = new createConnection(); //i created a new object

$p = new buildStructure();

$p->create_posts_network_power( $connection->connectToDatabase() );

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
	
	/*function search_by_slug() {
		$args = array(
			'name' 			=> $the_slug,
			'post_type' 	=> 'post',
			'post_status' 	=> 'publish',
			'numberposts' 	=> 1
		);
		
		$post = get_posts();
		
		print_r($post); die;
	}*/
	
}
?>