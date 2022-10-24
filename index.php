<?php
  $subject = '';
  $email = '';
  $comment_box = '';
  $errorMsg = '';
  
  if (isset($_POST['button'])) {
    $subject = $_POST['subject'];
    $email =  $_POST['email'];
    $comment_box = $_POST['comment_box'];
    
    if ((!$subject) || (!$email) || (!$comment_box)) {
      $errorMsg = "ERROR: You did not submit the following required information:< br /><br />";
    	
      if(!$subject) {
        $errorMsg .= ' * Subject<br />';
      }
      if(!$email) {
        $errorMsg .= ' * Email<br />';
      }
      if(!$comment_box) {
        $errorMsg .= ' * Comments<br />';
      }
    } else if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  	  $errorMsg = "This email address doesn't appear to be valid.";
  	} else {
  	  $to = "internetgrind@gmail.com";
  	  $emailSubject = $email . ' - ' . $subject;
 	  if (mail($to, $emailSubject, $comment_box)) {
   		$errorMsg = "Message successfully sent!";
  		$subject = '';
  		$email = '';
  		$comment_box = '';
  	  } else {
   		$errorMsg = "Message delivery failed...";
  	  }
  	}
  } 
?>

<?php include_once("inc/head.inc.php"); ?>
  <style>
    .contact_table {
      padding: 20px;
      margin-left: auto;
      margin-right: auto;
      border: 1px solid white;
    }
    #subject {
      margin-left: 30px;
    } 
    #email {
      margin-left: 40px;
    }
    #comment_box {
      margin-left: 9px;
    }
  </style>
</head>
<body>
  <div id="main_body">
    <img src="images/logo.png" height="490" width="460" class="logo_image" />
    <h2>This is the home of 7Parties. Get it on the IOS App Store today.</h2>
    <table class="contact_table">
      <form action="index.php" method="post" name="contactForm" id="contactForm">
        <tr> 
          <td class="contact_error">
            <?php echo $errorMsg; ?>
          <td>
        </tr>
        <tr>
          <td class="contact_subject">
            Subject:<span class="red">*</span><input type="text" value="<?php print "$subject"; ?>" name="subject" id="subject" size="40" />
          </td>
        </tr>
        <tr>
          <td class="contact_email">
            Email:<span class="red">*</span><input type="text" value="<?php print "$email"; ?>" name="email" id="email" size="40" />
          </td>
        </tr>
        <tr>
          <td class="contact_comments">
            Comments:<span class="red">*</span><textarea value="<?php print "$comment_box"; ?>" name="comment_box" cols="50" rows="5" id="comment_box"></textarea>
          </td>
        </tr>
        <tr>
          <td class="contact_submit">
            <br /><input type="submit" name="button" id="button" value="Send Mail"/>
          </td>
        </tr>
      </form>
    </table>
  </div>
</body>
</html>