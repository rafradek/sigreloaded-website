<?php
    $ini = parse_ini_file("../config.ini");
    try {
        $db = new PDO($ini["db_connection"],$ini["db_username"],$ini["db_password"]); 
    }  
    catch (PDOException $e){
        die();
    } 
    $sql = "select command, help from commands";
    $result = $db->query($sql);
    $emparray = array();
    while($row = $result->fetch(PDO::FETCH_ASSOC))
    {
        $emparray[] = $row;
    }
    echo json_encode($emparray);
?>