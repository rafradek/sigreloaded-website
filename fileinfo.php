<?php

    session_start();
    if (!isset($_SESSION['steamid'])){
        die();
    }
    $ini = parse_ini_file("../config.ini");

    $max_total_upload=1024*1024*1024;
    $max_filesize=1024*1024*100;

    $steam_id_text = $_SESSION['steamid'];
    $steam_id = $steam_id_text & 0xFFFFFFFF;

    
    try {
        $db = new PDO($ini["db_connection"],$ini["db_username"],$ini["db_password"]); 
    }  
    catch (PDOException $e){
        die();
    } 

    $sql = "select SUM(filesize) as filesize, COUNT(*) as count from file_ownership WHERE owner_steam_id = $steam_id";
    $result = $db->query($sql);
    $emparray = array();
    $row = $result->fetch(PDO::FETCH_ASSOC);
    $emparray["filesize"] = $row["filesize"];
    $emparray["maxfilesize"] = $max_filesize;
    $emparray["count"] = $row["count"];
    $emparray["maxtotalfilesize"] = $max_total_upload;
    echo json_encode($emparray);
?>