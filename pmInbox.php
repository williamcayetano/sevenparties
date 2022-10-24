<?php
//fix button
  include_once("scripts/connectToMySQL.php");
  include_once("scripts/includeFunctions.php");
  /*if (isset($_SESSION['idx'])) {
      $id = $logOptions_id;
  } else {
    header("location: index.php");
  }*/
  $id= 2;
  
  if (isset($_POST['deleteBtn'])) {
    foreach ($_POST as $key => $value) {
      $value = urlencode(stripslashes($value));
      if ($key != "deleteBtn") {
        $sql = mysqli_query($link, "UPDATE private_messages SET recipient_delete='y' WHERE id='$value' AND to_id='$id' LIMIT 1");
      }
    }
  } else if (isset($_POST['messageID'])) {
    $messageID = preg_replace('#[^0-9]#', '', $_POST['messageID']);
    /*// Decode the Session IDX variable and extract the user's ID from it
    $decryptedID = base64_decode($_SESSION['idx']);
    $idArray = explode("p3h9xfn8sq03hs2234", $decryptedID);
    $myID = $idArray[1];
   if ($id != $myID) {
      exit();
    } else {*/
      mysqli_query($link, "UPDATE private_messages SET recipient_opened='y' WHERE id='$messageID' LIMIT 1");
    //}
  } else if (isset($_POST['pmTextArea'])) {
    $to = preg_replace('#[^0-9]#', '', $_POST['pm_rec_id']);
    $from = preg_replace('#[^0-9]#', '', $_POST['pm_sender_id']);
    $sub = clean($_POST['pmSubject']);
    $msg = clean($_POST['pmTextArea']);
    
    $sub = 'Re: ' . $sub;
    
    if (empty($to) || empty($from) || empty($sub) || empty($msg)) { 
      echo '<img src="./images/round_error.png" alt="Error" width="15" height="15" /> &nbsp;  Missing Data to continue';
	  exit();
    } else {
      //insert data into table 
      if (!mysqli_query($link, "INSERT INTO private_messages (to_id, from_id, post_date, subject, message) VALUES ('$to', '$from', now(), '$sub', '$msg')")) { 
	      echo '<img src="./images/round_error.png" alt="Error" width="15" height="15" /> &nbsp;  Could not send message! Please try again.';
	      exit();
      } else { 
        echo '<img src="./images/round_success.png" alt="Success" width="15" height="15" /> &nbsp;&nbsp;&nbsp;<strong>Message sent successfully</strong>';
		exit();
	  }
    }
  
  }

?>

