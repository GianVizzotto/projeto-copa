<?php

class createConnection //create a class for make connection
{
    var $host		= "localhost";
    var $username	= "root";    // specify the sever details for mysql
    var $password	= "";
    var $myconn;

    function connectToDatabase() // create a function for connect database
    {

        $conn= mysql_connect($this->host,$this->username,$this->password);

        if(!$conn)// testing the connection
        {
            die ("Cannot connect to the database");
        }

        else
        {

            $this->myconn = $conn;
            
            mysql_query("SET NAMES utf8", $this->myconn);
        }

        return $this->myconn;

    }

    function selectDatabase($database) // selecting the database.
    {
		if(!$database) {
			die("You must specify the database name");
		}
		
        mysql_select_db($this->database);  //use php inbuild functions for select database

        if(mysql_error()) // if error occured display the error message
        {

            echo "Cannot find the database ".$this->database;

        }
         echo "Database selected..";       
    }

    function closeConnection() // close the connection
    {
        mysql_close($this->myconn);

        echo "Connection closed";
    }

}
	
?>