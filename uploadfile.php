<?php
// Requires those apps:
// unzip, 7za, lbzip2

// ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
/// error_reporting(E_ALL);
session_start();
include("vdfparser.php");

//if (isset($_POST['uniquefileid'])) {
//    ob_start();
//}
if(!isset($_SESSION['steamid']) && !isset($_POST['serverkey'])){
    echo "E:Not logged in<br>";
    die();
}

$REMOVE_SIGSEGV_CONDITIONAL = true;

$max_total_upload_all=1024*1024*1024*200;
$max_total_upload=1024*1024*1024;
$max_filesize=1024*1024*100;
$max_upload_rate=1024*1024*8;
$max_file_count=10000;

$GOOGLE_DRIVE_URL = "https://drive.google.com/open?id=";
$GOOGLE_DRIVE_URL_ALT = "https://drive.google.com/file/d/";
$GOOGLE_DRIVE_URL_ALT2 = "https://drive.google.com/u/0/uc?id=";
$GOOGLE_DRIVE_NUMCHAR = strlen($GOOGLE_DRIVE_URL);
$GOOGLE_DRIVE_ALT_NUMCHAR = strlen($GOOGLE_DRIVE_URL_ALT);
$GOOGLE_DRIVE_ALT2_NUMCHAR = strlen($GOOGLE_DRIVE_URL_ALT2);

$GOOGLE_DRIVE_FORMAT = "https://www.googleapis.com/drive/v2/files/%s?key=%s";
$GOOGLE_DRIVE_FORMAT_DOWNLOAD = "&alt=media&source=downloadUrl";
$GOOGLE_DRIVE_API_KEY = "AIzaSyCo1iFFDxaGQuqM3ChHb0q6MRUHGQq1PwQ";

$DROPBOX_URL = "https://www.dropbox.com/sh/";
$DROPBOX_NUMCHAR = strlen($DROPBOX_URL);

$ONEDRIVE_URL = "https://1drv.ms/u/s!";
$ONEDRIVE_NUMCHAR = strlen($ONEDRIVE_URL);
$ONEDRIVE_FORMAT = "https://api.onedrive.com/v1.0/shares/u!%s/root";
$target_dir = "tf";
$target_dir_temp = "temp";
$group_name = "www-data";
try {
    $mysql = new PDO("mysql:dbname=sourcemod;host=localhost","sourcemod","potat"); 
}  
catch (PDOException $e){
    echo "Error ".$e->getMessage();
    die();
} 

$file_exists_query = $mysql->prepare("SELECT COUNT(*) FROM files_registered WHERE filename = ? AND path= ?");
$get_owner_query = $mysql->prepare("SELECT owner_steam_id FROM file_ownership WHERE name = ? AND extension = ?");
$owner_filesize_query = $mysql->prepare("SELECT sum(filesize) FROM file_ownership WHERE owner_steam_id = ?");
$all_filesize_query = $mysql->prepare("SELECT sum(filesize) FROM file_ownership");
$file_count_query = $mysql->prepare("SELECT COUNT(*) FROM file_ownership WHERE owner_steam_id = ?");
$insert_file_query = $mysql->prepare("REPLACE INTO file_ownership (name, extension, owner_steam_id, filesize) VALUES (?, ?, ?, ?)");
$server_exists= $mysql->prepare("SELECT server_key FROM server_status WHERE server_key = ?");
$ext_info_query = $mysql->prepare("SELECT path, prefix, suffix FROM extension_info WHERE extension = ? AND ? like CONCAT(prefix,'%') AND ? like CONCAT('%',suffix) AND path NOT LIKE ('!%') ");
$ext_info_query_archive = $mysql->prepare("SELECT path, prefix, suffix FROM extension_info WHERE extension = ? AND ? like CONCAT(prefix,'%') AND ? like CONCAT('%',suffix)");
$admin_info = $mysql->prepare("SELECT COUNT(*) FROM sm_admins WHERE identity LIKE ?");