<?php include_once("inc/head.inc.php"); ?>
  <style>
    .msgDefault {font-weight:bold;cursor:pointer;}
	.msgRead {font-weight:100;color:#666;cursor:pointer;}
	.hiddenReply {display:none; width: 680px; height:264px; background-color: #005900; background-repeat:repeat; border: #333 1px solid; top:51px; position:fixed; margin:auto; z-index:50; padding:20px; color:#FFF;}
	.privateStatus {color:#F00; font-size:14px; font-weight:700;}
	.privateFinal {display:none; width:652px; background-color:#005900; border:#666 1px solid; top:51px; position:fixed; margin:auto; z-index:50; padding:40px; color:#FFF; font-size:16px;}
	.pm_inbox_checkbox{ width: 4%; }
	.pm_inbox_from { width: 20%; }
	.pm_inbox_subject { width: 58%; }
	.pm_inbox_date { width: 18%; }
	#pm_inbox_delete { margin-left: 7px; } 
	.hiddenDiv{ display:none }
	#pmFormProcessGif{ display:none }
	#replyBtn{ margin-left:-20px; margin-top:-20px; }
  </style>
  <script src="scripts/jquery-1.8.1.min.js"></script>
  <script>
    $(document).ready(function() {
	  $(".toggle").click(function () { 
        if ($(this).next().is(":hidden")) {
          $(".hiddenDiv").hide();
    	  $(this).next().slideDown("fast"); 
  	  	} else { 
    	  $(this).next().hide(); 
  	  	} 
	  }); //end toggle
    }); //end ready
    
    function toggleChecks(field) {
      if (document.myForm.toggleAll.checked == true) {
        for (i = 0; i < field.length; i++) {
          field[i].checked = true;
        }
      } else {
        for (i = 0; i < field.length; i++) {
          field[i].checked = false;
        }
      }
    }
    
    function markAsRead(msgID) {
      $.post("pmInbox.php",{ messageID:msgID } ,
      function(data) {
        $('#subj_line_'+msgID).addClass('msgRead');
      });
    }
     
     function toggleReplyBox(subject, senderid, recName, recID, replyWipit) {
       $("#subjectShow").text(subject);
       $("#recipientShow").text(recName);
       document.replyForm.pmSubject.value = subject;
       document.replyForm.pm_sender_id.value = senderid;
       document.replyForm.pm_rec_name.value = recName;
       document.replyForm.pm_rec_id.value = recID;
       document.replyForm.replyBtn.value = "Send reply to "+recName;
       if ($('#replyBox').is(":hidden")) {
          $('#replyBox').fadeIn(1000);
       } else {
          $('#replyBox').hide();
       }
     }
     
     function processReply() {
       var formData = $('#replyForm').serialize();
       var url = "pmInbox.php";
       if ($("#pmTextArea").val() == "") {
         $("#pmStatus").text("Please type in your message.").show().fadeOut(6000);
       } else {
         $("#pmFormProcessGif").show();
         $.post(url, formData,  
         function(data) {
           document.replyForm.pmTextArea.value = "";
           $("#pmFormProcessGif").hide();
           $("#replyBox").slideUp("fast");
           $("#pmFinal").html("&nbsp; &nbsp;"+data).show().fadeOut(8000);
         });
       }
     }
  </script>
</head>
<body>
  <div id="content_pm_inbox">
    <h2 id="pm_inbox_head">Your Private Messages</h2>
    <form name="myForm" action="pmInbox.php" method="POST" enctype="multipart/form-data">
      <div id="pm_inbox_delete">
        <img src="images/crookedArrow.png" />
        <input type="submit" name="deleteBtn" value="Delete" />
      </div><!--end pm_inbox_delete-->
      <table class="pm_inbox_header">
        <tr>
          <td class="pm_inbox_checkbox">
            <input name="toggleAll" id="toggleAll" type="checkbox" onclick="toggleChecks(document.myForm.cb)" />
          </td>
          <td class="pm_inbox_from">
            From
          </td>
          <td class="pm_inbox_subject">
            Subject
          </td>
          <td class="pm_inbox_date">
            Date
          </td> 
        </tr>
      </table>
<?php
  $sqlGetMessages = mysqli_query($link, "SELECT * FROM private_messages WHERE to_id='$id' AND recipient_delete='n' ORDER BY id DESC LIMIT 100");
  
  while ($row = mysqli_fetch_array($sqlGetMessages)) {
    $date = strftime("%b %d, %Y", strtotime($row['post_date']));
    if($row['recipient_opened'] == "y") {
      $textWeight = 'msgDefault';
    } else {
      $textWeight = 'msgRead';
    }
    $senderID = $row['from_id'];
    $senderName = getUsername($senderID);
  
?> 
      <table class="pm_inbox_message">
        <tr>
          <td class="pm_inbox_checkbox">
            <input type="checkbox" name="cb<?php echo $row['id']; ?>" id="cb" value="<?php echo $row['id']; ?>" />
          </td>
          <td class="pm_inbox_from"> 
            <a href="profile.php?id=<?php echo $senderID; ?>"><?php echo $senderName; ?></a>
          </td>
          <td class="pm_inbox_subject">
            <span class="toggle">
              <a class="<?php echo $textWeight; ?>" id="subj_line_<?php echo $row['id']; ?>" onclick="markAsRead(<?php echo $row['id']; ?>)"><?php echo htmlkarakter($row['subject']); ?></a>
            </span>
            <div class="hiddenDiv"> <br />
              <?php echo htmlkarakter($row['message']); ?>
              <br /><br /><a href="javascript:toggleReplyBox('<?php echo $row['subject']; ?>', '<?php echo $id; ?>','<?php echo $senderName; ?>','<?php echo $senderID; ?>')">REPLY</a><br />
            </div>
          </td>
          <td class="pm_inbox_date">
            <span style="font-size:10px;"><?php echo $date; ?></span>
          </td>
        </tr>
      </table>
    <hr class="horizontal" />
<?php
  } //Close Main while loop
?>
    </form>
    <div id="replyBox" class="hiddenReply">
      <div align="right">
        <a href="javascript:toggleReplyBox('close')"><font color="#00CCFF"><strong>CLOSE</strong></font></a>
      </div>
      <h2>Replying to <span style="color:#ABE3FE;" id="recipientShow"></span><h2>
      Re: <strong><span style="color:#ABE3FE;" id="subjectShow"></span></strong> <br>
      <form action="javascript:processReply();" name="replyForm" id="replyForm" method="POST">
        <textarea name="pmTextArea" id="pmTextArea" rows="8" style="width:98%;"></textarea><br />
        <input type="hidden" name="pmSubject" id="pmSubject" />
		<input type="hidden" name="pm_rec_id" id="pm_rec_id" />
		<input type="hidden" name="pm_rec_name" id="pm_rec_name" />
		<input type="hidden" name="pm_sender_id" id="pm_sender_id" />
		<br />
		<input name="replyBtn" id="replyBtn" type="button" onclick="javascript:processReply()" /> &nbsp;&nbsp;&nbsp; 
		<span id="pmFormProcessGif"><img src="images/loading.gif" width="28" height="10" alt="Loading" /></span>
		<div id="pmStatus" class="privateStatus">&nbsp;</div><!--end pmStatus-->
      </form>
    </div><!--end replyBox-->
    <div id="pmFinal" class="privateFinal"></div><!--end pmFinal-->
  </div><!--end content_pm_inbox-->
</body>
</html>