<?php
    session_start();
    if (!isset($_SESSION['steamid'])){
        die();
    }
    
    $ini = parse_ini_file("../config.ini");

    $target_dir = "tf/";
    
    $steam_id_text = $_SESSION['steamid'];
    $steam_id = $steam_id_text & 0xFFFFFFFF;

    try {
        $db = new PDO($ini["db_connection"],$ini["db_username"],$ini["db_password"]); 
    }  
    catch (PDOException $e){
        die();
    } 
    
    $delete_query = $db->prepare("DELETE FROM file_ownership WHERE owner_steam_id = $steam_id AND name = ? AND extension = ?");
    $get_query = $db->prepare("SELECT COUNT(*) FROM file_ownership WHERE owner_steam_id = $steam_id AND name = ? AND extension = ?");
    $ext_info_query = $db->prepare("SELECT path, prefix FROM extension_info WHERE extension = ?");
    //$sql = "select name, extension as ext, filesize as size from file_ownership WHERE owner_steam_id = $steam_id";
    $get_query->bindParam(1,$_POST["name"],PDO::PARAM_STR);
    $get_query->bindParam(2,$_POST["ext"],PDO::PARAM_STR);
	$get_query->execute();
    
    if($get_query->fetch(PDO::FETCH_NUM)[0] > 0){
        $ext_info_query->bindParam(1,$_POST["ext"],PDO::PARAM_STR);
        $ext_info_query->execute();
        $result = $ext_info_query->fetch(PDO::FETCH_NUM);
        if (!$result)
            $rel_path =  "";
        else
            $rel_path = $result[0];

        $path = $target_dir.$rel_path."/".$_POST["name"].".".$_POST["ext"];
        unlink($path);
        unlink($path.".bz2");
        if (!file_exists($path)){
            $delete_query->bindParam(1,$_POST["name"],PDO::PARAM_STR);
            $delete_query->bindParam(2,$_POST["ext"],PDO::PARAM_STR);
            $delete_query->execute();
            echo json_encode(array(true,"removed $path"));
        }
        else
            echo json_encode(array(false,"cannot remove $path"));
    }
    else
        echo json_encode(array(false,"not uploaded"));
?>