$compress_last_file=true;
$steam_id = 0;
if (isset($_SESSION['steamid'])){
    $steam_id_text = $_SESSION['steamid'];
    $steam_id = $steam_id_text & 0xFFFFFFFF;
}
else
{
    $server_exists->bindParam(1,$_POST['serverkey'],PDO::PARAM_STR);
	$server_exists->execute();
    
	if ($server_exists->fetch(PDO::FETCH_NUM)){
        $steam_id = $_POST['steamid'];
    }
    else
    {
        //echo json_encode(array("message" => "Invalid server key", "type" => "error"));
        echo "E:Invalid server key<br>";
        die();
    }
}

if (isset($_FILES['fileUpload']) && is_uploaded_file($_FILES['fileUpload']['tmp_name']))
{
    $dot_pos = strpos($_FILES['fileUpload']['name'], ".");
    $name = substr($_FILES['fileUpload']['name'], 0, $dot_pos);
    $extension = substr(strtolower($_FILES['fileUpload']['name']), $dot_pos+1);

    $filesize = $_FILES['fileUpload']['size'];
    $empty_string = "";
    if(check_move_file($name,$extension,$filesize,$empty_string) && check_file_content($_FILES['fileUpload']['tmp_name'],$name,$extension)) {
        move_file_from($_FILES['fileUpload']['tmp_name'],$name,$extension,$filesize,true,"");
        
        /*mkdir($target_dir_temp.$steam_id);
        chmod($target_dir_temp.$steam_id,0775);
        $target_name=$target_dir_temp.$steam_id."/".basename($_FILES['fileUpload']['name']);
        move_uploaded_file($_FILES['fileUpload']['tmp_name'],$target_name);
        chmod($target_name,0664);*/

        //echo json_encode(array("message" => "Uploaded", "type" => "success", "name" => $name, "extension" => $extension));
        //echo "S:$name.$extension: Uploaded<br>";
    }
}
else if(isset($_POST['urlUpload'])){
    $is_google = false;
    $is_onedrive = false;
    $url = str_replace('"','',$_POST['urlUpload']);
    if (strncmp($url,$GOOGLE_DRIVE_URL,$GOOGLE_DRIVE_NUMCHAR) == 0) {
        $url=substr($url,$GOOGLE_DRIVE_NUMCHAR);
        $url=sprintf($GOOGLE_DRIVE_FORMAT,$url);
        $is_google = true;
    }
    else if (strncmp($url,$GOOGLE_DRIVE_URL_ALT,$GOOGLE_DRIVE_ALT_NUMCHAR) == 0) {
        if (strpos($url,"/",$GOOGLE_DRIVE_ALT_NUMCHAR))
            $url=substr($url,$GOOGLE_DRIVE_ALT_NUMCHAR,strpos($url,"/",$GOOGLE_DRIVE_ALT_NUMCHAR)-$GOOGLE_DRIVE_ALT_NUMCHAR);
        else
            $url=substr($url,$GOOGLE_DRIVE_ALT_NUMCHAR);
        $url=sprintf($GOOGLE_DRIVE_FORMAT,$url,$GOOGLE_DRIVE_API_KEY);
        $is_google = true;
    }
    else if (strncmp($url,$GOOGLE_DRIVE_URL_ALT2,$GOOGLE_DRIVE_ALT2_NUMCHAR) == 0) {
        if (strpos($url,"&",$GOOGLE_DRIVE_ALT2_NUMCHAR))
        $url=substr($url,$GOOGLE_DRIVE_ALT2_NUMCHAR,strpos($url,"/",$GOOGLE_DRIVE_ALT2_NUMCHAR)-$GOOGLE_DRIVE_ALT2_NUMCHAR);
        else
            $url=substr($url,$GOOGLE_DRIVE_ALT2_NUMCHAR);
        $url=sprintf($GOOGLE_DRIVE_FORMAT,$url,$GOOGLE_DRIVE_API_KEY);
        $is_google = true;
    }
    else if (strncmp($url,$DROPBOX_URL,$DROPBOX_NUMCHAR) == 0) {
        //$url=substr($url,0,strlen($url)-1)."1";
    }
    else if (strncmp($url,$ONEDRIVE_URL,$ONEDRIVE_NUMCHAR) == 0) {
        $url=base64_encode($url);
        $url = str_replace("/","_",$url);
        $url = str_replace("+","-",$url);
        $url=sprintf($ONEDRIVE_FORMAT,$url);
        $is_onedrive = true;
    }
    if ($is_google) {
        $header = shell_exec("curl \"".$url."\"");
        $meta = json_decode($header);
        $unique_id = $meta->etag;
        $unique_id_found=true;
        $url .= $GOOGLE_DRIVE_FORMAT_DOWNLOAD;
        $file_name = $meta->originalFilename;
        $filesize = intval($meta->fileSize);
        //echo "S:got $unique_id $file_name $filesize<br>";
    }
    else if($is_onedrive) {
        $header = shell_exec("curl \"".$url."\"");
        $url = $meta->{"@content.downloadUrl"};
        $meta = json_decode($header);
        $unique_id = $meta->eTag;
        $unique_id_found=true;
        $file_name = $meta->name;
        $filesize = intval($meta->size);
        //echo "S:got $url $unique_id $file_name $filesize<br>";
    }
    else {
        // $curl = curl_init($url);
        // curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($curl, CURLOPT_HEADER, true);
        // curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        // $header = curl_exec($curl);
        // $url = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
        // curl_close($curl);
        // echo $url;
        
        $header = shell_exec("curl -L -I -X GET \"". $url."\"");

        // if (strripos($header," 200\r") == 0) {
        //    echo "E:The URL is not accessible<br>";
        //    die();
        // }
        $unique_id_pos=strripos($header,"etag: ") + 6;
        $unique_id_found=false;
        $unique_id="";
        
        if ($unique_id_pos <= 6) {
            $unique_id_pos = strripos($header,"last-modified: ") + 15;
            if ($unique_id_pos > 15)
                $unique_id_found=true;
        }
        else
            $unique_id_found=true;

        
        if ($unique_id_found){
            $unique_id = substr($header,$unique_id_pos,strpos($header,"\r",$unique_id_pos)-$unique_id_pos);
        }

        $file_name_pos=strripos($header,"filename=") + 9;
        $file_name="";
        if ($file_name_pos > 9){
            $file_name = substr($header,$file_name_pos,strpos($header,"\r",$file_name_pos)-$file_name_pos);
            if ($file_name{0} == "\"")
                $file_name=substr($file_name,1,strpos($file_name, "\"", 1)-1);
            
        }
        else{
            $url_pos=strripos($header,"location:") + 9;
            if ($url_pos > 9)
                $url = intval(substr($header,$url_pos,strpos($header,"\r",$url_pos)-$url_pos));

            $file_name=pathinfo($url)['basename'];
            $query_pos= strpos($file_name,"?");
            if ($query_pos > 0)
                $file_name=substr($file_name,0,$query_pos);
        }
        
        $filesize = -1;

        
        $filesize_pos=strripos($header,"content-length:") + 15;
        
        if ($filesize_pos > 15)
            $filesize = intval(substr($header,$filesize_pos,strpos($header,"\r",$filesize_pos)-$filesize_pos));
        else{
            
            //echo json_encode(array("message" => "Cannot determine remote file size", "type" => "error", "name" => $name, "extension" => $extension));
            //echo "E:$name.$extension: Cannot determine remote file size<br>";
            //die();
        }
    }

    if (isset($_POST['uniquefileid']) && $unique_id_found){
        echo "U:$unique_id<br>";
        //echo "S:is file same ".$_POST['uniquefileid']." ".($_POST['uniquefileid'] == $unique_id)."<br>";
        if ($_POST['uniquefileid'] == $unique_id)
            exit();
    }

    $dot_pos = strpos($file_name, ".");
    $name = substr($file_name, 0, $dot_pos);
    $extension = substr(strtolower($file_name), $dot_pos+1);
    $empty_string = "";
    if(check_move_file($name,$extension,$filesize,$empty_string)) {
        //$curl = curl_init($url);
        $tmp_path = "/tmp/tmpdownl".random_int(0,PHP_INT_MAX);
        shell_exec("curl -L \"".$url."\" -o ".$tmp_path." -s --max-filesize ".$max_filesize." --max-redirs 5 --limit-rate ".$max_upload_rate);
        //$tmp_file = fopen($tmp_path,"w");
        /*curl_setopt($curl, CURLOPT_FILE,$tmp_file);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_MAX_RECV_SPEED_LARGE, $max_upload_rate);
    
        curl_setopt($curl, CURLOPT_MAXREDIRS, 5);
        curl_exec($curl);
        echo curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
        curl_close($curl);*/
        //fclose($tmp_file);
        $filesize = filesize($tmp_path);
        if (check_move_file($name,$extension,$filesize,$empty_string) && check_file_content($tmp_path,$name,$extension))
            move_file_from($tmp_path,$name,$extension,$filesize,false,"");
        else
            unlink($tmp_path);
        //echo json_encode(array("message" => "Uploaded", "type" => "success", "name" => $name, "extension" => $extension));
        //echo "S:$name.$extension: Uploaded<br>";
    }
}

