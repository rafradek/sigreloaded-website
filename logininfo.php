<?php

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
require ('steamauth/steamauth.php');
$out = array();

$out['sessteamid'] = $_SESSION['steamid'];

if(isset($_SESSION['steamid'])) {
    include ('steamauth/userInfo.php');
    $out['steamid'] = $steamprofile['steamid'];
    $out['personaname'] = $steamprofile['personaname'];
    $out['avatar'] = $steamprofile['avatar'];
}
echo json_encode($out);
// if(!isset($_SESSION['steamid'])) {

//     loginbutton(); //login button

// }  else {

//     //Protected content
//     echo "Welcome back " . $steamprofile['personaname'] . "</br>";
//     //echo "here is your avatar: </br>" . '<img src="'.$steamprofile['avatarfull'].'" title="" alt="" /><br>'; // Display their avatar!

//     logoutbutton();
// }    
?> 