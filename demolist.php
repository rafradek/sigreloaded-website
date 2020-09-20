<?php
header("Content-Type: text/plain");

$connection = mysqli_connect("localhost","sourcemod","potat","sourcemod") or die("Error " . mysqli_error($connection));
$sql = "select name, address from server_location";
$result = mysqli_query($connection, $sql) or die("Error in Selecting " . mysqli_error($connection));
$array = array();
while($row =mysqli_fetch_assoc($result))
{
    $serverarray = array();
    $serverarray["server_name"] = $row["name"];
    $serverarray["address"] = $row["address"];
    $returnval = shell_exec("curl -m 3 -L \"http://". $row["address"]."/demolistlocal.php\"");
    $serverarray["demos"] = json_decode($returnval);
    $array[] = $serverarray;
}
echo json_encode($array,JSON_NUMERIC_CHECK);
mysqli_close($connection);

$LOOK_DIR = "demos";

?>