//if (isset($_POST['uniquefileid'])) {
//    ob_end_clean();
//}
function dir_is_empty($dir) {
    $handle = opendir($dir);
    while (false !== ($entry = readdir($handle))) {
        if ($entry != "." && $entry != "..") {
        closedir($handle);
        return FALSE;
        }
    }
    closedir($handle);
    return TRUE;
}

function test_done() {
    global $target_dir_temp, $steam_id;
    for ($i = 0; $i < 5; $i++) {
        sleep(1);
        if(!file_exists($target_dir_temp.$steam_id) || dir_is_empty($target_dir_temp.$steam_id)){
            //echo json_encode(array("message" => "File added to the server", "type" => "success"));
            echo "S:File added to the server<br>";
            return;
        }
    }
    //echo json_encode(array("message" => "Cannot add files to the server, overwrites original file?", "type" => "error"));
    echo "E:Cannot add files to the server, overwrites original file<br>";
}
function recursive_load($path, $filesize_total, $current_dir)
{
    if ($handle = opendir($path."/".$current_dir)) {
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != "..") {
                $file_path = $path."/".$current_dir."/".$entry;
                if (is_dir($file_path)){
                    $filesize_total = recursive_load($path, $filesize_total, $current_dir == "" ? $entry : ($current_dir."/".$entry));
                }
                else {
                    $dot_pos = strpos($entry, ".");
                    $name = substr($entry, 0, $dot_pos);
                    $extension = substr(strtolower($entry), $dot_pos+1);
                    
                    $filesize = filesize($file_path);
                    if (check_move_file($name,$extension,$filesize+$filesize_total,$current_dir) && check_file_content($file_path,$name,$extension)){
                        move_file_from($file_path,$name,$extension,$filesize,false,$current_dir);
                    }
                    else{
                        unlink($file_path);
                    }
                    $filesize_total +=$filesize;
                }
            }
        }
        closedir($handle);
        rmdir($path."/".$current_dir);
    }
    return $filesize_total;
}
function move_file_from($from,$name,$extension,$filesize,$moveuploaded, $destination_override) {
    global $target_dir, $steam_id, $insert_file_query,$mysql,$compress_last_file, $group_name;
    if ($extension == "zip") {
        //$zip = new ZipArchive;
        //$zip->open($from);
        $tmp_unzip_path="/tmp/".random_int(0,PHP_INT_MAX);
        mkdir($tmp_unzip_path);
        shell_exec("unzip ".$from." -d ".$tmp_unzip_path);
        //for ($i=0; $i < $zip->count();$i++){
        //    $zip->extractTo($tmp_unzip_path, $zip->getNameIndex($i));
        //}
        //$zip->extractTo($tmp_unzip_path);
        echo "S:$name.$extension: Unzipped<br>";
        recursive_load($tmp_unzip_path,0,"");
        return;
    }
    
    $rel_path = get_path_for_file($name,$extension,$destination_override != "");
    
    if ($destination_override != "")
    {
        $rel_path = $destination_override;
    }

    mkdir($target_dir."/".$rel_path);
    chmod($target_dir."/".$rel_path,0777);
    $target_name=$target_dir."/".$rel_path."/".$name.".".$extension;
    echo "S:Added $name.$extension<br>";
    if ($moveuploaded)
        move_uploaded_file($from,$target_name);
    else
        rename($from, $target_name);
    if ($compress_last_file) {
        shell_exec("lbzip2 $target_name -n 1 -f -k -4");
        $filesize_compress = filesize($target_name.".bz2");

        if ($filesize_compress > $filesize * 0.7)
            unlink($target_name.".bz2");
        else
            $filesize += $filesize_compress;
    }
    chgrp($target_name,$group_name);
    chmod($target_name,0666);

    $insert_file_query->bindParam(1,$name,PDO::PARAM_STR);
    $insert_file_query->bindParam(2,$extension,PDO::PARAM_STR);
    $insert_file_query->bindParam(3,$steam_id,PDO::PARAM_INT);
    $insert_file_query->bindParam(4,$filesize,PDO::PARAM_INT);
	$insert_file_query->execute();
    $mysql->query("REPLACE INTO files_changed_timestamp () VALUES ()");
    $compress_last_file = true;
}

