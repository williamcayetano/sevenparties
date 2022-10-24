<?php
  function getStatusCodeMessage($status) {
    // these could be stored in a .ini file and loaded
    // via parse_ini_file()... however, this will suffice
    // for an example
    $codes = Array(
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => '(Unused)',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported'
    );

    return (isset($codes[$status])) ? $codes[$status] : '';
  }

  // Helper method to send a HTTP response code/message
  function sendResponse($status = 200, $body = '', $content_type = 'text/html') {
    $status_header = 'HTTP/1.1 ' . $status . ' ' . getStatusCodeMessage($status);
    header($status_header);
    header('Content-type: ' . $content_type);
    echo $body;
  }
  
  function clean($str) {
    global $link;
    $str = preg_replace("#['`]#", "&#039;", $str);
    $str = stripslashes($str);
    $str = htmlspecialchars($str);
    return mysqli_real_escape_string($link, $str);
  }
  
  function htmlkarakter($string) { 
   $string = str_replace(array("&lt;", "&gt;", '&amp;', '&#039;', '&quot;','&lt;', '&gt;'), array("<", ">",'&','\'','"','<','>'), htmlspecialchars_decode($string, ENT_NOQUOTES)); 
   return $string; 
  }
  
  function checkUserPic($userID) {
    $checkPic = "members/$userID/image01.jpg";
	$defaultPic = "members/0/image01.png";
	if (file_exists($checkPic)) {
	  $userPic = $checkPic;
	} else {
	  $userPic = $defaultPic;
	}
	return $userPic;
  }
  
  function getUsername($userID) {
    global $link;
   	$sqlGetUserName = mysqli_query($link, "SELECT username, first_name, last_name FROM members WHERE id='$userID' LIMIT 1");
    while ($row = mysqli_fetch_array($sqlGetUserName)) {
      $username = $row['username'];
      $firstName = htmlkarakter($row['first_name']);
      $lastName = htmlkarakter($row['last_name']);
    }
    
      //check if first, last name replaced
    if ($firstName != "" && $lastName != "") {
      $completeName = $firstName . ' ' . $lastName;
    } else {
      $completeName = $username;
    }
    return $completeName;
  }
  
  function getUser($userID, $rsvpCheck) {
    global $link;
    global $result;
    global $i;
    if ($rsvpCheck) {
      $sql = mysqli_query($link, "SELECT * FROM members WHERE id='$userID' AND rsvp_show='y' AND active='y'") or die ("Sorry there has been an error!");
      $existCount = mysqli_num_rows($sql);
      if ($existCount == 0) { sendResponse(200, "Hmmm, this person doesn't seem to exist."); exit; }
    } else {
      $sql = mysqli_query($link, "SELECT * FROM members WHERE id='$userID' AND active='y' LIMIT 1");
      $existCount = mysqli_num_rows($sql);
      if ($existCount == 0) { sendResponse(200, "Hmmm, this person doesn't seem to exist."); exit; }
    }
    $userPic = checkUserPic($userID);
    $username = getUsername($userID);
    while ($row = mysqli_fetch_array($sql)) {
      $result[$i] = array(
        'id' => $row["id"],
        'username' => $username,
        'city' => $row["city"],
        'state' => $row["state"],
        'country' => $row["country"],
        'birthday' => $row["birthday"],
        'website' => $row["website"],
        'bio' => htmlkarakter($row["bio_body"]),
        'trackDesired' => $row["track_desired"],
        'rsvpShow' => $row["rsvp_show"], 
        'userPic' => $userPic,
      );
      $i++;
    }//end while
    return $result;
  }
  
  function convertDatetime($str) {
      list($date, $time) = explode(' ', $str);
      list($year, $month, $day) = explode('-', $date);
      list($hour, $minute, $second) = explode(':', $time);
      $timeStamp = mktime($hour, $minute, $second, $month, $day, $year);
      return $timeStamp;
   }
    
   function makeAgo($timeStamp) {
     $difference = time() - $timeStamp;
     $periods = array("sec", "min", "hr", "day", "week", "month", "year", "decade");
     $lengths = array("60","60","24","7","4.35","12","10");
   	 for($j = 0; $difference >= $lengths[$j] && $j < count($lengths)-1; $j++)
   		$difference /= $lengths[$j];
   		$difference = round($difference);
   	 if($difference != 1) $periods[$j].= "s";
   		$text = "$difference $periods[$j] ago";
   		return $text;
   }
   
   function getEvents($eventType, $eventID) {
    global $link;
    global $result;
    global $i;
    $todaysDateTime = time();
    //I had to assign $eventType to this variable because I was getting strange comparisons of database date/time and today's date time
    $eventTyppe;
    $sqlEvent = mysqli_query($link, "SELECT * FROM locations WHERE id='$eventID' AND active='y' LIMIT 1") or die ("Sorry there has been an error!");
    while ($row = mysqli_fetch_array($sqlEvent)) {
      //get username
      $userID = $row['user_id'];
      $username = getUsername($userID);
           
      $unixPostedStamp = strtotime($row['post_date']);
      //compare times to check if event already happened; databaseDateTime returns date("Y-m-d H:i:s");
      $databaseDateTime = strtotime($row['time']);
      if ($todaysDateTime < $databaseDateTime ) { $eventTyppe = $eventType; } else { $eventTyppe = 'past'; }
      
      //get # of rsvps
      $sqlGetRsvpNumber = mysqli_query($link, "SELECT id FROM location_rsvps WHERE location_id='$eventID' AND active='y'");
      $sqlNumRsvps = mysqli_num_rows($sqlGetRsvpNumber);
      
      //get comment number and photo number, for past
      $sqlGetCommentNumber = mysqli_query($link, "SELECT id FROM location_comments WHERE location_id='$eventID'");
      $sqlNumComments = mysqli_num_rows($sqlGetCommentNumber);
      $sqlGetPhotoNumber = mysqli_query($link, "SELECT id FROM photos WHERE location_id='$eventID'");
      $sqlNumPhotos = mysqli_num_rows($sqlGetPhotoNumber);
           
      $result[$i] = array(
        'whosEvent' => $eventTyppe,
        'eventID' => $row['id'],
        'originalDB' => $eventType,
        'type' => $row['type'],
        'time' => $row['time'],   
        'unixTimeStamp' => $databaseDateTime,
        'street' => $row['street'],
        'city' => $row['city'],
        'state' => $row['state'],
        'country' => $row['country'],
        'age' => $row['age'],
        'admission' => $row['admission'],
        'rsvpLimit' => $row['rsvp_limit'],
        'rsvps' => $sqlNumRsvps,
        'comments' => $sqlNumComments,
        'photos' => $sqlNumPhotos,
        'title' => htmlkarakter($row['title']),
        'keyword1' => $row['keyword1'],
        'keyword2' => $row['keyword2'],
        'keyword3' => $row['keyword3'],
        'description' => $row['description'],
        'latitude' => $row['latitude'],
        'longitude' => $row['longitude'],
        'userID' => $userID,
        'username' => $username, 
        'posted' => $unixPostedStamp,
       );
      $i++;
    }//end while
    
    return $result;
  }
?>
