<?php

    session_start();
    if (!isset($_SESSION['steamid'])){
        die();
    }

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
    $connection = mysqli_connect("localhost","sourcemod","potat","sourcemod") or die("Error " . mysqli_error($connection));
    //AND ( $strings)
    $sql = "select name, extension as ext, filesize as size from file_ownership WHERE owner_steam_id = $steam_id LIMIT $min, $count";
    $result = mysqli_query($connection, $sql) or die("Error in Selecting " . mysqli_error($connection));
    $emparray = array();
    while($row =mysqli_fetch_assoc($result))
    {
        $emparray[] = $row;
    }
    echo json_encode($emparray);
    mysqli_close($connection);
?>