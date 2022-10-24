<?php
//add events page,  photo page, InteractionLinksDiv
//maybe pagination on feed items
  include_once("scripts/connectToMySQL.php");
  include_once("scripts/includeFunctions.php");
  
  if (isset($_SESSION['idx'])) {
      $id = $logOptions_id;
  }
  $id= 2;
  
####################get profile info
  if (isset($_GET['id'])) {
    $profileID = preg_replace('#[^0-9]#', '', $_GET['id']);
    $sqlProfile = mysqli_query($link, "SELECT * FROM members WHERE id='$profileID' LIMIT 1");
    $sqlProfileCount = mysqli_num_rows($sqlProfile);
    
    if ($sqlProfileCount > 0) {
      while ($row = mysqli_fetch_array($sqlProfile)) {       
        $username = getUsername($profileID);
        $city = $row['city'];
        $state = $row['state'];
        $country = $row['country'];
        $gender = $row['gender'];
        $track_desired = $row['track_desired'];
        $signUpDate = $row['sign_up_date'];
        $lastLogDate = $row['last_log_date'];
        $bioBody = $row['bio_body'];
        $active = $row['active'];
        $birthday = $row['birthday'];
        $website = $row['website'];
        $userPic = checkUserPic($profileID);
        
      }//end while($row = mysqli_fetch_array($sqlProfile))
    }//end if ($existCount > 0)
    
    
####################get all members this member is following for following box
    $sqlGetFollowing = mysqli_query($link, "SELECT * FROM follow WHERE follower_id='$profileID' AND active='y'");
    $followingCount = mysqli_num_rows($sqlGetFollowing);
    $rankingArray = array();
    $commentDisplayList = '';
    
    if ($followingCount > 0) {
      while ($row2 = mysqli_fetch_array($sqlGetFollowing)) {
        $followingID = $row2['followed_id'];
        $followingUsername = getUsername($followingID);
        
        //now get all posts by user
        $sqlFollowLocComm = mysqli_query($link, "SELECT * FROM location_comments WHERE user_id='$followingID'");
        $followLocCount = mysqli_num_rows($sqlFollowLocComm);
        $sqlFollowPhotoComm = mysqli_query($link, "SELECT * FROM photo_comments WHERE user_id='$followingID'");
        $followPhotoCount = mysqli_num_rows($sqlFollowPhotoComm);
        
        if ($followLocCount > 0) {
          while ($row3 = mysqli_fetch_array($sqlFollowLocComm)) {
            //get loc info
            $locationID = $row3['location_id'];
            $sqlGetLocTitle = mysqli_query($link, "SELECT type FROM locations WHERE id='$locationID' AND ACTIVE='y' LIMIT 1");
            while ($row98 = mysqli_fetch_array($sqlGetLocTitle)) { $type = $row98['type']; }
            $locTitle = $type != 'Other' ? 'a ' . $type : $type;
            
            $unixPostDate = strToTime($row3['post_date']);
            $comment = $row3['comment'];
            $rankingArray[] = $unixPostDate . '`' . $locTitle . '`' . $locationID . '`' . $followingUsername . '`' . $followingID . '`' . $comment . '`location';
          }//end while ($row3 = $sqlFollowLocComm)
        }//end if ($followLocCount > 0)
        
        if ($followPhotoCount > 0) {
          while ($row4 = mysqli_fetch_array($sqlFollowPhotoComm)) {
            $photoID = $row4['photo_id'];
          
            $unixPostDate = strToTime($row4['post_date']);
            $comment = $row4['comment'];
            $rankingArray[] = $unixPostDate . '`a Photo' . '`' . $photoID . '`' . $followingUsername . '`' . $followingID . '`' . $comment . '`photo';
          }//end while ($row4 = $sqlFollowPhotoComm)
        }// end if ($followPhotoCount > 0)
      }//end while ($row2 = mysqli_fetch_array($sqlGetFollowing))
      
      if ($followLocCount > 0 || $followPhotoCount > 0) {
        arsort($rankingArray);
        //get 12 latest updates
        $sortArray[] = array_slice($rankingArray, 0, 12);
        foreach ($sortArray[0] as $key => $value) {
  	      $kaboom = explode("`", $rankingArray[$key]);
  	      $dateRow = date("Y-m-d H:i:s", $kaboom[0]);
  	      $title = $kaboom[1];
  	      $objectID = $kaboom[2];
  	      $followingUsername = $kaboom[3];
  	      $followingID = $kaboom[4];
  	      $commentRow = $kaboom[5];
  	      $objectType = $kaboom[6];
          $convertedTime = convertDatetime($dateRow);
      	  $timeAgo = makeAgo($convertedTime);
      	  $commentDisplayList .= '<table class="comment_table">
      	  						  <tr>
    		  						<td class="name_display">
    		  						  <a href="profile.php?id=' . $followingID . '">' . $followingUsername . ' </a>commented on <a href=" ' 
    		  						  . $objectType . '.php?id=' . $objectID . '">'
    		  						  . $title . '</a>
    		  						</td>
    							  </tr>
    		                  	  <tr>
    						    	<td class="comment_display">
    						      	  ' . htmlkarakter($commentRow) . '
    						    	</td>
    						  	  </tr>
    						  	  <tr>
    						    	<td class="comment_date">'
    						      		. $timeAgo .
    						       '</td>
    						  	  </tr>
    							</table>';
        } //end foreach ($rankingArray as $key => $value)
      }//if ($followLocCount > 0 || $followPhotoCount > 0)
    }//end if ($followingCount > 0)
    
####################
    if (isset($id) && $profileID != $id) {//if visiting member, get if following or not
      $sqlGetFollow = mysqli_query($link, "SELECT * FROM follow WHERE followed_id='$profileID' AND follower_id='$id' AND active='y' LIMIT 1");
      $followCount = mysqli_num_rows($sqlGetFollow);
    
      if ($followCount > 0) {//if visiting member following
        $followLink = '<a href="#" class="hideThis" onclick="return false" onmousedown="javascript:addFollow(' . $profileID . ', \'n\');">Unfollow</a> &nbsp; &nbsp;';
      } else {
        $followLink = '<a href="#" class="hideThis" onclick="return false" onmousedown="javascript:addFollow(' . $profileID  . ', \'y\');">Follow</a> &nbsp; &nbsp;';
      }//end if ($followCount > 0)
      
      $interactionBox = '<table class="interaction_table" cellpadding="10">
      					   <tr>
      					     <td class="photos_link interaction_link">
      					       <a href="photos.php?id=' . $profileID . '&type=profile">My Photos</a>
      					     </td>
      					     <td class="events_link interaction_link">
      					       <a href="events.php?id=' . $profileID . '">My Events</a>
      					     </td>
      					     <td class="invite_link interaction_link">
      					       <a href="events.php?id=' . $id . '&invite=' . $profileID . '">Invite</a>
      					     </td>
      					   </tr>
                           <tr>
                             <td class="private_link interaction_link">
                               <a href="#" onclick="return false" onmousedown="javascript:toggleInteractContainers(\'private_message\');">Private Message</a></div>
                             </td>
                             <td class="follow_link interaction_link">
                               ' . $followLink . '
                             </td>
                           </tr>
                         </table>';
    } else if (isset($id) && $profileID == $id) {//if member viewing their own page
      $interactionBox = '<table class="interaction_table" cellpadding="10">
                           <tr>
                             <td class="private_link interaction_link">
                               <a href="pmInbox.php">Private Messages</a>
                             </td>
                             <td class="edit_link interaction_link">
                               <a href="editProfile.php">Edit Profile</a>
                             </td>
                           </tr>
                           <tr>
                             <td class="events_link interaction_link">
                               <a href="events.php?id=' . $profileID . '">My Events</a>
                             </td>
                             <td class="photos_link interaction_link">
                               <a href="photos.php?id=' . $profileID . '&type=profile">My Photos</a>
                             </td>
                           </tr>
                           <tr>
                             <td class="followers_link interaction_link">
                               <a href="followers.php">My Followers</a>
                             </td>
                             <td class="logout_link interaction_link">
                               <a href="logOut.php">Log Out</a>
                             </td>
                           </tr>
      					 </table>';
    } else {//if non-member viewing site
      $interactionBox = '<div class="interaction_box">
                         <a href="index.php">Sign Up</a> or <a href="index.php">Log In</a> to interact with ' . $username . '
                         </div>';
    }
  } else if (isset($_POST['pmSubject']) && isset($id)) {###########################################
    $subject = preg_replace( '/\s+/', ' ', $_POST['pmSubject']);
    $message = preg_replace( '/\s+/', ' ', $_POST['pmTextArea']);
    $profileID = preg_replace('#[^0-9]#', '', $_POST['pmRecID']);
    
    if ((!$subject) || (!$message)) {
      echo "You need a subject and a message";
      exit();
    } else if ($id == $profileID) {
      echo "You can't send a Private Message to yourself silly!";
      exit();
    } else {
      $subject = clean($subject);
      $message = clean($message);
      $sqlPostMessage = mysqli_query($link, "INSERT INTO private_messages (to_id, from_id, subject, message, post_date) VALUES ('$profileID', '$id', '$subject', '$message', now())");
      echo 'Message Sent';
      exit();
    }
  } else if (isset($_POST['followRequest']) && isset($id)) {#######################################
    $profileID = preg_replace('#[^0-9]#', '', $_POST['followID']);
    $followFlag = $_POST['followRequest'];
    
    if ($id == $profileID) {
      echo "You can't follow yourself silly!";
      exit();
    } else {//follow request
       //now either reject follow or post it
      $sqlGetFollow = mysqli_query($link, "SELECT * FROM follow WHERE followed_id='$profileID' AND follower_id='$id' LIMIT 1");
      $followCount = mysqli_num_rows($sqlGetFollow);
      
      //get user name
      $username = getUsername($profileID);
      
      if ($followCount != 0) {//if user following already
        while ($row = mysqli_fetch_array($sqlGetFollow)) {
          $followID = $row['id'];
          $active = $row['active'];
          
          if ($followFlag == 'y' && $active == 'y') {//this should never get called
      	    echo "You are already following $username";
      	    exit();
          } else if ($followFlag == 'y' && $active == 'n') {
            $sqlUpdatefollow = mysqli_query($link, "UPDATE follow SET active='y' WHERE id='$followID'");
            echo "You are now following $username!";
            exit();
          } else if ($followFlag == 'n' && $active == 'y') { 
            $sqlUpdatefollow = mysqli_query($link, "UPDATE follow SET active='n' WHERE id='$followID'");
            echo "You have now unfollowed $username";
            exit();
          } else {//want to unfollow as indicated by $followFlag, currently not Following
            echo "You are not following $username";
            exit();
          }//end if ($followFlag == 'y' && $active == 'y')
        
        } //end while ($row = mysqli_fetch_array($sqlGetFollow))
      } else {
        if ($followFlag == 'y') {
          $sqlPostUser = mysqli_query($link, "INSERT INTO follow (followed_id, follower_id, post_date) VALUES ('$profileID', '$id', now())"); 
          echo "You are now following $username!";
          exit();
        } else {//this should never get called
          echo "You are not following $username";
          exit();
        }
      }//end if ($followFlag == 'y' && $active == 'y') 
    }//end if ($id == $profileID)
  }
