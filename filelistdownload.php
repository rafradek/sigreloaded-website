<?php
$start_time = microtime(true); 
header("Content-Type: text/plain");
if(!isset($_GET['serverkey'])){
    echo "Not logged in";
    die();
}
$LOOK_DIR = "tf";

$newest_date = 0;
recursive_load($LOOK_DIR);
// $server_exists->bindParam(1,$_GET['serverkey'],PDO::PARAM_STR);
// $server_exists->execute();
// if ($server_exists->fetch(PDO::FETCH_NUM)){
    
//     recursive_load($LOOK_DIR);
// }
// else
// {
//     echo "Invalid server key";
//     die();
// }
echo $newest_date;
function recursive_load($path)
{
    global $LOOK_DIR, $newest_date;
    if ($handle = opendir($path)) {
        //echo substr($path,strlen($LOOK_DIR))."\n";
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != "..") {
                $file_path = $path."/".$entry;
                if (is_dir($file_path)){
                    recursive_load($file_path);
                }
                else {
                    //$path_info=pathinfo($entry);
                    //if ($path_info['extension'] != "bz2") {
                        $filedate = filectime($file_path);
                        if ($filedate > $newest_date) {
                            $newest_date = $filedate;
                            //echo "$entry|$filedate\n";
                        }
                    //}
                }
            }
        }
        closedir($handle);
    }
}
?>