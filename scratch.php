<?php

  $today = getdate();
  echo 'Month: ' . $today['month'] . '<br />';
  echo 'Day of month: ' . $today['mday'] . '<br />';
  echo 'Hours: ' . $today['hours'] . '<br />';
  echo 'Minutes: ' . $today['minutes'] . '<br />';
  //$tomorrow = mktime(0, 0, 0, date("m"), date("d")+1, date("y"));
  //echo "Tomorrow is ".date("m/d/y", $tomorrow) ."<br />"; 
  /*for ($i = 0; $i < 366; $i++) {
    $tomorrow = mktime(0, 0, 0, date("m"), date("d")+$i, date("y"));
    echo "Tomorrow is ".date("D M j", $tomorrow) ."<br />"; 
  }*/
  for ($j = 1; $j <= 12; $j++) {
    echo $j . '<br />';
  }
  

?>