?>
<?php include_once("inc/head.inc.php"); ?>
  <style>
  /*--------PM Box--------*/
  #pmSubject, #pmTextArea { 
    width: 98%; 
  }
  #private_message { 
    background-color:#BDF; 
    border:#999 1px solid; 
    padding:8px; 
  }
    .comment_table {
    width: 265px;
    background: white;
    border: 1px solid black;
    table-layout:fixed;
  }
  /*------Feed Table------*/
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
  .name_display {
    color:black;
    font-size:10px;
  }
  /*------Page layout-------*/
  #content_wrapper {
    border: 1px solid red;
    border-left: 1px solid blue;
    width: 904px;
    margin-left: auto;
    margin-right: auto;
  }
  #right_side_container {
    width: 510px;
    float: right;
  
    border: 1px solid #FFF;
    padding-top: 20px;
    padding-left: 20px;
  }
  #profile_about {
    height: 200px;
    margin-bottom: 20px;
  }
  #profile_feed {
    margin-bottom: 20px; 
  }
  #profile_pictures {
    height: 200px;
    margin-bottom: 20px;
  }
  #left_side_container {
    width: 350px;
    float: left;
  
    border: 1px solid #FFF;
    padding-top: 20px;
    padding-left: 20px;
    padding-bottom: 20px;
  }
  #profile_picture {
    float: left;
    margin-right: 20px;
  }

  #profile_picture1 {
    width: 10px;
    height: 10px;
  }

  #profile_info {
    margin-top: 40px;
    margin-bottom: 20px;
  }

  #profile_login {
    margin-bottom: 20px;
  }

  #profile_pageURL {
    margin-bottom: 20px;
  }
  
  .interaction_table {
    text-align: center;
  }
  </style>
  <script src="scripts/jquery-1.8.1.min.js"></script>
  <script language="javascript" type="text/javascript">
      $(document).ready(function() {
        $('.interactContainers').hide();
      }); //end ready
      
      var followRequestURL = "profile.php";
      function addFollow(a, b) {
          $.post(followRequestURL,{ followID: a, followRequest: b } ,
          function(data) {
            $("#add_follow").html(data).show().fadeOut(3000);
            $(".hideThis").hide();
          }); //end post
      }
      
      function toggleInteractContainers(x) {
        if ($('#'+x).is(":hidden")) {
          $('#'+x).slideDown(200);
        } else {
          $('#'+x).hide();
        }
      }
      
      function sendPM() {
        var formData = $('#pmForm').serialize();
	    var url = "profile.php";
	    if ($("#pmSubject").val() == "") {
	      $("#interactionResults").html('<img src="images/round_error.png" alt="Error" width="31" height="30" /> &nbsp; Please type a subject.').show().fadeOut(6000);
	    } else if ($("#pmTextArea").val() == "") { 
	      $("#interactionResults").html('<img src="images/round_error.png" alt="Error" width="31" height="30" /> &nbsp; Please type in your message.').show().fadeOut(6000);
	    } else {
	      $("#pmFormProcessGif").show();
	        $.post(url, formData, function(data) {
	          $('#private_message').slideUp('fast');
	          $('#interactionResults').html(data).show().fadeOut(3000);
	          document.pmForm.pmTextArea.value='';
	          document.pmForm.pmSubject.value='';
	          $('#pmFormProcessGif').hide();
	        }); // end post
	    }
      }
  </script>
