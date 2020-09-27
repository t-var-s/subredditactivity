<?php
require_once 'SubredditActivity.php';
$sa = new SubredditActivity(array(
    'subreddits'=>array('dnd', 'rpg'),
    //'timezone'=>'Europe/Lisbon' is only used for a timestamp
    //'activity_file'=>'activity.json' where data is stored
    //'info_file'=>'info.json' where anonymous relevant information is stored
));
?>
