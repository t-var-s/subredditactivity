<?php
class SubredditActivity{
    public $base_endpoint = 'https://www.reddit.com/r/%SUBREDDIT%.json';
    private $activity = array();
    public $info = array();
    private $activity_file = 'activity.json';
    private $info_file = 'info.json';
    private $timezone = 'Europe/Lisbon';
    function __construct($options){
        if(!$options['subreddits']){ return false; }
        foreach($options['subreddits'] as $subreddit){
            array_push($this->activity, array('identifier'=>$subreddit, 'users'=>array()));
        }
        if($options['timezone']){ $this->timezone = $options['timezone']; }
        date_default_timezone_set($this->timezone);
        if($options['activity_file']){ $this->activity_file = $options['activity_file']; }
        if($options['info_file']){ $this->info_file = $options['info_file']; }
        $this->activity_file = dirname(__FILE__).'/'.$this->activity_file;
        $this->info_file = dirname(__FILE__).'/'.$this->info_file;
        if(!file_exists($this->activity_file)){ 
            file_put_contents($this->activity_file, json_encode($this->activity, true));
        }else{
            $this->activity = json_decode(file_get_contents($this->activity_file), true);
        }
        foreach($this->activity as $s=>$subreddit){
            $this->activity[$s] = $this->getActivity($subreddit);
        }
        file_put_contents($this->activity_file, json_encode($this->activity, true));
        $this->info = $this->parseInfo($this->activity);
        file_put_contents($this->info_file, json_encode($this->info, true));
    }
    function getActivity($subreddit){
        $endpoint = str_replace('%SUBREDDIT%', $subreddit['identifier'], $this->base_endpoint);
        $response = json_decode(file_get_contents($endpoint), true);
        if(!$response){ return $subreddit; }
        $data_posts = $response['data']['children'];
        foreach($data_posts as $data_post){
            sleep(0.1);
            $comments_endpoint = 'https://www.reddit.com'.substr($data_post['data']['permalink'], 0, -1).'.json';
            $response = json_decode(file_get_contents($comments_endpoint), true);
            if(!$response){ return $subreddit; }
            $post = $response[0]['data']['children'][0]['data'];
            if($post['author'] && $post['created_utc']){
                $subreddit['users'][$post['author']] = $post['created_utc'];
            }
            $comments = $response[1]['data']['children'];
            foreach($comments as $comment){
                if($comment['data'] && $comment['data']['author'] && $comment['data']['created_utc']){
                    $subreddit['users'][$comment['data']['author']] = $comment['data']['created_utc'];
                }
            }
        }
        $subreddit['user_count'] = count($subreddit['users']);
        $total_utc = 0;
        foreach($subreddit['users'] as $username=>$utc){ $total_utc += $utc; }
        if($subreddit['user_count']){
            $subreddit['average_utc'] = intval($total_utc / $subreddit['user_count']);
        }
        return $subreddit;
    }
    function parseInfo($activity){
        $info = array('timestamp'=>date('Y-m-d H:i', time()), 'total_user_count'=>0, 'overlapping_user_count'=>0);
        if(!$activity){ return $info; }
        $overlap = array();
        foreach($activity as $sub_activity){
            if(!$sub_activity['users']){ continue; }
            $info[$sub_activity['identifier'].'_user_count'] = $sub_activity['user_count'];
            $info['total_user_count'] = $info['total_user_count'] + $sub_activity['user_count']; 
            $info[$sub_activity['identifier'].'_seconds_ago'] = time() - $sub_activity['average_utc'] ;
            foreach($sub_activity['users'] as $author=>$utc){
                if(!$overlap[$author]){ $overlap[$author] = 1; }else{ $overlap[$author] ++; }
            }
        }
        if(!$info['total_user_count']){ return $info; }
        foreach($overlap as $author=>$sub_count){
            if($sub_count == count($activity)){ $info['overlapping_user_count'] ++; }
        }
        $info['overlapping_percentage'] = intval(($info['overlapping_user_count']/$info['total_user_count'])*100);
        $control_sum = $info['overlapping_percentage'];//to check for the rounding error
        foreach($activity as $sub_activity){
            $info[$sub_activity['identifier'].'_percentage'] = intval(($info[$sub_activity['identifier'].'_user_count']/$info['total_user_count'])*100);
            $control_sum = $control_sum + $info[$sub_activity['identifier'].'_percentage']; 
        }
        if($control_sum < 100){ $info['overlapping_percentage'] = $info['overlapping_percentage'] + (100 - $control_sum); }
        return $info;
    }
}
?>
