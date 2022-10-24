<?php
  //note: test rsvp cancel, favorite functionality
  //note: see if we can't substitute all the events for the actual types of locations
  include_once("scripts/connectToMySQL.php");
  include_once("scripts/includeFunctions.php");
  
  $formatPostTime; $eventType; $type; $time; $street; $city; $state; 
  $country; $age; $admission; $rsvpLimit; $title; $description; $latitude; 
  $longitude; $postString; $buttonString; $rsvpButtonString;
  
  if (isset($_SESSION['idx'])) {
      $id = $logOptions_id;
  }
  $id= 2;
  
  if (isset($_GET['id'])) {
    $eventID = preg_replace('#[^0-9]#', '', $_GET['id']);
    $todaysDateTime = time();
    $username;
    $commentDisplayList = '';
    
    $sqlGetEvent = mysqli_query($link, "SELECT * FROM locations WHERE id='$eventID' LIMIT 1");
    $sqlEventRows = mysqli_num_rows($sqlGetEvent);
    
    if ($sqlEventRows > 0) {
      while ($row = mysqli_fetch_array($sqlGetEvent)) {
        //get username
        $userID = $row['user_id'];
        $username = getUsername($userID);
        
        //set posted string
        $unixPostedStamp = strtotime($row['post_date']);
        $formatPostTime = date("D M j, Y", $unixPostedStamp);
        $postString = 'Posted ' . $formatPostTime . ' by <a href="profile.php?id=' . $userID . '">' . $username . '</a>';  
        
        //compare times to check if event already happened; databaseDateTime returns date("Y-m-d H:i:s");
        $databaseDateTime = strtotime($row['time']);
        if ($todaysDateTime < $databaseDateTime ) { $eventType = "future"; } else { $eventType = 'past'; }
        
        $id;
        if (isset($_SESSION['idx'])) {
          $id = $logOptions_id;
        } 
        
        //set buttons to either past or future
        $rsvpLimit = $row['rsvp_limit'];
        if ($eventType == "past") {
        
          //photo number, for past
      	  $sqlGetPhotoNumber = mysqli_query($link, "SELECT id FROM photos WHERE location_id='$eventID'");
          $sqlNumPhotos = mysqli_num_rows($sqlGetPhotoNumber);
          
          //sets buttonString
          $photoString;
          
          if ($sqlNumPhotos == 0) {
            $photoString = "Add Photo";
          } else if ($sqlNumPhotos == 1) {
            $photoString = $sqlNumPhotos . ' Photo';
          } else {
            $photoString = $sqlNumPhotos . ' Photos';
          }
          
          $buttonString = '<a href="photos.php?id=' . $eventID . '&type=event">' . $photoString. '</a>';
        } else {
          
          //set rsvp button string
          if (isset($id)) {
            //see if user started event
            $sqlGetOrig = mysqli_query($link, "SELECT * FROM locations WHERE id='$eventID' AND user_id='$id' LIMIT 1");
            $sqlNumOrig = mysqli_num_rows($sqlGetOrig);
            
            //originator? They just get cancel button
            if ($sqlNumOrig > 0) {
              $rsvpButtonString = '<a href="#" onclick="return false" onmousedown="javascript:updateEvent(\'' . $eventID 
              . '\', \'cancel\');">Cancel Event</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="eventEdit.php?id=' . $eventID . '">Edit Event</a>';
            } else {//not originator? 
               $sqlSelectRsvp = mysqli_query($link, "SELECT * FROM location_rsvps WHERE location_id='$eventID' AND user_id='$id' AND active='y'");
          	   $sqlSelectFav = mysqli_query($link, "SELECT * FROM location_favs WHERE location_id='$eventID' AND user_id='$id' AND active='y'");
          	   $sqlNumRsvps = mysqli_num_rows($sqlSelectRsvp);
          	   $sqlNumFavs = mysqli_num_rows($sqlSelectFav);
          	   
          	   if ($sqlNumRsvps > 0) {//if member rsvpd already, they can only cancel
          	     $rsvpButtonString =  '<a href="#" onclick="return false" onmousedown="javascript:updateEvent(\'' . $eventID . '\', \'cancel\');">Cancel RSVP</a>';
          	   } else if ($sqlNumFavs > 0) {//if member fav'd already, they can rsvp or cancel fav
          	     $rsvpButtonString =  '<a href="#" onclick="return false" onmousedown="javascript:cancelEvent(\'' . $eventID 
          	     . '\', \'cancel\');">Remove From Favorites</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="#" onclick="return false" onmousedown="javascript:updateEvent(\'' 
          	     . $id . '\', \'' . $eventID . '\', \'rsvp\');">RSVP For This Event!</a>';
          	   } else { //member is not originator, or rsvp'd or fav'd, so they can rsvp or fav
          	     $rsvpButtonString = '<a href="#" onclick="return false" onmousedown="javascript:updateEvent(\''
          	     . $eventID . '\', \'rsvp\');">RSVP For This Event!</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="#" onclick="return false" onmousedown="javascript:updateEvent(\''
          	     . $eventID . '\', \'favorite\');">Add This Event To Favorites!</a>';
          	   }
          	 }
          }//end isset $id
        
          //get # of rsvps
          $sqlGetRsvpNumber = mysqli_query($link, "SELECT id FROM location_rsvps WHERE location_id='$eventID' AND active='y'");
          $sqlNumRsvps = mysqli_num_rows($sqlGetRsvpNumber);
          
          //set buttonstring
          $rsvpString = ''; $endString = ''; $midString = '';
          if ($rsvpLimit != 0) {
            if ($sqlNumRsvps >= $rsvpLimit) {
              $rsvpString = 'This event is full to capacity';
            } else {
              if ($sqlNumRsvps > 0) {
                $rsvpDifference = $rsvpLimit - $sqlNumRsvps;
              
                if ($rsvpDifference == 1) {
                  $endString = ' invite open. Hurry!';
                } else {
                  $endString = ' invites open. Hurry!';
                }
              
                if ($sqlNumRsvps == 1) {
                  $midString = ' person has rsvp\'d! ';
                } else {
                  $midString = ' people have rsvp\'d! ';
                }
              
                $rsvpString = $sqlNumRsvps . '' . $midString . '' . $rsvpDifference . '' . $endString;
              } else {
              
                $rsvpString = 'No one has rsvp\'d yet. Be the first!';
              }// endif ($sqlNumRsvps > 0)
              
            }//end if ($sqlNumRsvps >= $rsvpLimit)
            
            $rsvpString = 'Come one come all! Unlimited rsvp!';
          }//end if ($rsvpLimit != 0)
          
          $buttonString = '<a href="rsvp.php?id=' . $eventID . '">' . $rsvpString . '</a>';
        }
        
        
        
        //set admission string
        $sqlAdmission = $row['admission'];
        if ($sqlAdmission == 0) {
          $admission = 'Free';
        } else {
          $admission = '$' . $sqlAdmission . '.00';
        }
    	
    	//set individual variables
        $type = $row['type'];
        $time = date("D M j, Y g:i a", $databaseDateTime);   
        $street = $row['street'];
        $city = $row['city'];
        $state = $row['state'];
        $country = $row['country'];
        $age = $row['age'];
        $title = htmlkarakter($row['title']);
        $description = htmlkarakter($row['description']);
        $latitude = $row['latitude'];
        $longitude = $row['longitude'];
      }//end while fetch_array
    }//sqlEventRows
    
    //now get comments
    $sqlGetComments = mysqli_query($link, "SELECT * FROM location_comments WHERE location_id='$eventID' ORDER BY post_date ASC");
    $commentsCheck = mysqli_num_rows($sqlGetComments);
    if ($commentsCheck > 0) {
      while ($row = mysqli_fetch_array($sqlGetComments)) {
        $dateRow = $row['post_date'];
      	$commentRow = $row['comment'];
      	$userID = $row['user_id'];
      	$convertedTime = convertDatetime($dateRow);
      	$timeAgo = makeAgo($convertedTime);
      	$commentDisplayList .= '<table class="comment_table">
    						  	  <tr>
    						    	<td class="comment_date">'
    						      		. $timeAgo .
    						       '</td>
    						  	  </tr>
    		                  	  <tr>
    						    	<td class="comment_display">
    						      	  ' . htmlkarakter($commentRow) . '
    						    	</td>
    						  	  </tr>
    						  	  <tr>
    		  						<td class="name_display">
    		  						  <a href="profile.php?id=' . $userID . '">' . $username . '</a>
    		  						</td>
    							  </tr>
    							</table>';
          
          
      }//end while ($row = mysqli_fetch_array($sqlGetComments)) 
    }//end if ($commentsCheck == 0)
  } else if (isset($_POST['event'])) {//if rsvping or favoriting or cancelling
    if (isset($_SESSION['idx'])) {
      $id = $logOptions_id;
      $eventID = preg_replace('#[^0-9]#', '', $_POST['event']);
      $requestType = $_POST['type'];
      
      $sqlGetOrig = mysqli_query($link, "SELECT * FROM locations WHERE id='$eventID' AND user_id='$id' LIMIT 1");
      $sqlNumOrig = mysqli_num_rows($sqlGetOrig);
      $sqlSelectRsvp = mysqli_query($link, "SELECT * FROM location_rsvps WHERE location_id='$eventID' AND user_id='$id' AND active='y'");
      $sqlSelectFav = mysqli_query($link, "SELECT * FROM location_favs WHERE location_id='$eventID' AND user_id='$id' AND active='y'");
      $sqlNumRsvps = mysqli_num_rows($sqlSelectRsvp);
      $sqlNumFavs = mysqli_num_rows($sqlSelectFav);
      
      //handle cancel block
      
      if ($sqlNumOrig > 0 && $requestType == 'cancel') { //if this is members actual event
        echo '<a href="#" onclick="return false" onmousedown="javascript:confirmEvent(\'' . $eventID . '\', \'cancel\');">
        Are you sure you want to cancel this event? All RSVP\'s will be notified.</a>';
      } else if($sqlNumRsvps > 0 && $requestType == 'cancel') {
        echo '<a href="#" onclick="return false" onmousedown="javascript:confirmEvent(\'' . $eventID . '\', \'cancel\');">
        Are you sure you want to cancel on this event?</a>';
      } else if($sqlNumFavs > 0 && requestType == 'cancel') {
        $sqlUpdateFav = mysqli_query($link, "UPDATE location_favs SET active='n' WHERE location_id='$eventID' AND user_id='$id'");
        echo 'This event was removed from your favorites.';
      } else if ($sqlNumOrig == 0 && $sqlNumRsvps == 0 && requestType == 'rsvp') {
        echo '<a href="#" onclick="return false" onmousedown="javascript:confirmEvent(\'' . $eventID . '\', \'rsvp\');">
        Just making sure you want to RSVP for this event!</a>';
      } else if ($sqlNumOrig == 0 && $sqlNumRsvps == 0 && $sqlNumFavs == 0 && $requestType == 'favorite') {
        //make sure they haven't fav'd before at all, regardless of active
        $sqlSelectFav = mysqli_query($link, "SELECT * FROM location_favs WHERE location_id='$eventID' AND user_id='$id'");
        $sqlNumFav = mysqli_num_rows($sqlSelectFav);
        if ($sqlNumFav > 0) {
          $sqlUpdateFav = mysqli_query($link, "UPDATE location_favs SET active='y' WHERE location_id='$eventID' AND user_id='$id'");
          echo "This event has been readded to your favorites";
        } else {
          $sqlPostFav = mysqli_query($link, "INSERT INTO location_favs (location_id, user_id, post_date) VALUES ('$eventID', '$id', now())");
          echo "This event has been added to your favorites";
        }
      } else {
        echo "Command not recognized";
      }
    } else {
      echo "Only users can do that!";
    }//end if (isset($_SESSION['idx']))
  } else if (isset($_POST['cEvent'])  && isset($_SESSION['idx'])) {//cancel event
      $id = $logOptions_id;
      $eventID = preg_replace('#[^0-9]#', '', $_POST['event']);
      $requestType = $_POST['cType'];
      
      $sqlGetOrig = mysqli_query($link, "SELECT * FROM locations WHERE id='$eventID' AND user_id='$id' LIMIT 1");
      $sqlNumOrig = mysqli_num_rows($sqlGetOrig);
      $sqlSelectRsvp = mysqli_query($link, "SELECT * FROM location_rsvps WHERE location_id='$eventID' AND user_id='$id' AND active='y'");
      $sqlSelectFav = mysqli_query($link, "SELECT * FROM location_favs WHERE location_id='$eventID' AND user_id='$id' AND active='y'");
      $sqlNumRsvps = mysqli_num_rows($sqlSelectRsvp);
      $sqlNumFavs = mysqli_num_rows($sqlSelectFav);
      
      if ($sqlNumOrig > 0 && $requestType == 'cancel') {
        //set event active and in_user_past to n so originator won't see it 
        $sqlUpdateLoc = mysqli_query($link, "UPDATE locations SET active='n', in_user_past='n' WHERE id='$eventID' AND user_id='$id'");
        //check to see if any users signed up so we can send them canceled pm's
        $sqlSelectRsvp = mysqli_query($link, "SELECT * FROM location_rsvps WHERE location_id='$eventID' AND active='y'");
        $sqlNumRsvps = mysqli_num_rows($sqlSelectRsvp);

        if ($sqlNumRsvps > 0) {//send a private message to all who made a rsvp
          while ($row = mysqli_fetch_array($sqlSelectRsvp)) {
            $userID = $row['user_id'];
            while ($row = mysqli_fetch_array($sqlSelectLoc)) {
              $street = $row['street'];
              $city = $row['city'];
              $state = $row['state'];
              $time = strtotime($row['time']);
              $meetUserID = $row['user_id'];
            }
            $meetUsername = getUsername($meetUserID);
            $date = date("F j, Y, g:i a", $time); //e.g. March 10, 2001, 5:16 pm
            
            $subject = 'CANCELLATION';
            $pm = 'The event started by ' . $meetUsername . ', on ' . $date . ', located at ' . $street . ' ' . $city . ' ' . $state . ', has been canceled. Please be advised';
            $sqlPostPm = mysqli_query($link, "INSERT INTO private_messages (to_id, from_id, subject, message, post_date) VALUES ('$userID', '$meetUserID', '$subject', '$pm', now())");
          }//end while
        }//end if
        $sqlUpdateRsvp = mysqli_query($link, "UPDATE location_rsvps SET active='n' WHERE location_id='$eventID'");
        echo "You've just canceled on this event";
      } else if($sqlNumRsvps > 0 && $requestType == 'cancel') {
        $sqlUpdateRsvp = mysqli_query($link, "UPDATE location_rsvps SET active='n' WHERE location_id='$eventID' AND user_id='$id'");
        echo "You've just canceled on this event.";
      } else if ($sqlNumOrig == 0 && $sqlNumRsvps == 0 && requestType == 'rsvp') { 
        //check to see if user has ever rsvpd for this event before
        $sqlSelectRsvp = mysqli_query($link, "SELECT * FROM location_rsvps WHERE location_id='$eventID' AND user_id='$id'");
        $sqlSelectFav = mysqli_query($link, "SELECT * FROM location_favs WHERE location_id='$eventID' AND user_id='$id' AND active='y'");
        $sqlNumRsvp = mysqli_num_rows($sqlSelectRsvp);
        $sqlNumFav = mysqli_num_rows($sqlSelectFav);
      
        if ($sqlNumRsvp > 0) {//user rsvp'd before
          while ($row = mysqli_fetch_array($sqlSelectRsvp)) { $active = $row['active'];  $invited = $row['invited']; }
          if ($active == 'n') {//they must have rsvp'd and then canceled, setting active to n, so just set active to y
            if ($invited == 'y') {
              $sqlUpdateRsvp = mysqli_query($link, "UPDATE location_rsvps SET active='y' WHERE location_id='$eventID' AND user_id='$id'");
              //if they favorited this event, make it inactive so there won't be duplicate entries
              if ($sqlNumFavs > 0) { $sqlUpdateFav = mysqli_query($link, "UPDATE location_favs SET active='n' WHERE location_id='$eventID' AND user_id='$id'"); }
              echo "You've just RSVP'd for this event!";
            } else {
              echo "Unfortunately, your previous rsvp was canceled by the event starter.\n You can not rsvp for this event again.";
            }
          }
        } else {//user has not rsvp'd
          $sqlPostRsvp = mysqli_query($link, "INSERT INTO location_rsvps (location_id, user_id, post_date) VALUES ('$eventID', '$id', now())");
          if ($sqlNumFav > 0) { $sqlUpdateFav = mysqli_query($link, "UPDATE location_favs SET active='n' WHERE location_id='$eventID' AND user_id='$id'"); }
          echo "You've just RSVP'd for this event!";
        } 
      } else {
        echo "Command not recognized";
      }
  } else if (isset($_POST['eventComment']) && isset($id)) {//if comment posted
    $postID = preg_replace('#[^0-9]#', '', $_POST['eventComment']);
    $comment = clean($_POST["comment"]);
    if (is_numeric($postID)) {
      $sqlInsertComment = mysqli_query($link, "INSERT INTO location_comments (location_id, user_id, comment, post_date) VALUES ('$postID', '$id', '$comment', now())") or die (mysqli_error($link));
      $date = date("Y-m-d H:i:s");
      $convertedTime = convertDatetime($date);
      $timeAgo = makeAgo($convertedTime);
      $username = getUsername($id);
      echo '<table class="comment_table">
    		  <tr>
    		    <td class="comment_date">'
    		      . $timeAgo .
    		    '</td>
    		  </tr>
    		  <tr>
    		    <td class="comment_display">
    			  ' . htmlkarakter($comment) . '
    		    </td>
    		  </tr>
    		  <tr>
    		    <td class="name_display">
    		      <a href="profile.php?id=' . $id . '">' . $username . '</a>
    		    </td>
    		  </tr>
    	    </table>';
      exit();
    } else {
      echo "No event specified";
      exit;
    }
  } /*else {
    echo "Only users can do that!";
  }//end $_GET/$_POST/$_POST*/

