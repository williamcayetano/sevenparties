<?php
  //session_start();
  
  require("post2.php");
  require("get2.php");
  include_once("scripts/includeFunctions.php");
  
  if (isset($_POST['command'])) {
    switch($_POST['command']) {
      case "userLoc":
        userLoc($_POST['latitude'], $_POST['longitude']);
        break;
        
      case "updateEvent"://used to rsvp, favorite, or cancel on an event
        updateEvent($_POST['update'], $_POST['event'], $_POST['whos'], $_POST['profileID']);
        break;
        
      case "removeRsvp"://if creator is removing user from event in UserInfoList
        removeRsvp($_POST['delUserID'], $_POST['eventID']);
        break;
        
      case "comment":
        comment($_POST['id'], $_POST['photoFlag'], $_POST['comment']);
        break;
      
      case "register":
        register($_POST['email'], $_POST['username'], $_POST['pass1'], $_POST['gender']);
        break;
      
      case "profilePhoto":
        profilePhoto($_POST['photoID'], $_FILES['photo']);
        break;
        
      case "login":
        login($_POST['username'], $_POST['pass1']);
        break;
    
      case "event": //post event
        event($_POST['type'], $_POST['time'], $_POST['street'], $_POST['city'], $_POST['state'], $_POST['country'], $_POST['age'], $_POST['admission'], $_POST['rsvp'], $_POST['title'], $_POST['description'], $_POST['latitude'], $_POST['longitude']);
        break;
      
      case "password"://change password
        password($_POST['currentPass'], $_POST['newPass'], $_POST['retypePass'], $_POST['profileID']);
        break;
        
      case "delete"://delete account
        delete($_POST['deletePass'], $_POST['profileID']);
        break;
        
      case "rsvpEdit":
        rsvpEdit($_POST['rsvpShow'], $_POST['profileID']);
        break;
        
	  case "logout":
	    logout();
	    break;
        
	  case "delPastEvent"://remove event in user's past events, basically putting the rsvp or fav's active to n, and location's in_user_past to n
	    delPastEvent($_POST['delEventID'], $_POST['delDBType'], $_POST['delProfileID']);
	    break;
      
      case "photo":
        photo($_POST['eventID'], $_FILES['photo']);
        break;
        
      case "rate":
        rate($_POST['rating'], $_POST['photoID']);
        break;
        
      case "follow":
        follow($_POST['profileID'], $_POST['followFlag']);
        break;
        
      case "message":
        message($_POST['profileID'], $_POST['subject'], $_POST['message']);
        break;
        
      case "delMessage":
        delMessage($_POST['delMessageID'], $_POST['delMessageType'], $_POST['delProfileID']);
        break;
      
      case "messageOpened":
        opened($_POST['messageID'], $_POST['messageType'], $_POST['profileID']);
        break;
        
      case "setProfile":
	    setProfile($_POST['profileID']);
	    break;
      
      default:
        sendResponse(400, 'Invalid request');
        exit;
    }
  } else if (isset($_GET['command'])) {
    switch($_GET['command']) {
      case "locations"://used in map view, on initial loading of app
        locations($_GET['type'], $_GET['time'], $_GET['city'], $_GET['state'], $_GET['country'], $_GET['age'], $_GET['admithi']);
        break;
        
      case "popular":
        popular($_GET['city'], $_GET['state'], $_GET['country'], $_GET['pastFut']);
        break;
    
      case "eventFromDetailed"://coming from EventViewController, when user taps event in detailed photo controller
        eventFromDetailed($_GET['eventID']);
        break;
        
      case "eventFromMap"://used in EventViewController from Map View to see if user logged in or not and fav'd rsvp'd event
        eventFromMap($_GET['userEventID']);
        break;
        
      case "users"://used in UserInfoList to populate RSVP's
        users($_GET['users']);
        break;
        
      case "comments":
        comments($_GET['id'], $_GET['last'], $_GET['photoFlag']);
        break;

	  case "profile":
	    profile(isset($_GET['id']) ? $_GET['id'] : NULL);
	    break;
	
	  case "events"://used to populate user's event list in profile
	    events($_GET['profileID']);
	    break;
	    
	  case "photos":
	    photos($_GET['id'], $_GET['rangeStart'], $_GET['eventFlag']);
	    break;
	    
	  case "detailedPhoto":
        detailedPhoto($_GET['photoID']);
        break;
        
      case "followers":
        followers($_GET['profileID']);
        break;
        
      case "messages":
        messages($_GET['profileID']);
        break;
	    
	  case "editProfile":
	    editProfile($_GET['profileID']);
	    break;
	  
      default:
        sendResponse(400, 'Invalid request');
        exit;
    }
  }
 
//exit; 
?>