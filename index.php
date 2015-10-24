<?php
global $conf;
require_once 'config.inc.php';
/**
CREATE TABLE programme (
	start BIGINT,
	start_bst TINYINT,
	stop BIGINT,
	stop_bst TINYINT,
	channel VARCHAR(64),
	title TEXT,
	description MEDIUMTEXT,
	url TEXT,
	category TEXT,
	rating VARCHAR(16),
	date VARCHAR(16),
	PRIMARY KEY(
		start, start_bst, channel)
) CHARSET=UTF8;
CREATE TABLE channel (
	num INT NOT NULL DEFAULT 1000,
	id VARCHAR(64),
	channel_description VARCHAR(255),
	img VARCHAR(255),
	PRIMARY KEY(
		id)
) CHARSET=UTF8;
*/
if(php_sapi_name() != 'cli'){
  // If we are not on the command line, we output the HTML
  header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
  header("Pragma: no-cache"); // HTTP 1.0.
  header("Expires: 0"); // Proxies.
  $start_timestamp = floor(time()/3600)*3600;
  if(isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] < 42 && $_GET['page'] > -42){
    $start_timestamp = $start_timestamp + ($_GET['page'] * 14400);
  }

  $now = date('YmdH0000', $start_timestamp);
  $now1 = date('YmdH0000', $start_timestamp + 3600);
  $now4 = date('YmdH0000', $start_timestamp + 14400);
  $hour_width = calculate_width($now, $now1, $now, $now4);
  echo '<!doctype html>
