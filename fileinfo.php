<?php

    session_start();
    if (!isset($_SESSION['steamid'])){
        die();
    }
    $max_total_upload=1024*1024*1024;
    $max_filesize=1024*1024*100;

    $steam_id_text = $_SESSION['steamid'];
    $steam_id = $steam_id_text & 0xFFFFFFFF;

    $connection = mysqli_connect("localhost","sourcemod","potat","sourcemod") or die("Error " . mysqli_error($connection));
    $sql = "select SUM(filesize) as filesize, COUNT(*) as count from file_ownership WHERE owner_steam_id = $steam_id";
    $result = mysqli_query($connection, $sql) or die("Error in Selecting " . mysqli_error($connection));
    $emparray = array();
    $row =mysqli_fetch_assoc($result);
    $emparray["filesize"] = $row["filesize"];
    $emparray["maxfilesize"] = $max_filesize;
    $emparray["count"] = $row["count"];
    $emparray["maxtotalfilesize"] = $max_total_upload;
    echo json_encode($emparray);
    mysqli_close($connection);
?>