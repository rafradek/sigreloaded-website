<?php
    $connection = mysqli_connect("localhost","sourcemod","potat","sourcemod") or die("Error " . mysqli_error($connection));
    $sql = "select server_name, address, players_red, players_blu, connecting_players, max_players, wave, max_wave, mission, map, UNIX_TIMESTAMP(update_time) as update_time from server_status";
    $result = mysqli_query($connection, $sql) or die("Error in Selecting " . mysqli_error($connection));
    $emparray = array();
    while($row =mysqli_fetch_assoc($result))
    {
        $emparray[] = $row;
    }
    echo json_encode($emparray,JSON_NUMERIC_CHECK);
    mysqli_close($connection);
?>