<html>
 <head>
  <meta charset="UTF-8">
  <link type="text/css" rel="stylesheet" href="reset" media="all" />
  <link type="text/css" rel="stylesheet" href="style" media="all" />
  <script type="text/javascript" src="jquery/jquery.min.js"></script>
  <script type="text/javascript" src="jquery/jquery-ui.min.js"></script>
  <script>
    $(function(){
      $( document ).tooltip({
          content: function () {
              return $(this).prop(\'title\');
          }
      });
    });
  </script>
  <title>TV</title>
 </head>
 <body><div class="row"><div class="channel empty"><h2><a href="/'.($_GET['page'] == 1 ? '' : '?page='.($_GET['page']-1) ).'">&Lang;</a></h2></div><div class="header" style="width:'.$hour_width.'px"><h2>'.date('H:00', $start_timestamp).'</h2></div><div class="header" style="width:'.$hour_width.'px"><h2>'.date('H:00', $start_timestamp + 3600).'</h2></div><div class="header" style="width:'.$hour_width.'px"><h2>'.date('H:00', $start_timestamp + 7200).'</h2></div><div class="header" style="width:'.($hour_width-81).'px"><h2>'.date('H:00', $start_timestamp + 10800).'</h2></div><div class="channel empty right"><h2><a href="/'.($_GET['page'] == -1 ? '' : '?page='.($_GET['page']+1) ).'">&Rang;</a></h2></div></div><div class="row">';
  // Connect to the database
  $mysqli = new mysqli("localhost", $conf['db']['username'], $conf['db']['password'], $conf['db']['database']);
  $result = $mysqli->query("SELECT * FROM programme LEFT JOIN channel ON programme.channel = channel.id WHERE start < $now4 AND stop > $now ORDER BY num ASC, channel ASC, start ASC");
  $previous_channel = FALSE;
  while($row = $result->fetch_object()){
    if($previous_channel && $row->channel != $previous_channel){
      echo '</div><div class="row">';
    }
    if(!$previous_channel || $row->channel != $previous_channel){
      echo '<div class="channel"><img width="80" height="45" src="images/'.$row->img.'" title="<h3>'.$row->channel_description.'</h3><p class=\'time\'>'.$row->num.'</p>" alt="'.$row->channel_description.'"/></div>';
    }
    $previous_channel = $row->channel;
    $categories = unserialize($row->category);
    echo '<div class="programme '.implode(' ', $categories).'" style="width:'.calculate_width($row->start, $row->stop, $now, $now4).'px" title="'.get_tooltip_text($row).'"><p>'.get_title_text($row).'</p></div>';
  }
  echo '
  </div>
 </body>
</html>';
} else {
  // If we are being called from the command line, we do the load.
  // Connect to the database
  $mysqli = new mysqli("localhost", $conf['db']['username'], $conf['db']['password'], $conf['db']['database']);
  // Empty tables
  $mysqli->query("TRUNCATE channel");
  $mysqli->query("TRUNCATE programme");

  // Dom document
  $doc = new DOMDocument();
  $doc->loadXML(file_get_contents('tv.xml'));

  // XPath document
  $values = array();
  $xpath = new DOMXpath($doc);
  // Load channels
  foreach($xpath->query('/tv/channel') as $element){
    $id = $element->getAttribute('id');
  	foreach($element->getElementsByTagName('icon') as $icon){
      $url = $icon->getAttribute('src');
    }
    foreach($element->getElementsByTagName('display-name') as $displayname){
      $displayname = $displayname->nodeValue;
    }
  	// Get the filename (could use a proper URL function here).
  	$filename = explode('/', $url);
  	$filename = array_pop($filename);
  	// Check if the image exists, else we try to download it
    if(!file_exists("images/$filename")){
      file_put_contents("images/$filename", file_get_contents($url));
    }
    $num = 1000;
    // Update the number for the channels
    if(isset($conf['freeview_numbers'][$id])){
      $num = $conf['freeview_numbers'][$id];
    }
    $values[] = sprintf("('%s',%d,'%s','%s')", $mysqli->escape_string($id), $num, $mysqli->escape_string($displayname), $mysqli->escape_string($filename));
  }
  $mysqli->query("INSERT INTO channel (id, num, channel_description, img) VALUES " . implode(',', $values));
  // Load programmes
  $sql = "INSERT INTO programme (start, start_bst, stop, stop_bst, channel, title, description, url, category, rating, date) VALUES ";
  foreach($xpath->query("/tv/programme") as $element){
    $start = $element->getAttribute('start');
    $start_bst = substr($start, 17, 1);
    $start = substr($start, 0, 14);
    $stop = $element->getAttribute('stop');
    $stop_bst = substr($stop, 17, 1);
    $stop = substr($stop, 0, 14);
    $channel = $element->getAttribute('channel');
    $title = '';
    foreach($element->getElementsByTagName('title') as $title){
      $title = $title->nodeValue;
    }
    $description = '';
    foreach($element->getElementsByTagName('desc') as $desc){
      $description = $desc->nodeValue;
    }
    $categories = array();
    foreach($element->getElementsByTagName('category') as $category){
      $categories[$category->nodeValue] = $category->nodeValue;
    }
    $categories = serialize($categories);
    $date = '';
    foreach($element->getElementsByTagName('date') as $date){
      $date = $date->nodeValue;
    }
    $rating = '';
    foreach($element->getElementsByTagName('star-rating') as $starrating){
      foreach($starrating->getElementsByTagName('value') as $value){
        $rating = $value->nodeValue;
      }
    }
    $url = '';
    foreach($element->getElementsByTagName('url') as $url){
      $url = $url->nodeValue;
    }
    if($start_bst == $stop_bst && $stop > $start){
      $values[] = sprintf("('%s',%d,'%s',%d,'%s','%s','%s','%s','%s','%s','%s')", $start, $start_bst, $stop, $stop_bst, $mysqli->escape_string($channel), $mysqli->escape_string($title), $mysqli->escape_string($description), $mysqli->escape_string($url), $mysqli->escape_string($categories), $mysqli->escape_string($rating), $mysqli->escape_string($date));
    }
    if(count($values) == 100){
      $mysqli->query($sql . implode(',', $values));
      $values = array();
    }
  }
  $mysqli->query($sql . implode(',', $values));
  $values = array();
  exit(0);
}
// Title text
function get_title_text($row){
  return ( $row->url ? '<a href=\''.$row->url.'\'>' : '' ).htmlspecialchars($row->title). ( $row->url ? '</a>' : '');
}
// Title stuff
function get_tooltip_text($row){
  return '<h3 class=\'programme_title\'>'.
    htmlspecialchars($row->title).
    ($row->date?' ('.$row->date.')':'').
    '</h3><p class=\'time\'>'.
    date('H:i', time2timestamp($row->start)).
    ' -> '.
    date('H:i', time2timestamp($row->stop)).
    '</p>'.
    ($row->description?'<p class=\'description\'>'.htmlspecialchars($row->description).'</p>':'').
    ($row->rating?'<p class=\'rating\'>'.htmlspecialchars($row->rating).'</p>':'').
    '"';
}
// Width calculate function
function calculate_width($start, $stop, $min, $max, $screen_width = 980, $width = 240){
  $start = time2timestamp($start);
  $stop = time2timestamp($stop);
  $min = time2timestamp($min);
  $max = time2timestamp($max);
  if($start < $min){
    $start = $min;
  }
  if($stop > $max){
    $stop = $max;
  }
  return (((($stop - $start)/60)/$width) * $screen_width)-1;
}
// Convert time to timestamp
function time2timestamp($time){
  return strtotime(substr($time, 0, 4).'-'.substr($time, 4, 2).'-'.substr($time, 6, 2).' '.substr($time, 8, 2).':'.substr($time, 10, 2));
}
