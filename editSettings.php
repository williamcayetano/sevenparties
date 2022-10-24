<?php
  include_once("scripts/connectToMySQL.php");
  include_once("scripts/includeFunctions.php");
  
  /*if (isset($_SESSION['idx'])) {
      $id = $logOptions_id;
  } else {
    header('location: index.php');
  }*/
  $id= 2;
  $errorMsg = '';
  $rsvpCheck = '';
  
  $sqlGetRsvp = mysqli_query($link, "SELECT rsvp_show FROM members WHERE id='$id' LIMIT 1");
  while ($row = mysqli_fetch_array($sqlGetRsvp)) {
    $rsvpShow = $row['rsvp_show'];
    if ($rsvpShow == 'y') {
      $rsvpCheck = 'checked';
    }
  }
  
  if (isset($_POST['submit']) && isset($id)) {
    $currentPass = $_POST['currentPass'];
	$newPass1 = $_POST['newPass1'];
	$newPass2 = $_POST['newPass2'];
	
	if ($new_pass1 != $new_pass2) {
	  $errorMsg = '<span class="red">ERROR:</span> The confirmation password you provided didn\'t match your new pasword';
	} else {
	
	  $currentPass = clean($currentPass);
      $newPass = clean($newPass);
      $hashCurPass = md5($currentPass);
      $hashNewPass = md5($newPass);
      
      $sql = mysqli_query($link, "SELECT * FROM members WHERE id='$id' AND password='$hashCurPass'");
      $passCheckNum = mysqli_num_rows($sql);
      
      if ($passCheckNum > 0) {
        $sqlUpdate = mysqli_query($link, "UPDATE members SET password='$hashNewPass' WHERE id='$id'");
        $errorMsg = '* Your password has been successfully changed.
        <br /> Click <a href="profile.php?id=' . $id . '">here</a> to go back to your profile';
      } else {
        $errorMsg = '<span class="red">ERROR:</span> Unsuccessful. Your current password did not match your profile.';
      }
    }
  } else if (isset($_POST['submit1']) && isset($id)) { 
    if($_POST['rsvp'] == 'Yes') {
    
    } else {
    
    }
  } else if (isset($_POST['submit2']) && isset($id)) {
  
  }

?>

<?php include_once("inc/head.inc.php"); ?>
  <style>
  </style>
  <script>
  </script>
<head>
<body>
  <div id="content_wrapper">
    
    <h2>Edit Your Account Settings Here</h2>
    <?php echo $errorMsg; ?>
    <hr class="horizontal" />
    <table class="settings0">
      <form action="editSettings.php" method="post">
      <tr>
        <td class="settings1">
          <strong>Change Your Password Here</strong>
        </td>
      </tr>
      <tr>
        <td class="settings2">
          Your Current Password:
        </td>
        <td class="settings3">
          <input name="currentPass" type="password" id="currentPass" size="28" maxlength="32" />
        </td>
      </tr>
      <tr>
        <td class="settings4">
          Create New Password:
        </td>
        <td class="settings5">
          <input name="newPass1" type="password" id="newPass1" size="28" maxlength="32" />
        </td>
      </tr>
      <tr>
        <td class="settings6">
          Confirm New Password:
        </td>
        <td class="settings7">
          <input name="newPass2" type="password" id="newPass2" size="28" maxlength="32" />
        </td>
      </tr>
      <tr>
        <td class="settings8">
          <input name="submit" type="submit" value="Change Password" />
        </td>
      </tr>
      </form>
    </table>
    <br /><br />
    <hr class="horizontal" />
    <form action="editSettings.php" method="post">
      <strong>Make RSVP's visible</strong><br />
      <input type="checkbox" name="rsvp" value="Yes" <?php echo $rsvpCheck; ?>> &nbsp;&nbsp;Make RSVP's visible<br />
      <input name="submit1" type="submit" value="Change RSVP's" />
    </form>
    <br /><br />
    <hr class="horizontal" />
    <table class="settings_delete0">
      <form action="editSettings.php" method="post">
      <tr>
        <td class="settings_delete1">
          <strong>Delete Your Account</strong>
        </td>
      </tr>
      <tr>
        <td class="settings_delete2">
          Please enter your current password to proceed with account deletion.
        </td>
      </tr>
      <tr>
        <td class="settings_delete3">
          <input name="delAcctPass" type="password" id="delAcctPass" size="28" maxlength="32" />
        </td>
      </tr>
      <tr>
        <td class="settings_delete4">
          <input name="submit2" type="submit" value="Proceed" />
        </td>
      </tr>
      </form>
    </table>
  </div><!--end content_wrapper-->
</body>
</html>