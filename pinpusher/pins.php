<?php

require_once 'config.php';
require_once './PHPebbleTimeline/TimelineAPI/Timeline.php';

use TimelineAPI\Pin;
use TimelineAPI\PinLayout;
use TimelineAPI\PinLayoutType;
use TimelineAPI\PinIcon;
use TimelineAPI\PinReminder;
use TimelineAPI\Timeline;

if ($argv[1] == "test") {
	$key = TEST_KEY;
} else {
	$key = PROD_KEY;
}

$category_list = ["pop", "rock", "metal", "indie", "classics", "riffs", "jams", "hits", "smashes", "picks", "knockouts", "anthems", "headliners", "blockbusters"];

$d = json_decode(file_get_contents("https://www.guitarhero.com/api/papi-client/ghl/v1/channelSchedules/en/all/"));

$data = $d->data;

$programs =[];

foreach($data as $channel=>$channel_details){

	foreach($channel_details->programmes as $program){
		$title = $program->title;
		$program->channel = $channel_details->title;
		$program->startTime = new \DateTime("@".($program->startTime/1000),new DateTimeZone("UTC"));
		$program->endTime = clone $program->startTime;
		$program->endTime->modify("+".($program->length/1000)." seconds");
		$program->duration = ($program->length / 60000);
		$program->category = getCategory($title);
		$programs[] = $program;
	}
}


usort($programs,"sortPrograms");


$now = new \DateTime("now", new DateTimeZone("UTC"));
$then = clone $now;
$then->modify("+3 days");
foreach($programs as $program){
		if($program->endTime <= $now || $program->startTime > $then){
			continue;
		}

		$pinLayout = new PinLayout(
			PinLayoutType::GENERIC_PIN, $program->title, "GHTV", $program->category, getBody($program), PinIcon::TIMELINE_SPORTS
		);

		$reminderlayout = new PinLayout(
			PinLayoutType::GENERIC_REMINDER, $program->title, null, null, null, PinIcon::NOTIFICATION_REMINDER, null, null, null, null, null, null, null, ["locationName" => $program->channel]
		);

		$reminder_time = clone $program->startTime;
		$reminder_time->modify("-30 minutes");
		$reminder = new PinReminder($reminderlayout, $reminder_time); //send the reminder right away


		echo getPinId($program);exit;

		$pin = new Pin(getPinId($program), $program->startTime, $pinLayout, $program->duration , null);
		$pin->addReminder($reminder);

		Timeline::pushSharedPin($key, [$program->category], $pin);



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
	return sprintf("ghtv-%s-%d",strtolower(preg_replace('/\s+/','',$program->channel)),$program->startTime->format("YmdHis"));
}

function getBody($program){
	$body = PHP_EOL;
	$body .= "Title: {$program->title}".PHP_EOL . PHP_EOL;
	$body .= "Category: {$program->category}".PHP_EOL . PHP_EOL;
	$body .= "Channel: {$program->channel}".PHP_EOL . PHP_EOL;

	return $body;
}

