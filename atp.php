<?php
  # Include libraries
  include("scripts/LIB_parse.php");
  include("scripts/LIB_http.php");
  /*//Phase One
  # Download a web page
  $web_page = http_get($target="http://www.alltheparties.com", $referer="");

  $getBetween = return_between($web_page['FILE'], "<h1>Recent Photos</h1>", "<h1>Top 10 Viewed Photos</h1>", EXCL);

  $link_tag_array = parse_array($getBetween, "<a href=", ">");

  echo "<html>";
  for($i=0; $i<count($link_tag_array); $i++) {
    $link_path = get_attribute($link_tag_array[$i],  $attribute="href");
    $tag_excl = return_between($getBetween, $link_tag_array[$i], "</a>", EXCL);
    if (!preg_match("#^<img#", $tag_excl)) {
      echo $link_path . '<br />' . $tag_excl . '<br /><br /><br />';
    }
    
  }
   
   echo "</html>";*/
  
  
  /*http://www.alltheparties.com/account/login
  
  <h1>Login</h1>
<form action="/account/login" id="login_form" method="post">
<div style="padding:1px 5px;">
	<p><label>Username:</label><input id="user_username" maxlength="75" name="user[username]" size="20" tabindex="1" type="text" /></p>

<p><label>Password:</label><input id="user_password" name="user[password]" size="12" tabindex="2" type="password" /></p>

<p><label>Remember Username and Password</label><input id="cookie_remember_me" name="cookie[remember_me]" tabindex="3" type="checkbox" value="1" /><input name="cookie[remember_me]" type="hidden" value="0" /></p>

<p><label>&nbsp;</label><input name="commit" tabindex="4" type="submit" value="LOGIN" /></p>

<p><a href="/account/create" class="stronger">Not a member? Sign up now</a> | <a href="/account/lost_password">Forgot Password?</a></p>

<input type="hidden" id="md5" name="md5" value="0" />
</div>
</form> */
  
  $web_page = http_get($target="http://nyc.alltheparties.com/pictures/event/125291", $referer="");

  $getBetween = return_between($web_page['FILE'], "<h1>Thumbnails", "<br clear='all' />", EXCL);

  $link_tag_array = parse_array($getBetween, "<a href=", ">");
  
  echo "<html>";
  for($i=0; $i<count($link_tag_array); $i++) {
    $link_path = get_attribute($link_tag_array[$i],  $attribute="href");
    echo $link_path . '<br /><br />';
  }
  echo "</html>";
?>