function parse_key_values($file, &$error) {
    $handle = fopen($file, "r");
    $braces = 0;
    $had_braces = false;
    $inquote = false;
    $incharacter = false;
    $keywords = 0;
    $line = 0;
    $current = "";
    $line_keyname = 0;
    $line_keyvalue_start = 0;
    //$global_array = Array();
   // $cur_array = $global_array;
    //$stack_array = Array();
    $last_string = "";
    while (($buffer = fgets($handle, 4096)) !== false) {
        $line++;
        $len = strlen($buffer);
        $inquote = false;
        $incharacter = false;

        for ($i = 0; $i < $len; $i++) {
            $char = $buffer[$i];
            if (!$inquote) {
                if($char == "{"){
                    if ($incharacter && $current[0] != "["){
                        $keywords+=1;
                        //$cur_array[$last_string] = $current;
                        //echo "len ".strlen($current)."|$current|";
                    }
                    $braces++;
                    if (!$had_braces && $keywords > 1) {
                        $error = "Invalid keyvalues structure";
                        return false;
                    }
                    $had_braces = true;
                    if ($keywords % 2 == 0){
                        $error = "Expected key name, got { at line $line";
                        return false;
                    }
                    //$last_array = $cur_array;
                    //$cur_array = Array();
                    //$last_array[$current] = $cur_array;
                    //array_push($stack_array,$cur_array);
                    $keywords = 0;
                    $incharacter = false;
                }
                else if($char == "}"){
                    if ($incharacter && $current[0] != "["){
                        $keywords+=1;
                        //$cur_array[$last_string] = $current;
                        //echo "len ".strlen($current)."|$current|";
                    }
                    $braces--;
                    if ($keywords % 2 == 1){
                        $error = "Expected key value, got } at line $line";
                        return false;
                    }
                    else if ($braces < 0){
                        $error = "Unexpected } at line $line";
                        return false;
                    }
                    $keywords = 0;
                    $incharacter = false;
                    //var_dump($cur_array);
                    //array_pop($stack_array);
                    //$cur_array = $stack_array[count($stack_array)-1];
                }
                else if($char == "\""){
                    $incharacter = true;
                    $inquote = true;
                    if ($current != ""){
                        $last_string = $current;
                        $current = "";
                    }
                    if ($keywords % 2 == 0) {
                        $line_keyname = $line;
                    }
                    else {
                        $line_keyvalue_start = $line;
                    }
                }
                else if($char == " " || $char == "\t" || $char == "\r" || $char == "\n"){
                    if ($incharacter && $current[0] != "["){
                        $keywords+=1;
                        if ($keywords % 2 == 0 && $line_keyvalue_start != $line_keyname)
                            echo "Key value and key name start at different line, is this correct? ($last_string $current) at $line <br>";
                        if ($keywords % 2 == 0 && ($last_string == "#base" || $last_string == "#include"))
                            $keywords -=2;
                        //$cur_array[$last_string] = $current;
                        //echo "len ".strlen($current)."|$current|";
                    }
                    $incharacter = false;
                }
                else if($i > 0 && $char == "/" && $buffer[$i-1] == "/"){
                    if ($incharacter && $current != "/"){
                        if ($current[0] == "[") {
                            $error = "Add whitespace character before comment block at line $line";
                            return false;
                        }
                        else {
                            $current = substr($current,0,strlen($current)-1);
                            $keywords+=1;
                            if ($keywords % 2 == 0 && $line_keyvalue_start != $line_keyname)
                                echo "Key value and key name start at different line, is this correct? ($last_string $current) at $line <br>";
                            if ($keywords % 2 == 0 && ($last_string == "#base" || $last_string == "#include"))
                                $keywords -=2;
                        }
                        //$cur_array[$last_string] = $current;
                        //echo "len ".strlen($current)."|$current|";
                    }
                    $incharacter = false;
                    $current = "";
                    break;
                }
                else
                {
                    if (!$incharacter && $current != ""){
                        $last_string = $current;
                        $current = "";
                    }
                    $incharacter = true;
                    if ($current == "") {
                        if ($keywords % 2 == 0) {
                            $line_keyname = $line;
                        }
                        else {
                            $line_keyvalue_start = $line;
                        }
                    }
                    $current .= $char;
                }
            }
            else
            {
                if($char == "\""){
                    $inquote = false;
                    $incharacter = false;
                    $keywords+=1;
                    if ($keywords % 2 == 0 && $line_keyvalue_start != $line_keyname)
                        echo "Key value and key name start at different line, is this correct? ($last_string $current) at $line <br>";
                    if ($keywords % 2 == 0 && ($last_string == "#base" || $last_string == "#include"))
                        $keywords -=2;
                    //$cur_array[$last_string] = $current;
                    //echo "len ".strlen($current)."|$current|";
                }
                else
                {
                    if (!$incharacter && $current != ""){
                        $last_string = $current;
                        $current = "";
                    }
                    $incharacter = true;
                    
                    $current .= $char;
                }
            }
            
        }
        if ($incharacter && $current[0] != "[") {
            $keywords+=1;
            //$cur_array[$last_string] = $current;
            //echo "len ".strlen($current)."|$current|";
        }
    }
    fclose($handle);
    if ($braces != 0){
        $error = "Extraneous number of braces";
        return false;
    }
    if (!$had_braces) {
        $error = "Invalid keyvalues structure";
        return false;
    }
    return true;
}

