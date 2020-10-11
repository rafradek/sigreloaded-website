<?php
header("Content-Type: text/plain");

$ini = parse_ini_file("../config.ini");

try {
    $db = new PDO($ini["db_connection"],$ini["db_username"],$ini["db_password"]); 
}  
catch (PDOException $e){
    die();
} 

$sql = "select name, address from server_location";
$result = $db->query($sql);
$array = array();
while($row = $result->fetch(PDO::FETCH_ASSOC))
{
    $serverarray = array();
    $serverarray["server_name"] = $row["name"];
    $serverarray["address"] = $row["address"];
    $returnval = shell_exec("curl -m 3 -L \"http://". $row["address"]."/demolistlocal.php\"");
    $serverarray["demos"] = json_decode($returnval);
    $array[] = $serverarray;
}
echo json_encode($array,JSON_NUMERIC_CHECK);

$LOOK_DIR = "demos";

?>