<?php
  include_once("scripts/connectToMySQL.php");
  include_once("scripts/includeFunctions.php");
  
  if (isset($_SESSION['idx'])) {
      $id = $logOptions_id;
  }
  $id= 2;
  
  function makeList($event, $title, $eventDate) {
    $listName = '<table class="popular_table">
    				    <tr>
    					  <td class="popular_info">
    					    <a href="event.php?id=' . $event . '"><strong>' . $title . '</strong>
    					    <span class="liteGreyColor textsize11">' . $eventDate . '</span>
    					  </td>
    				    </tr>
    				  </table>';
    				  
    return $listName;
  }
  
  if (isset($_GET['id']) && isset($id)) {
    $profileID = preg_replace('#[^0-9]#', '', $_GET['id']);
    if (isset($_GET['invite'])) { 
      $inviteID = preg_replace('#[^0-9]#', '', $_GET['invite']); 
    }
    
    
    $todaysDateTime = time();
    $myArray = array();
    $favArray = array();
    $rsvpArray = array();
    $pastArray = array();
    $myList = '';
    $favList = '';
    $rsvpList = '';
    $pastList = '';
    
    $sqlGetMy = mysqli_query($link, "SELECT * FROM locations WHERE user_id='$profileID' AND active='y' AND in_user_past='y' ORDER BY post_date ASC");
    $sqlNumMy = mysqli_num_rows($sqlGetMy);
    
    if ($sqlNumMy > 0) {
      while($row = mysqli_fetch_array($sqlGetMy)) {
        $eventID = $row['id'];
        $eventTitle = $row['title'];
        $eventType = $row['type'];
        $unixTimeStamp = strtotime($row['time']);
        
        $eventHeading = $eventTitle ? $eventTitle : $eventType;
        
        
        $myArray[] = $eventID . '`' . $eventHeading . '`' . $unixTimeStamp;
      }//end while
    }//end if
    
    //get rsvps if user on own profile, check if rsvps should be shown on visiting profile
    if ($profileID == $id) {
      //select events that user rsvp'd
      $sqlGetRsvp = mysqli_query($link, "SELECT location_id FROM location_rsvps WHERE user_id='$profileID' AND active='y'");
      $sqlNumRsvps = mysqli_num_rows($sqlGetRsvp);
      if ($sqlNumRsvps > 0) {
        while($row = mysqli_fetch_array($sqlGetRsvp)) {
          $locationID = $row['location_id'];
		  $rsvpArray[] = $locationID;
        }//end while
      }//end if
    } else {
      $sqlRsvpShow = mysqli_query($link, "SELECT rsvp_show FROM members WHERE id='$profileID' LIMIT 1");
      while($row = mysqli_fetch_array($sqlRsvpShow)) { $rsvpShow = $row['rsvp_show']; }
      if ($rsvpShow == 'y') {
        //select events that user rsvp'd
        $sqlGetRsvp = mysqli_query($link, "SELECT location_id FROM location_rsvps WHERE user_id='$profileID' AND active='y'");
        $sqlNumRsvps = mysqli_num_rows($sqlGetRsvp);
        if ($sqlNumRsvps > 0) {
          while($row = mysqli_fetch_array($sqlGetRsvp)) {
            $locationID = $row['location_id'];
        	$rsvpArray[] = $locationID;
          }//end while
        }//end if
      }
    }
    
    //select events user favorited
    $sqlGetFav = mysqli_query($link, "SELECT location_id FROM location_favs WHERE user_id='$profileID' AND active='y'");
    $sqlNumFavs = mysqli_num_rows($sqlGetFav);
    if ($sqlNumFavs > 0) {
      while($row = mysqli_fetch_array($sqlGetFav)) {
        $locationID = $row['location_id'];
        $favArray[] = $locationID;
      }//end while
    }//end if
    
    if (count($myArray) != 0 || count($favArray) != 0 || count($rsvpArray) != 0) {
      if (count($myArray) > 0) {
        $myList = '<h3>My Events</h3>
         		   <table class ="my_table" border="1">';
        
        $inviteLink = '';
         		  
        foreach ($myArray as $key => $value){
          $kaboom = explode("`", $value);
          $eventID = $kaboom[0];
          $eventHead = htmlkarakter($kaboom[1]);
          $eventTime = date("D M j, Y g:i a", $kaboom[2]);
          
          if (isset($inviteID)) {
            $inviteLink = '<td><a href="#" class="invite" onclick="return false" onmousedown="javascript:inviteMember(' . $inviteID . ', ' . $eventID . ');">Invite</a></td>';
          } 
          
          if ($todaysDateTime < $kaboom[2] ) { 
        
            $myList .= '<tr>
                          <td class="my_link">
    					    <a href="event.php?id=' . $eventID . '"><strong>' . $eventHead . '</strong>
    					    <span class="liteGreyColor textsize11">' . $eventTime . '</span>
    					  </td>'
    					  . $inviteLink . '
    					</tr>';
          } else { 
            array_shift($myArray);
            $pastArray[] = $eventID . '`' . $eventHead . '`' . $eventTime;
          }
        }
        
        $myList .= '</table>';
      }
      
      if (count($favArray) > 0) {
        $favList = '<h3>My Favorites</h3>
          			<table class ="fav_table" border="1">';
        foreach ($favArray as $key => $value){
          $sqlGetEvent = mysqli_query($link, "SELECT * FROM locations WHERE id='$value' LIMIT 1");
          while ($row = mysqli_fetch_array($sqlGetEvent)) {
            $eventID = $row['id'];
            $eventTitle = $row['title'];
            $eventType = $row['type'];
        	$unixTimeStamp = strtotime($row['time']);
        	
        	$eventHeading = $eventTitle ? htmlkarakter($eventTitle) : $eventType;

            $eventTime = date("D M j, Y g:i a", $unixTimeStamp);
          
            if ($todaysDateTime < $unixTimeStamp ) { 
              $favList .= '<tr>
                          	 <td class="fav_link">
    					       <a href="event.php?id=' . $eventID . '"><strong>' . $eventHeading . '</strong>
    					       <span class="liteGreyColor textsize11">' . $eventTime . '</span>
    					     </td>
    					   </tr>'; 
            } else { 
              array_shift($favArray);
              $pastArray[] = $value . '`' . $eventHeading . '`' . $eventTime;
            }
            
          }
          $favList .= '</table>';
        }
      }
        
        if (count($rsvpArray) > 0) {
          $rsvpList = '<h3>RSVP\'d</h3>
          			   <table class ="rsvp_table" border="1">';
          			   
          $inviteLink = '';
        
          foreach ($rsvpArray as $key => $value){
            $sqlGetEvent = mysqli_query($link, "SELECT * FROM locations WHERE id='$value' LIMIT 1");
            while ($row = mysqli_fetch_array($sqlGetEvent)) {
              $eventID = $row['id'];
              $eventTitle = $row['title'];
              $eventType = $row['type'];
        	  $unixTimeStamp = strtotime($row['time']);
        	  
        	  if (isset($inviteID)) {
                $inviteLink = '<td><a href="#" class="invite" onclick="return false" onmousedown="javascript:inviteMember(' . $inviteID . ', ' . $eventID . ');">Invite</a></td>';
              } 
        	
        	  $eventHeading = $eventTitle ? htmlkarakter($eventTitle) : $eventType;

              $eventTime = date("D M j, Y g:i a", $unixTimeStamp);
              
              if ($todaysDateTime < $unixTimeStamp ) { 
                $rsvpList .= '<tr>
                          		<td class="rsvp_link">
    					    	  <a href="event.php?id=' . $eventID . '"><strong>' . $eventHeading . '</strong>
    					    	  <span class="liteGreyColor textsize11">' . $eventTime . '</span>
    					        </td>
    					        ' . $inviteLink . '
    					      </tr>';
              } else { 
                array_shift($rsvpArray);
                $pastArray[] = $value . '`' . $eventHeading . '`' . $eventTime;
              }
          
            }
            
            $rsvpList .= '</table>';
          }
        }
        
      if (count($pastArray) > 0) {
        $pastList = '<h3>Past Events</h3>
        			 <table class ="past_table" border="1">';
        $removeLink = '';
          
        foreach ($pastArray as $key => $value){
          $kaboom = explode("`", $value);
          $eventID = $kaboom[0];
          
          $eventHead = htmlkarakter($kaboom[1]);
          $eventTime = $kaboom[2];
          
          if ($profileID == $id) {
            $removeLink = '<td class="post_link remove"><a href="#" class="remove" onclick="return false" onmousedown="javascript:removePast(' . $eventID . ', ' . $profileID . ');">Remove</a></td>';
          }
            
          $pastList .= '<tr id="link_id_' . $eventID . '">
                          <td class="past_link">
    					    <a href="event.php?id=' . $eventID . '"><strong>' . $eventHead . '</strong>
    					    <span class="liteGreyColor textsize11">' . $eventTime . '</span>
    					  </td>
    					  ' . $removeLink . '
    					</tr>';
         
        }
        
        $pastList .= '</table>';
      }
      
      
    } else {
      echo "This member has no events";
    }
    
  } else if (isset($_POST['inviteID']) && isset($id)) {
    $inviteID = preg_replace('#[^0-9]#', '', $_POST['inviteID']);
    $eventID = preg_replace('#[^0-9]#', '', $_POST['eventID']);
    $todaysDateTime = time();
    
    if ($inviteID == $id) {
      echo "Can't invite yourself silly";
      exit();
    } else {
      $sqlCheckEvent = mysqli_query($link, "SELECT id FROM locations WHERE id='$eventID' AND user_id='$inviteID' LIMIT 1");
      $checkEventCount = mysqli_num_rows($sqlCheckEvent);
      
      $sqlCheckRSVP = mysqli_query($link, "SELECT id FROM location_rsvps WHERE location_id='$eventID' AND user_id='$inviteID' AND active='y'");
      $checkRSVPCount = mysqli_num_rows($sqlCheckRSVP);
      
      if ($checkEventCount > 0) {
        echo "This member started this event.";
        exit();
      } else if ($checkRSVPCount > 0) {
        echo "This member has already rsvp'd for this event.";
        exit();
      } else {
        $sqlGetEvent = mysqli_query($link, "SELECT * FROM locations WHERE id='$eventID' LIMIT 1");
        $checkGetCount = mysqli_num_rows($sqlGetEvent);
        if ($checkGetCount > 0) {
          while ($row = mysqli_fetch_array($sqlGetEvent)) {
            $eventType = $row['type'];
            $eventTitle = $row['title'];
            $eventTime = $row['time'];
            $eventStreet = $row['street'];
            $eventCity = $row['city'];
            $eventState = $row['state'];
          }
          
          $eventHeading = $eventTitle ? htmlkarakter($eventTitle) : 'a ' . $eventType;
          
          $myUsername = getUsername($id);
          $date = date("F j, Y, g:i a", $todaysDateTime); //e.g. March 10, 2001, 5:16 pm
            
          $subject = 'Invite: ' . $eventHeading;
          $pm = '<a href="profile.php?id=' . $id . '">' . $myUsername . '</a> has invited you to <a href="event.php?id=' . $eventID . '">' . $eventHeading . '<a>, located at ' . $eventStreet . ' ' . $eventCity . ' ' . $eventState . '.';
          
          $sqlCheckInvite = mysqli_query($link, "SELECT * FROM private_messages WHERE message='$pm' AND to_id='$inviteID'");
          $inviteCount = mysqli_num_rows($sqlCheckInvite);
          
          if ($inviteCount > 0) {
            echo "This member has already been invited.";
            exit();
          } else {
            $sqlPostPm = mysqli_query($link, "INSERT INTO private_messages (to_id, from_id, subject, message, post_date) VALUES ('$inviteID', '$id', '$subject', '$pm', now())");
            echo "Your invite has been sent.";
            exit();
          }
        }
      }
    }
  } else if (isset($_POST['removeEventID']) && isset($id)) {
    $removeID = preg_replace('#[^0-9]#', '', $_POST['removeEventID']);
    $profileID = preg_replace('#[^0-9]#', '', $_POST['profileID']);
    
    if ($profileID != $id) {
      echo "You can only remove your own events.";
      exit();
    } else {
      $sqlCheckEvent = mysqli_query($link, "SELECT id FROM locations WHERE id='$removeID' AND user_id='$id' AND in_user_past='y' LIMIT 1");
      $checkEventCount = mysqli_num_rows($sqlCheckEvent);
      
      $sqlCheckRSVP = mysqli_query($link, "SELECT id FROM location_rsvps WHERE location_id='$removeID' AND user_id='$id' AND active='y'");
      $checkRSVPCount = mysqli_num_rows($sqlCheckRSVP);
      
      $sqlCheckFav = mysqli_query($link, "SELECT id FROM location_favs WHERE location_id='$removeID' AND user_id='$id' AND active='y'");
      $checkFavCount = mysqli_num_rows($sqlCheckFav);
      
      if ($checkEventCount > 0) {
        $sqlUpdateMy = mysqli_query($link, "UPDATE locations SET in_user_past='n' WHERE id='$removeID'");
        echo "You've just removed this event.";
        exit();
      } else if ($checkRSVPCount > 0) {
        $sqlUpdateRsvp = mysqli_query($link, "UPDATE location_rsvps SET active='n' WHERE location_id='$removeID' AND user_id='$id'");
        echo "You've just removed this event.";
        exit();
      } else if ($checkFavCount > 0) {
        $sqlUpdateFav = mysqli_query($link, "UPDATE location_favs SET active='n' WHERE location_id='$removeID' AND user_id='$id'");
        echo "You've just removed this event.";
        exit();
      } else {
        echo "There has been a gross miscalculation.";
      }
    }
  }

?>

<?php include_once("inc/head.inc.php"); ?>
  <style>
  </style>
  <script src="scripts/jquery-1.8.1.min.js"></script>
  <script>
    var postRequest = "events.php";
    function inviteMember(id, event) {
      $.post(postRequest,{ inviteID: id, eventID: event } ,
      function(data) {
        $("#add_invite").html(data).show().fadeOut(10000);
      });
    }
    function removePast(id, profile) {
      var removedLink = "link_id_" + id;
      $.post(postRequest,{ removeEventID: id, profileID: profile } ,
      function(data) {
        //document.removedLink.value='';
        $("#add_invite").html(data).show().fadeOut(10000);
      });
    }
  </script>
</head>
<body>
  <div id="add_invite"></div>
  <?php
    echo count($myArray) > 0 ? $myList : '';
    echo count($favArray) > 0 ? $favList : '';
    echo count($rsvpArray) > 0 ? $rsvpList : '';
    echo count($pastArray) > 0 ? $pastList : '';
  ?>
</body>
</html>