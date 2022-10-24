<?php
  session_start();
  
  include_once("connectToMySQL.php");
  $loggedIn = '';
  
  // If the session variable and cookie variable are not set
  if (!isset($_SESSION['idx'])) {
    if (!isset($_COOKIE['idCookie'])) {
      $loggedIn = 'n';
    }
  }
  
  // If session ID is set for logged in user without cookies or remember me feature set
  if (isset($_SESSION['idx'])) {
    $decryptedID = base64_decode($_SESSION['idx']);
    $id_array = explode("p3h9xfn8sq03hs2234", $decryptedID);
    $logOptions_id = $id_array[1];
  	$loggedIn = 'y';
  } else if (isset($_COOKIE['idCookie'])) { // If id cookie is set, but no session ID is set yet
    $decryptedID = base64_decode($_COOKIE['idCookie']);
    $id_array = explode("nm2c0c4y3dn3727553", $decryptedID);
    $userID = $id_array[1];
    $userPass = $_COOKIE['passCookie'];
    $username = ''; //this was added because when first registering and then adding a profile photp, username would return nothing and result in an error
    // Get their user first name to set into session var
    
    $sql_uname = mysqli_query($link, "SELECT username FROM members WHERE id='$userID' AND password='$userPass' LIMIT 1");
    $numRows = mysqli_num_rows($sql_uname);
    
    if ($numRows == 0) {
      //Kill cookies, send back to homepage
      setcookie("idCookie", '', time()-42000, '/');
      setcookie("passCookie", '', time()-42000, '/');
    }
    
    $username;
    while($row = mysqli_fetch_array($sql_uname)){
      $username = $row["username"];
    }
    
    $_SESSION['id'] = $userID;
	$_SESSION['idx'] = base64_encode("g4p3h9xfn8sq03hs2234$userID");
    $_SESSION['username'] = $username;
	$_SESSION['userpass'] = $userPass;
	
	$logOptions_id = $userID;
	
	mysqli_query($link, "UPDATE members SET last_log_date=now() WHERE id='$logOptions_id'");
	//$sql_pm_check = mysqli_query($link, "SELECT id FROM private_messages WHERE to_id='$logOptions_id' AND opened='0'");
    $loggedIn = 'y';
  } 
?>