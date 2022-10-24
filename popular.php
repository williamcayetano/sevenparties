<?php
  include_once("scripts/connectToMySQL.php");
  include_once("scripts/includeFunctions.php");
  $city = '';
  $state = '';
  $country = '';
  $gt = ">";
  $popularList = "";
  $rankingArray = array();
  $stateArray = array();
  $countryArray = array();
  
  //if coming from index.php
  if (isset($_GET['city']) && isset($_GET['state']) && isset($_GET['country'])) {
    $city = urldecode($_GET['city']);
    $state = urldecode($_GET['state']);
    $country = urldecode($_GET['country']);
    //$location = $city . '`' . $state . '`' . $country;
    //$expire = time()+60*60*24*30; //one month (60 sec * 60 min * 24 hours * 30 days)
    //setcookie(location, $location, $expire, '/');
  } /*else if (isset($_COOKIE['location'])) { //coming to this page straight instead of going of coming from index.php
    $kaboom = explode('`', $_COOKIE['location']);
    $city = $kaboom[0];
    $state = $kaboom[1];
    $country = $kaboom[2];
  }*/
  
  if ($country == '') {
    $country = "United States";
  }
  
  //this will at least get most popular parties in United States, if nothing happening in city or state
  $rankingArray = featuredPopular($city, $state, $country, $gt);
  
  //if no city and state, these will not be triggered
  if ($city != '' && count($rankingArray) < 25) {//if there was a city and it didn't amount to 25, let's dig deeper by checking whole state
    $city = '';
    $stateArray = featuredPopular($city, $state, $country, $gt);
    $rankingArray = $rankingArray + $stateArray; //combine arrays putting city first as it's more relevant
  }
    
  if ($state != '' && count($rankingArray) < 25) {//if there was a state and it didn't amount to 25, let's dig deeper by checking country
    $city = '';
    $state = '';
    $countryArray = featuredPopular($city, $state, $country, $gt);
    $rankingArray = $rankingArray + $countryArray;
  }
  
############################Begin Pagination Logic#############################
  $numberRows = count($rankingArray);
  if (isset($_GET['pn'])) {
    $pageNumber = preg_replace('#[^0-9]#', '', $_GET['pn']);
  } else {
    $pageNumber = 1;
  }
  
  $itemsPerPage = 20;

  // Get the value of the last page in the pagination result set; Gets the total number of pages
  $lastPage = ceil($numberRows / $itemsPerPage);
  
  // Be sure URL variable $pageNumber is no lower than page 1 and no higher than $lastpage
  if ($pageNumber < 1) { 
    $pageNumber = 1; 
  } else if ($pageNumber > $lastPage) { 
    $pageNumber = $lastPage; 
  } 
  
  // This creates 5 numbers to click in between the next and back buttons
  $centerPages = ""; // Initialize this variable
  $sub1 = $pageNumber - 1;//value of subtracting 1 page
  $sub2 = $pageNumber - 2;//value of subtracting 2 pages
  $add1 = $pageNumber + 1;//value of adding 1 page
  $add2 = $pageNumber + 2;//value of adding 2 pages
  if ($pageNumber == 1) { //should be no back button because you're already on page 1
	//We use $_SERVER['PHP_SELF'] in case script has to change server environments, the pages won't be hardcoded. It will grap this current scripts name (in this case member_search.php)
	$centerPages .= '&nbsp; <span class="pagNumActive">' . $pageNumber . '</span> &nbsp;'; //ex. page 1 (current page)
	$centerPages .= '&nbsp; <a href="' . $_SERVER['PHP_SELF'] . '?pn=' . $add1 . '">' . $add1 . '</a> &nbsp;'; //ex.page 2
  } else if ($pageNumber == $lastPage) { //should be no forward button because you're already on last page
	$centerPages .= '&nbsp; <a href="' . $_SERVER['PHP_SELF'] . '?pn=' . $sub1 . '">' . $sub1 . '</a> &nbsp;'; //ex. page 29
	$centerPages .= '&nbsp; <span class="pagNumActive">' . $pageNumber . '</span> &nbsp;'; //ex. page 30 (current page)
  } else if ($pageNumber > 2 && $pageNumber < ($lastPage - 1)) { //set how many clickable numbers inbetween next and back buttons
	$centerPages .= '&nbsp; <a href="' . $_SERVER['PHP_SELF'] . '?pn=' . $sub2 . '">' . $sub2 . '</a> &nbsp;'; //ex. page 5
	$centerPages .= '&nbsp; <a href="' . $_SERVER['PHP_SELF'] . '?pn=' . $sub1 . '">' . $sub1 . '</a> &nbsp;'; //ex. page 6
	$centerPages .= '&nbsp; <span class="pagNumActive">' . $pageNumber . '</span> &nbsp;'; //ex. page 7 (current page)
	$centerPages .= '&nbsp; <a href="' . $_SERVER['PHP_SELF'] . '?pn=' . $add1 . '">' . $add1 . '</a> &nbsp;'; //ex. page 8
	$centerPages .= '&nbsp; <a href="' . $_SERVER['PHP_SELF'] . '?pn=' . $add2 . '">' . $add2 . '</a> &nbsp;'; //ex. page 9
  } else if ($pageNumber > 1 && $pageNumber < $lastPage) { //on next to last page, just show last page number after current. one on each side. notice add1
	$centerPages .= '&nbsp; <a href="' . $_SERVER['PHP_SELF'] . '?pn=' . $sub1 . '">' . $sub1 . '</a> &nbsp;'; //ex. page 28
	$centerPages .= '&nbsp; <span class="pagNumActive">' . $pageNumber . '</span> &nbsp;'; //ex. page 29 (current page)
	$centerPages .= '&nbsp; <a href="' . $_SERVER['PHP_SELF'] . '?pn=' . $add1 . '">' . $add1 . '</a> &nbsp;'; //ex. page 30 (last page)
  }
  
  //Ex. LIMIT 20 returns first 20 results; LIMIT 20, 5 returns 5 results(20, 21, 22, 23, 24)
  //subtract 1 since sql counts from 0
  $limit = ($pageNumber - 1) * $itemsPerPage; 
  
  $paginationDisplay = ""; 
  //if only 1 page we require no paginated links to display (so none of this code will run)
  if ($lastPage != "1"){
    // This shows the user what page they are on, and the total number of pages.
    $paginationDisplay .= 'Page <strong>' . $pageNumber . '</strong> of ' . $lastPage. '&nbsp;&nbsp;';
	// If we are not on page 1 we can place the Back link
    if ($pageNumber != 1) {
	    $previous = $pageNumber - 1;
		$paginationDisplay .=  '&nbsp;  <a href="' . $_SERVER['PHP_SELF'] . '?pn=' . $previous . '" class="backNextLink"> Back</a> ';
    } 
    // Lay in the clickable numbers display here between the Back and Next links
    $paginationDisplay .= '<span class="paginationNumbers">' . $centerPages . '</span>';
    // If we are not on the very last page we can place the Next link
    if ($pageNumber != $lastPage) {
        $nextPage = $pageNumber + 1;
		$paginationDisplay .=  '&nbsp;  <a href="' . $_SERVER['PHP_SELF'] . '?pn=' . $nextPage . '" class="backNextLink"> Next</a> ';
    } 
  }
