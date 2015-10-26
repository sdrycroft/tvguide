<?php
// Title text
function get_title_text($row){
  return ( $row->url ? '<a href=\''.$row->url.'\' target=\'_blank\'>' : '' ).htmlspecialchars($row->title). ( $row->url ? '</a>' : '');
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
    ($row->rating?'<p class=\'rating\'>'.htmlspecialchars($row->rating).'</p>':'');
}
// Width calculate function
function calculate_width($start, $stop, $min, $max, $screen_width, $width = 240){
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