function check_file_content($file, $name, $extension){
    global $compress_last_file, $REMOVE_SIGSEGV_CONDITIONAL;
    $compress_last_file = true;
    $good = true;
    if ($extension == "bsp") {
        $handle = fopen($file,"r");
        $good = fread($handle,8) == "VBSP\x14\0\0\0";
        fseek($handle, 1036);
        $compress_last_file = fread($handle,4)!="LZMA";
        fclose($handle);
    }
    else if ($extension == "vtf") {
        $handle = fopen($file,"r");
        $good = fread($handle,4) == "VTF\0";
        $compress_last_file = filesize($file) > 6000;
        fclose($handle);
    }
    else if ($extension == "nav") {
        $handle = fopen($file,"r");
        $good = fread($handle,8) == "\xCE\xFA\xED\xFE\x10\0\0\0";
        $compress_last_file = false;
        fclose($handle);
    }
    else if ($extension == "wav") {
        $handle = fopen($file,"r");
        $good = fread($handle,4) == "RIFF";
        fclose($handle);
    }
    else if ($extension == "mp3"){
        $compress_last_file = false;
    }
    else if($extension == "pop" || $extension == "txt" || $extension == "res" || $extension == "vmt") {
        $error = "";
        $keyvalues = parse_key_values($file, $error);
        $compress_last_file = $extension == "txt";
        if (!$keyvalues)
            echo "E:$name.$extension: $error<br>";
        else if (strlen($error) > 0)
            echo "$name.$extension: $error<br>";

        //Remove sigsegv conditional since it can break some plugin reading mission stuff
        if ($REMOVE_SIGSEGV_CONDITIONAL && $extension == "pop") {
            $keyvalues_content = file_get_contents($file);
            $keyvalues_content = str_ireplace('[$SIGSEGV]', ' ',$keyvalues_content);
            file_put_contents($file, $keyvalues_content);
        }

        return $keyvalues;
        //$kv = VDFParse($file);
        //if (is_string($kv))
        //    echo "E:$name.$extension: $kv<br>";
        //else
        //    var_dump($kv);
        //$good = ($extension == "pop" && isset($kv["WaveSchedule"])) || ($extension == "res" && isset($kv["Resources"])) || ($extension == "txt" && isset($kv["upgrades"]));
    }
    if (!$good){
        echo "E:$name.$extension: Parse error<br>";
        return false;
    }
    return true;
}

