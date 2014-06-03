<?php
include ('connect.php');
include('/home/gian/proprietarios/htdocs/wp-blog-header.php');
// require('/home/gian/proprietarios/htdocs/wp-includes/taxonomy.php');

$connection = new createConnection(); //i created a new object

$p = new buildStructure();

$p->build( $connection->connectToDatabase() );

class buildStructure {
	
	#Criando as categorias pai;
	function build($conn) {
	
		
		$parent_categories = array('Partidos','Empresas', 'UF', 'Cidade', 'Análises de poder', 'Obras');
		$parent_categories = array(
			'Partidos' => array('Ano eleitoral'),
			'Empresas',
			'Redes de poder',
			'UF',
			'Análises de poder',
			
		);
		
		$i = 0;
		while($parent_categories) {
		
			if( !term_exists($parent_categories[$i]) ) {
				wp_insert_term($parent_categories[$i], 'category');
			}
			
			$i++;
		}
	}
	
	function search_by_slug() {
		$args = array(
			'name' => $the_slug,
			'post_type' => 'post',
			'post_status' => 'publish',
			'numberposts' => 1
		);
		
		$post = get_posts();
		
		print_r($post); die;
	}
	
}


?>