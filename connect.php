<?php

$host_name="localhost";
$db_name="pharmacy";
$db_user="root";
$password="9787";
$conn=@mysql_connect($host_name, $db_user,$password);
!$conn? die('Couldn\'t connect to DB, Authentication error.'):
$select=@mysql_select_db($db_name);
!$select? die('Couldn\'t connect to DB, DB doesn\'t exist.'): '';


?>