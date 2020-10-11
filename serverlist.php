<?php
    $ini = parse_ini_file("../config.ini");

    try {
        $db = new PDO($ini["db_connection"],$ini["db_username"],$ini["db_password"]); 
    }  
    catch (PDOException $e){
        die();
    } 
    $sql = "select server_name, address, players_red, players_blu, connecting_players, max_players, wave, max_wave, mission, map, UNIX_TIMESTAMP(update_time) as update_time from server_status";
    $result = $db->query($sql);
    $emparray = array();
    while($row = $result->fetch(PDO::FETCH_ASSOC))
    {
        $emparray[] = $row;
    }
    echo json_encode($emparray,JSON_NUMERIC_CHECK);
?>