?>
<?php include_once("inc/head.inc.php"); ?>
  <style>
  #content_wrapper {
    border: 1px solid red;
    border-left: 1px solid blue;
    width: 904px;
    margin-left: auto;
    margin-right: auto;
  }
  #left_side_container {
    width: 350px;
    float: left;
    border: 1px solid white;
  }
  #right_side_container {
    width: 550px;
    float: right;
    border: 1px solid white;
  }
  #map {
    margin-left: auto;
    margin-right: auto;
  }
  #bottom_container {
    clear: both;
    text-align: center;
  }
  .comment_table {
    width: 265px;
    background: white;
    border: 1px solid black;
    table-layout:fixed;
  }
  .comment_display {
	width: 264px;
	overflow:hidden;
	color: black;
  }
  .comment_date{
	color:#7C7C7C;
	font-size:10px;
  }
  .name_display a:link {
	text-decoration:none;
	color:black;
	font-size:10px;
  }
  </style>
  <script src="scripts/jquery-1.8.1.min.js"></script>
  <script type="text/javascript" 
           src="http://maps.googleapis.com/maps/api/js?sensor=false">
  </script>
  <script type="text/javascript"> 
    function initialize() {
      var map = new google.maps.Map(document.getElementById("map"), {
        zoom: 12,
        center: new google.maps.LatLng(<?php echo $latitude; ?>, <?php echo $longitude; ?>),
        mapTypeId: google.maps.MapTypeId.ROADMAP
      });
      
      var marker = new google.maps.Marker({
  		position: new google.maps.LatLng(<?php echo $latitude; ?>, <?php echo $longitude; ?>), 
 		map: map
	  });
    }
    
    function updateEvent(event, type) {
      var eventID = event;
      var requestType = type;
      var url = "event.php";
      
      $.post(url, { event: eventID, type: requestType }, function(data) {
        $(rsvp_wrapper).val() == data; 
      });//end post
    }
    
    function confirmEvent(event, type) {
      var eventID = event;
      var requestType = type;
      var url = "event.php";
      
      $.post(url, { cEvent: eventID, cType: requestType }, function(data) {
        $(rsvp_wrapper).val() == data; 
      });//end post
    }
    
    var postNumber = 0;
    function sendComment() {
      var formData = $('#commentForm').serialize();
	  var url = "event.php";
	  if ($("#comment").val() == "") {
        $("#interactionResults").html('&nbsp; Please type something.').show().fadeOut(6000);
      } else {
        if (postNumber < 2) {
	      $("#pmFormProcessGif").show();
	      $.post(url, formData, function(data) {
	        var returnString = data;
	        if (returnString.match(/^Error:/)) {
	          $("#interactionResults").html(data).show().fadeOut(6000);
	        } else if (postNumber == 0) {
	          $('#newCommentDisplay').html(returnString).show();
	        } else if (postNumber == 1){
	          $('#newCommentDisplay2').html(returnString).show();
	        }
	        
	        document.commentForm.comment.value='';
	        $('#pmFormProcessGif').hide();
	        ++postNumber;
	      }); // end post
	    } else {
	     $('#interactionResults').html("<p>To combat spam, we only allow 2 posts at a time.<br />Please refresh your browser and try again. Thank you.<p>").show();
	    }//end if postNumber
	  }
    }
   </script> 