function check_move_file($name,$extension,$filesize, &$destination_override){
    global $steam_id, $target_dir_temp, $owner_filesize_query,$all_filesize_query, $max_total_upload, $max_total_upload_all, $max_filesize, $max_file_count;

    $relpath = get_path_for_file($name,$extension,$destination_override != "");
    $owner = get_file_owner($name, $extension);

    if ($relpath != "" && $destination_override != "" ) {
        if (substr($relpath,0,1) == "!") {
            $relpath = substr($relpath,1);
        }
        if (strncmp($relpath,$destination_override,strlen($relpath)) != 0) {
            $destination_override = $relpath;
            //echo "E:$name.$extension: This file must be placed in $relpath<br>";
            //return false;
        }
        $relpath = $destination_override;
    }
    else if ($relpath == "!") {
        echo "E:$name.$extension: This file must be uploaded in a zip archive<br>";
        return false;
    }

    if ($extension != "zip" && $owner != $steam_id && !is_uploader_admin(steam_id) && ($owner != 0 || file_exists_in_game($name.".".$extension,$relpath))){
        //echo json_encode(array("message" => "File owned by another user", "type" => "error", "name" => $name, "extension" => $extension));
        echo "E:$name.$extension: File owned by another user <br>";
    }
    else if ($extension != "pop" && isset($_POST['uniquefileid'])) {
        echo "E:$name.$extension: Auto updating is only supported for pop files<br>";
    }
    else if (get_owner_filecount() >= $max_file_count){
        // echo json_encode(array("message" => , "type" => "error", "name" => $name, "extension" => $extension));
         echo "E:$name.$extension: Exceeded the limit of $max_file_count files<br>";
    }
    else if ($filesize != -1 && $filesize > $max_filesize){
        // echo json_encode(array("message" => , "type" => "error", "name" => $name, "extension" => $extension));
         echo "E:$name.$extension: File exceeds the limit of ".($max_filesize/1024/1024)." MB <br>";
    }
    else if ($filesize != -1 && $filesize+ get_owner_filesize()> $max_total_upload){
       // echo json_encode(array("message" => , "type" => "error", "name" => $name, "extension" => $extension));
        echo "E:$name.$extension: File exceeds the user limit of ".($max_total_upload/1024/1024)." max MB stored<br>";
    }
    else if ($filesize != -1 && $filesize+ get_all_filesize()> $max_total_upload_all){
        //echo json_encode(array("message" => "File exceeds the global limit of ".$max_total_upload_all." max bytes stored", "type" => "error", "name" => $name, "extension" => $extension));
        echo "E:$name.$extension: File exceeds the global limit of ".($max_total_upload_all/1024/1024)." max MB stored<br>";
    }
    else if ($relpath == ""){
        //echo json_encode(array("message" => "Unrecognized file type", "type" => "error", "name" => $name, "extension" => $extension));
        echo "E:$name.$extension: Unrecognized file type<br>";
    }
    else
    {
        return true;
    }
    return false;
}


