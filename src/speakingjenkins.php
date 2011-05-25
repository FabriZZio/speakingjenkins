#!/usr/bin/php

Info: http://github.com/mathiasverraes/speakingjenkins/

Required:
	--url=http://YOURJENKINS/job/YOURJOB/
Optional:
	--username=NAME
	--password=PW

To run in background, add "> /dev/null &"

<?php
// Configuration
define('INTERVAL', 60); // in seconds
define('VOICE', 'Vicki'); // http://www.gabrielserafini.com/blog/2008/08/19/mac-os-x-voices-for-using-with-the-say-command/

// command line options
$url = ''; $username = ''; $password = '';
$options = getopt('', array('url:', 'username::', 'password::'));
extract($options);

// authentication
$context = array();
if($username && $password)
{
	$context = array('http' => array(
		'header'  => "Authorization: Basic " . base64_encode("$username:$password")
	));
}
$context = stream_context_create($context);

// get job info
$job = json_decode(file_get_contents($url.'api/json', null, $context));
$current = '';

// main loop
while(true)
{
	$last = file_get_contents($url.'api/xml?xpath=//lastCompletedBuild/url/text()', null, $context);
	if($current != $last)
	{
		$build = json_decode(file_get_contents($last.'api/json', null, $context));
		echo "{$build->fullDisplayName} {$build->result} {$build->id}".PHP_EOL;
		$current = $last;
		if($build->result !== 'SUCCESS') {
			speak($job, $build);
		}
	}
	sleep(INTERVAL);
}

function speak($job, $build)
{
	exec("say -v '.VOICE.' job, {$job->displayName}, number {$build->number}, {$build->result},");
	foreach($build->culprits as $culprit)
	{
		$fullName = preg_replace('/<(.*)>/', '', $culprit->fullName); // remove email
		exec("say -v '.VOICE.' Partner in crime, $fullName,");
	}
}
