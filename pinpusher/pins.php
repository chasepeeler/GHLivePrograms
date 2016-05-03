<?php

require_once 'config.php';
require_once 'vendor/autoload.php';

use TimelineAPI\Pin;
use TimelineAPI\PinLayout;
use TimelineAPI\PinLayoutType;
use TimelineAPI\PinIcon;
use TimelineAPI\PinReminder;
use TimelineAPI\Timeline;
use TimelineAPI\PinAction;
use TimelineAPI\PinActionType;
use TimelineAPI\PebbleColour;
use Garden\Cli\Cli;

define('PUSH','push');
define('DISPLAY','display');
define('DELETE','delete');

$cli = Cli::create()
	->command('push')
	->description('Pushes pins to timeline API')
	->command('delete')
	->description('Deletes pins from the timeline API. Will only delete pins for programs still scheduled.')
	->opt("id","Delete only the pin with the given ID",false,'string')
	->command('display')
	->description('Displays the JSON for the pins instead of pushing them to the API')
	->command('*')
	->opt('days-out:d','The number of days out to pull data from the GHTV API. Default is 4. ',false,'integer')
	->opt('num-items:i','The maximum number of items to pull from the GHTV API. Default is no limit.',false,'integer')
	->opt("testing:t","Enable options specific to pins pushed to sandbox",false,'boolean')
	->opt('verbose:v','Include more verbose output',false,'boolean');
	
$args = $cli->parse($argv);
$command = $args->getCommand();
$daysout = $args->getOpt('days-out',4);
$numitems = $args->getOpt('num-items',PHP_INT_MAX);
$verbose = $args->getOpt('verbose',false);

if(true === $args->getOpt('testing',false)){
	$key = TEST_KEY;
} else {
	$key = PROD_KEY;
}
	
if($command == DELETE && !empty($args->getOpt('id',''))){
	$id = $args->getOpt('id');
	TimelineAPI::deleteSharedPin($key,$id);
	if($verbose){
		echo "Deleted Pin: {$id}".PHP_EOL;
	}
	exit;
}
	
$category_list = ["pop", "rock", "metal", "indie", "classics", "riffs", "jams", "hits", "smashes", "picks", "knockouts", "anthems", "headliners", "blockbusters"];

$d = json_decode(file_get_contents("https://www.guitarhero.com/api/papi-client/ghl/v1/channelSchedules/en/all/"));

$data = $d->data;

$programs =[];

foreach($data as $channel=>$channel_details){

	foreach($channel_details->programmes as $program){
		$title = $program->title;
		$program->channel = $channel_details->title;
		$program->channel_number = $channel;
		$program->startTime = new \DateTime("@".($program->startTime/1000),new DateTimeZone("UTC"));
		$program->endTime = clone $program->startTime;
		$program->endTime->modify("+".($program->length/1000)." seconds");
		$program->duration = ($program->length / 60000);
		$program->category = getCategory($title);
		$programs[] = $program;
	}
}


usort($programs,"sortPrograms");

/*
cutoff in terms of days out works as follows:
if today is 1/1, and days out = 4, then all programs after 1/4 23:59:59 will be excluded.
now + days_out days 00:00:00 - 1 second

Days out = 1 will give you everything for the rest of today
*/

$now = new \DateTime("now", new DateTimeZone("UTC"));
$then = clone $now;
$then->modify("+{$daysout} days");
$then->setTime(0,0,0);
$then->modify("-1 second");
$item_count = 0;
$items = [];
foreach($programs as $program){
		if($program->endTime <= $now || $program->startTime > $then){
			continue;
		}
		$ghtv = "GHTV";
		if($key == TEST_KEY){
			$program->title .= "_TEST";
			$ghtv .= "-TEST";
		}
		$id = getPinId($program);
		if($command == DELETE){
			$r = Timeline::deleteSharedPin($key,$id);
			if($verbose){
				echo "Deleted Pin: {$id}. Category: {$program->category}, Title: {$program->title}, Start: ".$program->startTime->format("m/d/Y H:i").PHP_EOL;
				echo "Result: ".$r['status']['message'].' ('.$r['status']['code'].')'.PHP_EOL;
				if(!empty($r['result'])){
					echo json_encode($r['result'],JSON_PRETTY_PRINT).PHP_EOL;
				}
			}
		} else {
		
			$pinLayout = new PinLayout(
				PinLayoutType::CALENDAR_PIN, $program->title, $program->title, $program->channel, getBody($program), PinIcon::MUSIC_EVENT, PinIcon::MUSIC_EVENT, PinIcon::MUSIC_EVENT, PebbleColour::WHITE, PebbleColour::CHROME_YELLOW, null, null, null, ["locationName"=>$program->channel]
			);

			$reminderlayout = new PinLayout(
				PinLayoutType::GENERIC_REMINDER, $program->title, null, null, null, PinIcon::NOTIFICATION_REMINDER, null, null, null, null, null, null, null, ["locationName" => $program->channel]
			);

			$reminder_time = clone $program->startTime;
			$reminder_time->modify("-30 minutes");
			$reminder = new PinReminder($reminderlayout, $reminder_time); //send the reminder right away
			
			$action = new PinAction('Reminder',$program->channel_number.$program->startTime->getTimestamp(),PinActionType::OPEN_WATCH_APP);
			
			$pin = new Pin($id, $program->startTime, $pinLayout, $program->duration , null);
			$pin->addReminder($reminder);
			$pin->addAction($action);		
			
			if($command == PUSH){
				$r = Timeline::pushSharedPin($key, [$program->category], $pin);
				if($verbose){
					echo "Pushed Pin: {$id}. Category: {$program->category}, Title: {$program->title}, Start: ".$program->startTime->format("m/d/Y H:i").PHP_EOL;
					echo "Result: ".$r['status']['message'].' ('.$r['status']['code'].')'.PHP_EOL;
					if(!empty($r['result'])){
						echo json_encode($r['result'],JSON_PRETTY_PRINT).PHP_EOL;
					}
				}
			} else {
				$items[] = $pin->getData();
			}
		}
		$item_count++;
		if($item_count >= $numitems){
			break;
		}
}

if($command === DISPLAY){
	if(count($items) == 1){
		$items = $items[0];
	}
	echo json_encode($items,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
}

function sortPrograms($a,$b)
{
	if($a->startTime < $b->startTime){
		return -1;
	}
	if($a->startTime > $b->startTime){
		return 1;
	}

	return strcmp($a->channel,$b->channel);

}


function getCategory($title){
	global $category_list;
	foreach($category_list as $category){
		if(preg_match('/\b'.$category.'\b/i',$title)){
				return $category;
		}
	}
	return "other";
}


function getPinId($program){
	global $key;
	
	$id = sprintf("ghtv-%s-%s",strtolower(preg_replace('/\s+/','',$program->channel)),$program->startTime->format("YmdHi"));
	if($key == TEST_KEY){
		$id .= "-test";
	}
	return $id;
}

function getBody($program){
	$body = PHP_EOL;
	$body .= "Title: {$program->title}".PHP_EOL . PHP_EOL;
	$body .= "Category: ".ucfirst($program->category).PHP_EOL . PHP_EOL;
	$body .= "Channel: {$program->channel}".PHP_EOL . PHP_EOL;
	global $key;
	if($key == TEST_KEY){
		$body .= "ID: ".getPinId($program).PHP_EOL.PHP_EOL;
	}

	return $body;
}

