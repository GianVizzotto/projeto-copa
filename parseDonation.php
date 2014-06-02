<?php

include ('connect.php');

$connection = new createConnection(); //i created a new object

$p = new parseDonation();

$p->parseParties($connection->connectToDatabase());

class parseDonation
{
		
	function parseParties($conn) {
		$result 	= mysql_db_query('doacoes', 'select distinct(partido) from doacoes', $conn);
		
		$parties = [];
		$row = mysql_fetch_array($result, MYSQL_NUM);
		print_r($row); die;
		
		while($row = mysql_fetch_array($result, MYSQL_NUM)) {
			$parties[] = $row[0];
		}
		
		print_r($parties);
	}

}

?>