</head>
<body onload="initialize()">
  <div id="content_wrapper">
    <h2><?php echo isset($title) ? $title ? $title : $type : ''; ?></h2>
    <div id="rsvp_wrapper">
      <?php echo isset($rsvpButtonString) ? $rsvpButtonString : ''; ?>
    </div><!--end rsvp_wrapper-->
    <div id="left_side_container">
      <div id="event_info">
        <table class="event_table">
        <tr>
          <td class="event_table_0">
            <?php echo isset($type) ? $type : ''; ?>
          </td>
        </tr>
        <tr> 
          <td class="event_table_1">
            <?php echo isset($time) ? $time : ''; ?>
          </td>
        </tr>
        <tr>
          <td class="event_table_2">
            <?php echo isset($age) ? $age : ''; ?>
          </td>
        </tr>
        <tr>
          <td class="event_table_3">
            <?php echo isset($admission) ? $admission : ''; ?>
          </td>
        </tr>
        </table>
      </div><!--end event_info-->
    </div><!--end left_side_container-->
    <div id="right_side_container">
      <div id="map" style="width: 400px; height: 300px"></div>
      <div id="post_profile_link">
        <?php echo isset($postString) ? $postString : ''; ?>
      </div>
    </div><!--end right_side_container-->
    <div id="bottom_container">
      <div id="address">
        <?php echo isset($street) ?  $street . ' ' . $city . ' ' . $state . ' ' . $country : ''; ?>
      </div>
      <div id="description">
        <?php echo isset($description) ? $description : ''; ?>
      </div>
      <div id="buttons">
        <?php echo isset($buttonString) ? $buttonString : ''; ?>
      </div>
      
    </div><!--end bottom_container-->
    <?php if (isset($id)) { ?>
      <div id="interactionResults" style="font-size:15px; padding:10px;"></div>
        <form action="javascript:sendComment();" method="post" name="commentForm" id="commentForm">
          <textarea name="comment" cols="35" rows="5" id="comment"></textarea><br />
          <input type="hidden" name="eventComment" id="eventComment" value="<?php echo isset($eventID) ? $eventID : ''; ?>" />
          <input type="submit" name="button" id="button" value="Submit"/>
          <span id="pmFormProcessGif" style="display:none;"><img src="images/loading.gif" width="28" height="10" alt="Loading" /></span>
        </form>
        <div class="commentDisplayList">
        <div id="newCommentDisplay2"></div>
        <div id="newCommentDisplay"></div>
      </div>
      <?php } ?>
      <div class="comment_display_list"><?php echo isset($commentDisplayList) ? $commentDisplayList: ''; ?></div>
  </div><!--end content_wrapper-->
</body>
</html>