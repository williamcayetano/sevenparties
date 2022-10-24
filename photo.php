<?php
  include_once("scripts/connectToMySQL.php");
  include_once("scripts/includeFunctions.php");
  
  if (isset($_SESSION['idx'])) {
      $id = $logOptions_id;
  }
  $id= 2;
  
  if (isset($_GET['id'])) {
    $photoID = preg_replace('#[^0-9]#', '', $_GET['id']);
    $targetPath;
    $postString;
    $comments;
    $starDiv;
    $commentDisplayList = '';
    
    //used to identify anonymous people who are not logged in, for photo views
    $ipaddress = getenv('REMOTE_ADDR');
     
    $sqlPhotoGet = mysqli_query($link, "SELECT * FROM photos WHERE id='$photoID' LIMIT 1") or die (mysqli_error($link));
    $photoCheck = mysqli_num_rows($sqlPhotoGet);
    if ($photoCheck == 0) {
       $errorMsg = "Hmmm, no photo here";
       exit;
    } else {
       while($row = mysqli_fetch_array($sqlPhotoGet)) {
         $eventID = $row['location_id'];
         $targetPath = 'events/' . $eventID . '/maxsized_' . $row['photo_name'];
         $backupPath = 'events/' . $eventID . '/resized_' . $row['photo_name'];
         
         //get username
         $userID = $row['user_id'];
         $username = getUsername($userID);
         
         //setScore
         $score = $row['score'];
         $starDiv = scoreDiv($score);
         
         $unixPostedStamp = strtotime($row['post_date']);
         $formatPostTime = date("D M j, Y", $unixPostedStamp);
         $postString = '<br />Posted ' . $formatPostTime . ' by <a href="profile.php?id=' . $userID . '">' . $username . '</a>';
          
         //get event title
         $sqlEvent = mysqli_query($link, "SELECT title, type FROM locations WHERE id='$eventID' LIMIT 1");
         while ($row3 = mysqli_fetch_array($sqlEvent)) { $eventTitle = $row3["title"]; $eventType = $row3['type']; }
         
         $title;
         isset($eventTitle) ? $title = $eventTitle : $title = $eventType; 
          
         //get comments
         $sqlComments = mysqli_query($link, "SELECT * FROM photo_comments WHERE photo_id='$photoID' ORDER BY post_date ASC");
         $commentNumber = mysqli_num_rows($sqlComments);
         
    	 while($row = mysqli_fetch_array($sqlComments)) {
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
          
         }
       }//end while
     }//end else
     
     
     if (isset($id)) {
       $sqlPhotoView = mysqli_query($link, "SELECT * FROM photo_views WHERE photo_id='$photoID' AND user_id='$id' LIMIT 1");
       $sqlViewRows = mysqli_num_rows($sqlPhotoView);
       
       if ($sqlViewRows == 0) {
         $sqlInsertView = mysqli_query($link, "INSERT INTO photo_views (photo_id, user_id, ipaddress, post_date) VALUES ('$photoID', '$id', '$ipaddress', now())");
       }
     } else {
       $userID = 00;
       $sqlPhotoView = mysqli_query($link, "SELECT * FROM photo_views WHERE ipaddress='$ipaddress' LIMIT 1");
       $sqlViewRows = mysqli_num_rows($sqlPhotoView);
       
       if ($sqlViewRows == 0) {
         $sqlInsertView = mysqli_query($link, "INSERT INTO photo_views (photo_id, user_id, ipaddress, post_date) VALUES ('$photoID', '$userID', '$ipaddress', now())");
       }
     }
  } else if (isset($_POST['photo']) && isset($id)) {
    $postID = preg_replace('#[^0-9]#', '', $_POST['photo']);
    $comment = preg_replace( '/\s+/', ' ', $_POST["comment"]);//strip extra whitespace
    
    if ($comment != "") {
      $comment = clean($comment);
      $sqlInsertComment = mysqli_query($link, "INSERT INTO photo_comments (photo_id, user_id, comment, post_date) VALUES ('$postID', '$id', '$comment', now())") or die (mysqli_error($link));
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
      echo "You need to type something";
    }
  } else if (isset($_POST['starRating']) && isset($id)) {
    $rating = preg_replace('#[^0-9.]#', '', $_POST['starRating']);
    $photoID = preg_replace('#[^0-9]#', '', $_POST['photoID']);
    $newScore;
    
    //increment rating because stars are zero based
    ++$rating;
    
    if ($rating <= 5) {
      //get photo score
  	  $sqlPhotoGet = mysqli_query($link, "SELECT score FROM photos WHERE id='$photoID' LIMIT 1") or die (mysqli_error($link));
  	  while($row = mysqli_fetch_array($sqlPhotoGet)) { $photoScore = $row['score']; }
  	
  	  //get total number of votes
  	  $sqlVotesGet = mysqli_query($link, "SELECT score FROM photo_votes WHERE photo_id='$photoID'") or die (mysqli_error($link));
  	  $totalVoteNumber = mysqli_num_rows($sqlVotesGet);
  	
  	  //has user voted already?
  	  $sqlVoteGet = mysqli_query($link, "SELECT score FROM photo_votes WHERE photo_id='$photoID' AND user_id='$id' LIMIT 1") or die (mysqli_error($link));
  	  $voteCheck = mysqli_num_rows($sqlVoteGet);
  	  if ($voteCheck == 0) {//user hasn't voted yet
  	    $sqlScorePost =  mysqli_query($link, "INSERT INTO photo_votes (photo_id, user_id, score, post_date) VALUES ('$photoID', '$id', '$rating', now())") or die (mysqli_error($link)); 
  	    $totalVoteNumber++;
  	    $newScore = ($photoScore + $rating) / $totalVoteNumber;
  	  
  	  } else {//user must have voted already
  	    while ($row = mysqli_fetch_array($sqlVoteGet)) { $userScore = $row['score']; }
  	    $sqlScoreUpdate = mysqli_query($link, "UPDATE photo_votes SET score='$rating', post_date=now() WHERE photo_id='$photoID' AND user_id='$id'") or die (mysqli_error($link));
  	  
  	    if ($totalVoteNumber == 1) { //user is only one that voted for it
  	      $newScore = $rating;
  	    } else {
  	      //reverse score
  	      $origSum = $photoScore * $totalVoteNumber; 
  	      $alteredSum = $origSum - $userScore;
  	      $newScore = ($alteredSum + $rating) / $totalVoteNumber;
  	    }
  	  
  	  }
  	
  	  $sqlPhotoUpdate = mysqli_query($link, "UPDATE photos SET score='$newScore' WHERE id='$photoID'") or die (mysqli_error($link));
  	
      $starDiv = scoreDiv($newScore);
      echo $starDiv;
      exit();
    } else {
      echo "You're rating needs to be between 1 and 5 stars";
    }
  }