function get_owner_filesize()
{
    global $steam_id, $owner_filesize_query;
    $owner_filesize_query->bindParam(1,$steam_id,PDO::PARAM_INT);
    
    $owner_filesize_query->execute();
    
    $result = $owner_filesize_query->fetch(PDO::FETCH_NUM);
    return (int)$result[0];
}

function get_owner_filecount()
{
    global $steam_id, $file_count_query;
    $file_count_query->bindParam(1,$steam_id,PDO::PARAM_INT);
    
    $file_count_query->execute();
    
    $result = $file_count_query->fetch(PDO::FETCH_NUM);
    return (int)$result[0];
}
function steamid32_to_steamid($steamid)
{
    return ($steamid & 1).":".($steamid >> 1);
}
function is_uploader_admin()
{
    global $steam_id, $admin_info;
    $like ="%".steamid32_to_steamid($steam_id);
    $admin_info->bindParam(1,$like,PDO::PARAM_STR);
    $admin_info->execute();
    $result = $admin_info->fetch(PDO::FETCH_NUM);
    //var_dump($result);
    return (int)($result[0]);
    /*if (!$result){
        echo "S:Not Admin ";
        return 0;
    }
    else {

        echo "S:Admin? "..($result[0] == null);
        return $result[0];
    }*/

    /*if (!$result[0] && !(int)$result[0]) {
        echo "S:Not Admin";
        return false;
    }
    echo "S:Admin ..!$result && $result[0] > 0";
    return !$result && $result[0] > 0;*/
}

