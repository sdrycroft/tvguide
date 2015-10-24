<?php
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
<link type="text/css" rel="stylesheet" href="css/reset.css" media="all" />
<link type="text/css" rel="stylesheet" href="css/style.css" media="all" />
<script type="text/javascript" src="js/jquery.min.js"></script>
<script type="text/javascript" src="js/jquery-ui.min.js"></script>
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