?>

<?php include_once("inc/head.inc.php"); ?>
  <style>
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
  <script language="javascript" type="text/javascript">
    var postNumber = 0;
    //these will be populated on page load
    var star0;
    var star1;
    var star2;
    var star3;
    var star4;
    
    
    function sendComment() {
      var formData = $('#commentForm').serialize();
	  var url = "photo.php";
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
    
    function starPick(star, att) {
      console.log(star);
      star0, star1, star2, star3, star4;
      switch(star) {
        case 0:
          var src = $('#star_0').attr('src').replace(att, "images/full_star.png");
          $('#star_0').attr("src", src);
          break;
        case 1:
          var src0 = $('#star_0').attr('src').replace(att, "images/full_star.png");
          $('#star_0').attr("src", src0);
          var src = $('#star_1').attr('src').replace(att, "images/full_star.png");
          $('#star_1').attr("src", src);
          break;
        case 2:
          var src0 = $('#star_0').attr('src').replace(att, "images/full_star.png");
          $('#star_0').attr("src", src0);
          var src1 = $('#star_1').attr('src').replace(att, "images/full_star.png");
          $('#star_1').attr("src", src1);
          var src = $('#star_2').attr('src').replace(att, "images/full_star.png");
          $('#star_2').attr("src", src);
          break;
        case 3:
          var src0 = $('#star_0').attr('src').replace(att, "images/full_star.png");
          $('#star_0').attr("src", src0);
          var src1 = $('#star_1').attr('src').replace(att, "images/full_star.png");
          $('#star_1').attr("src", src1);
          var src2 = $('#star_2').attr('src').replace(att, "images/full_star.png");
          $('#star_2').attr("src", src2);
          var src = $('#star_3').attr('src').replace(att, "images/full_star.png");
          $('#star_3').attr("src", src);
          break;
        case 4:
          var src0 = $('#star_0').attr('src').replace(att, "images/full_star.png");
          $('#star_0').attr("src", src0);
          var src1 = $('#star_1').attr('src').replace(att, "images/full_star.png");
          $('#star_1').attr("src", src1);
          var src2 = $('#star_2').attr('src').replace(att, "images/full_star.png");
          $('#star_2').attr("src", src2);
          var src3 = $('#star_3').attr('src').replace(att, "images/full_star.png");
          $('#star_3').attr("src", src3);
          var src = $('#star_4').attr('src').replace(att, "images/full_star.png");
          $('#star_4').attr("src", src);
          break;
        default:
          var src0 = $('#star_0').attr('src').replace("images/full_star.png", star0);
          $('#star_0').attr("src", src0);
          console.log("Star 0 att: " + att);
          var src1 = $('#star_1').attr('src').replace("images/full_star.png", star1);
          $('#star_1').attr("src", src1);
          console.log("Star 1 att: " + att);
          var src2 = $('#star_2').attr('src').replace("images/full_star.png", star2);
          $('#star_2').attr("src", src2);
          console.log("Star 2 att: " + att);
          var src3 = $('#star_3').attr('src').replace("images/full_star.png", star3);
          $('#star_3').attr("src", src3);
          console.log("Star 3 att: " + att);
          var src4 = $('#star_4').attr('src').replace("images/full_star.png", star4);
          $('#star_4').attr("src", src4);
          console.log("Star 4 att: " + att);
          break;
      }
    }
    
    function starClick(star) {
       var url = "photo.php";
       var getID = $('#photo').attr('value');
      
      $.post(url, { starRating: star, photoID: getID }, function(data) {
        //$('#newCommentDisplay').html(data).show();
        $('#stars').replaceWith(data);
      });//end post
    }
    
    $(document).ready(function(){
      star0 = $('#star_0').attr('src');
      star1 = $('#star_1').attr('src');
      star2 = $('#star_2').attr('src');
      star3 = $('#star_3').attr('src');
      star4 = $('#star_4').attr('src');
     $('#star_0').hover( function () { starPick(0, star0); }, function () { starPick(99, star0); });
     $('#star_1').hover( function () { starPick(1, star1); }, function () { starPick(99, star1); });
     $('#star_2').hover( function () { starPick(2, star2); }, function () { starPick(99, star2); });
     $('#star_3').hover( function () { starPick(3, star3); }, function () { starPick(99, star3); });
     $('#star_4').hover( function () { starPick(4, star4); }, function () { starPick(99, star4); });
     $("#star_0").click( function() { starClick(0); });
     $("#star_1").click( function() { starClick(1); });
     $("#star_2").click( function() { starClick(2); });
     $("#star_3").click( function() { starClick(3); });
     $("#star_4").click( function() { starClick(4); });
    });

  </script>
</head>
<body>
  <div id="main_content">
    <?php if (isset($photoID)) { 
      echo '<h2>' . $title . '</h2>';
      echo $starDiv . '<br />'; ?>
    <img id="main_image" src="<?php echo  file_exists($targetPath) ? $targetPath : $backupPath; ?>"/> 
    <?php echo $postString; ?>
    <?php if (isset($id)) { ?>
    <div id="interactionResults" style="font-size:15px; padding:10px;"></div>
    <form action="javascript:sendComment();" method="post" name="commentForm" id="commentForm">
      <textarea name="comment" cols="35" rows="5" id="comment"></textarea><br />
      <input type="hidden" name="photo" id="photo" value="<?php echo $photoID; ?>" />
      <input type="submit" name="button" id="button" value="Submit"/>
      <span id="pmFormProcessGif" style="display:none;"><img src="images/loading.gif" width="28" height="10" alt="Loading" /></span>
    </form>
    <div class="commentDisplayList">
      <div id="newCommentDisplay2"></div>
      <div id="newCommentDisplay"></div>
    </div>
  <?php } ?>
  <div class="commentDisplayList"><?php echo isset($commentDisplayList)? $commentDisplayList: ''; } ?></div>
  </div>
</body>
</html>
