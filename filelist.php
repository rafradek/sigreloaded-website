<?php

    session_start();
    if (!isset($_SESSION['steamid'])){
        die();
    }
    $ini = parse_ini_file("../config.ini");

    $steam_id_text = $_SESSION['steamid'];
    $steam_id = $steam_id_text & 0xFFFFFFFF;
    $has_limit = false;
    $min = 0;
    $count = 10000;
    if (isset($_GET["min"]) && isset($_GET["count"])){
        $min = $_GET["min"];
        $count = $_GET["count"];
    }

    $strings="extension IN (";
    if (isset($_GET["maps"])){
        $strings.="'bsp','nav','res',";
    }
    if (isset($_GET["missions"])){
        $strings.="'pop',";
    }
    if (isset($_GET["icons"])){
        $strings.="'vtf','vmt',";
    }
    if (isset($_GET["sounds"])){
        $strings.="'wav','mp3',";
    }
    $strings=substr($strings, 0, strlen($strings)-1);
    $strings.=") ";
    if (isset($_GET["other"])){
        $strings.="OR NOT extension IN ('bsp','nav','res','pop','vtf','vmt','wav','mp3')";
    }
    
    try {
        $db = new PDO($ini["db_connection"],$ini["db_username"],$ini["db_password"]); 
    }  
    catch (PDOException $e){
        die();
    } 
    
    //AND ( $strings)
    $sql = "select name, extension as ext, filesize as size from file_ownership WHERE owner_steam_id = $steam_id LIMIT $min, $count";
    $result = $db->query($sql);
    $emparray = array();
    while($row = $result->fetch(PDO::FETCH_ASSOC))
    {
        $emparray[] = $row;
    }
    echo json_encode($emparray);
?>