############################End Pagination Logic#############################
  //remove sql limit in method
  
    
  //now sort by rank
  arsort($rankingArray);
  $sortArray[] = array_slice($rankingArray, ($pageNumber -1), $itemsPerPage);
  foreach ($sortArray[0] as $key => $value) {
  	$kaboom = explode("`", $rankingArray[$key]);
  	
  	$eventID = $kaboom[3];
  	$userID = $kaboom[22];
	$username = $kaboom[23];
  	$title = $kaboom[15];
  	$photos = $kaboom[14];
  	$comments = $kaboom[13];
  	$unixTimeStamp = $kaboom[1];
  	$time = $kaboom[8];
  	$thumbPath = $kaboom[25];
  	
  	$eventPic = '<div style="overflow:hidden; height: 60px;"><a href="event.php?id=' . $eventID . '"><img src="' . $thumbPath . '" width="60px" border="0" /></a></div>';
  	$formatTime = date("D M j, Y g:i a", $unixTimeStamp);
  	
  	$popularList .= '<table class="popular_table">
    						  <tr>
    						    <td class="popular_pic">'
    						      . $eventPic .
    						    '</td>
    						    <td class="popular_info">
    						    <a href="event.php?id=' . $eventID . '"><strong>' . $title . '</strong></a><br /><a href="photos.php?id=' . $eventID . '&type=event">' . $photos . ' photos  </a>' . $comments . ' comments<br /> 
    						    <span class="liteGreyColor textsize11">' . $formatTime . '</span>
    						    </td>
    						  </tr>
    						</table>';
  }
?>
<?php include_once("inc/head.inc.php"); ?>
  <style>
    .popular_table {
      /*padding: 20px;*/
      margin-left: auto;
      margin-right: auto;
      width:500px;
      background-color:white;
      overflow:hidden;
      padding:5px;
      border: 1px solid #7C7C7C;
    }
    .popular_info{
      display:block;
      margin-left:-15px;
      width:300px;
    }
    a:link {
      text-decoration: none;
    }
    .liteGreyColor { color:#7C7C7C; }
    .textsize11 { font-size:11px; }
    
    #paginationDisplay{
      text-align: center;
      margin-top: 20px;
      margin-bottom: 40px;
    }
    .pagNumActive {
      color: #000;
      border: #060 1px solid;
      background-color: #FFFFFF;
      padding-left: 3px;
      padding-right: 3px;
    }
    .paginationNumbers a:link { /* 2 numbers around active page are grey */
	  color: #000;
	  text-decoration: none;
	  border:#999 1px solid; 
	  background-color:#FFFFCC; 
	  padding-left:3px; 
	  padding-right:3px;
    }
    .paginationNumbers a:visited {
	  color: #000;
	  text-decoration: none;
	  border:#999 1px solid; 
	  background-color:#FFFFCC; 
	  padding-left:3px; 
	  padding-right:3px;
    }
    .paginationNumbers a:hover { /* color changes */
	  color: #000;
	  text-decoration: none;
	  border:#060 1px solid; 
	  background-color: #FFFFFF; 
	  padding-left:3px; 
	  padding-right:3px;
    }
    .paginationNumbers a:active {
	  color: #000;
	  text-decoration: none;
	  border:#999 1px solid; 
	  background-color:#FFFFCC; 
	  padding-left:3px; 
	  padding-right:3px;
    }
  </style>
</head>
<body>
  <h2>Popular Events</h2>
  <?php echo $popularList; ?>
  <?php echo '<div id="paginationDisplay">' . $paginationDisplay . '</div>'; ?>
</body>
</html>
