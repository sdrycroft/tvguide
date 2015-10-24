<?php
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
