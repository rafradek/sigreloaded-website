<?php
header("Content-Type: text/plain");
$LOOK_DIR = "demos";
$array = array();
if ($handle = opendir($LOOK_DIR)) {
    while (false !== ($entry = readdir($handle))) {
        if ($entry != "." && $entry != "..") {
            $file_path = $LOOK_DIR."/".$entry;
            if (is_dir($file_path)){

            }
            else {
                $fileobj = array();
                $fileobj["name"] = $entry;
                $fileobj["date"] = filectime($file_path);
                $fileobj["size"] = filesize($file_path);
                $array[] = $fileobj;
            }
        }
    }
}
closedir($handle);
echo json_encode($array);
?>