</head>
<body>
  <div id="content_wrapper">
    <div id="left_side_container">
      <div id="profile_picture">
        <table class="profile_picture0">
  	      <tr>
  	        <td class="profile_picture1">
  	          <?php echo isset($username) ? $username : ''; ?>
  	        <td>
  	      </tr>
  	      <tr> 
  	        <td class="profile_picture2">
  	          <?php echo isset($userPic) ? "<img src=\"$userPic\" width=\"150px\" />" : ''; ?>
  	        </td>
  	      </tr>
  	  	</table>
  	  </div><!--end profile_picture-->
  	  
  	  <div id="profile_info">
  	 	<table class="profile_info0">
  	      <tr>
  	        <td class="profile_info1">
  	          <?php echo isset($gender) ? $gender == "f" ? "Female" : "Male" : ''; ?>
  	        </td>
  	      </tr>
  	      <tr>
  	        <td class="profile_info3">
  	          <?php echo isset($city) ? $city != '' ? $city : '' : ''; ?>
  	        </td>
  	      </tr>
  	      <tr>
  	        <td class="profile_info4">
  	          <?php echo isset($state) ? $state != '' ? $state : '' : ''; ?>
  	        </td>
  	      </tr>
  	      <tr>
  	        <td class="profile_info5">
  	          <?php echo isset($country) ? $country != '' ? $country : '' : ''; ?>
  	        </td>
  	      </tr>
  	 	</table>
  	  </div><!--end profile_info-->
  	  
  	  <div id="profile_login">
  	 	<table class="profile_last_log0">
  	 	  <tr>
  	 	    <td class="profile_last_log1">
  	  		  Last Login:
  	  	    <td>
  		  </tr>
  		  <tr>
  		    <td class="profile_last_log2">
  	  		  <?php echo isset($lastLogDate) ? $lastLogDate : ''; ?>
  	  	    <td>
  	  	  </tr>
  	  	</table>
  	 </div><!--end profile_login-->
  	 
  	 <?php if(isset($website) ) {
  	   if ($website != "") { ?>
  	   <div id="profile_pageURL">
  	      <table class="profile_pageURL0">
  	        <tr>
  	          <td class="profile_pageURL0">
  	            Your Page URL:
  	          </td>
  	        </tr>
  	        <tr>
  	          <td class="profile_pageURL1">
  	            <?php echo $website; ?>
  	          </td>
  	        </tr>
  	      </table>
  	  	</div><!-- end profile_pageURL-->
  	  <?php } } ?>
  	 		
    </div><!--end left_side_container-->
  
    <div id="right_side_container">
      <div id="profile_about">About me: <?php echo isset($bioBody) ? $bioBody : ''; ?></div>
      <div id="add_follow"></div>
      <div id="profile_feed">
        <?php echo isset($interactionBox) ? $interactionBox : ''; ?>
      	      
        <div id="interactionResults" style="font-size:15px; padding:10px;"></div>
       
        <?php if (isset($profileID)) {
          if ($profileID != $id) { ?>
        <div id="private_message" class="interactContainers" >
      	  <form action="javascript:sendPM();" name="pmForm" id="pmForm" method="post">
      	    Sending Private Message To <strong><em><?php echo $username; ?></em></strong><br /><br />
      	    Subject: 
      	    <input name="pmSubject" id="pmSubject" type="text" maxlength="64" />
      	    Message:
      	    <textarea name="pmTextArea" id="pmTextArea" rows="8"></textarea>
      	    <input name="pmRecID" id="pmRecID" type="hidden" value="<?php echo $profileID; ?>" />
            <span id="PMStatus" style="color:#F00;"></span><br />
            <input name="pmSubit" type="submit" value="Submit" /> or <a href="#" onclick="return false" onmousedown="javascript:toggleInteractContainers('private_message');">Close</a>
            <span id="pmFormProcessGif" style="display:none;"><img src="images/loading.gif" width="28" height="10" alt="Loading" /></span>
      	   </form>
         </div><!--end div private_message-->
       <?php } } ?>
      	 Feed
        <?php echo isset($commentDisplayList) ? $commentDisplayList : ''; ?>
      </div><!--end profile_feed-->
    </div><!--end right_side_container-->
  </div><!--end content_wrapper-->
</body>
</html>