function get_all_filesize()
{
    global $all_filesize_query;
    $all_filesize_query->execute();
    $result = $all_filesize_query->fetch(PDO::FETCH_NUM);
    return (int)$result[0];
}

function get_path_for_file($name, $extension, $destination_override) {
    global $ext_info_query, $ext_info_query_archive;
    $ext_info_query->bindParam(1,$extension,PDO::PARAM_STR);
    $ext_info_query->bindParam(2,$name,PDO::PARAM_STR);
    $ext_info_query->bindParam(3,$name,PDO::PARAM_STR);
    $ext_info_query->execute();
    $result = $ext_info_query->fetch(PDO::FETCH_NUM);
        //if (strncmp($name,$result[1],strlen($result[1])) == 0 && $result[2] == substr($name,-strlen($result[2]))
    if ($result)
        return $result[0];
    else
        if ($destination_override) {
            $ext_info_query_archive->bindParam(1,$extension,PDO::PARAM_STR);
            $ext_info_query_archive->bindParam(2,$name,PDO::PARAM_STR);
            $ext_info_query_archive->bindParam(3,$name,PDO::PARAM_STR);
            $ext_info_query_archive->execute();
            $result = $ext_info_query_archive->fetch(PDO::FETCH_NUM);
            
            if ($result){
                return $result[0];
            }
            else {
                return "";
            }
        }
        else
            return "";
}
function file_exists_in_game($name, $path)
{
    global $file_exists_query;
    $file_exists_query->bindParam(1,$name,PDO::PARAM_STR);
    $file_exists_query->bindParam(2,$path,PDO::PARAM_STR);
	$file_exists_query->execute();
	return $file_exists_query->fetch(PDO::FETCH_NUM)[0] > 0;
}
function file_exists_in_database($name, $extension)
{
    global $get_owner_query;
    $get_owner_query->bindParam(1,$name,PDO::PARAM_STR);
    $get_owner_query->bindParam(2,$extension,PDO::PARAM_STR);
	$get_owner_query->execute();
    
	return $get_owner_query->fetch(PDO::FETCH_NUM);
}
function get_file_owner($name, $extension)
{
    global $get_owner_query;
    $get_owner_query->bindParam(1,$name,PDO::PARAM_STR);
    $get_owner_query->bindParam(2,$extension,PDO::PARAM_STR);
	$get_owner_query->execute();
    
    $result = $get_owner_query->fetch(PDO::FETCH_NUM);
    if (!$result)
        return 0;
    else {
        return $result[0];
    }
}
?>