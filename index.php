<?php
$url = $_GET['url'] ?: 'http://www.google.com';

// get the headers and response time
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,            $url);
curl_setopt($ch, CURLOPT_HEADER,         true);
curl_setopt($ch, CURLOPT_NOBODY,         true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT,        15);
$r = curl_exec($ch);
$info = curl_getinfo($ch);
$r = split("\n", $r);
curl_close($ch);

$response_time = round( $info['total_time'] * 1000 );

$status = 'Up';
// assume "down" if 4xx or 5xx error
if ( !in_array( substr($info['http_code'],0,1), array('2','3') ) )
{
    $status = 'Down';
}

// just clean up for the file name
$file = '/tmp/downtime/'.str_replace( array('http://', '/'), array('',''), $url ).'.txt';

if ( $status == 'Down' )
{
    // log the current timestamp for future calculations
    file_put_contents($file, time().'|'.$info['http_code']);
}

// do some calc to prettify the time strings
$last_downtime_info = (int) @file_get_contents($file);
list( $last_downtime_ts, $code ) = explode( '|', $last_downtime_info );
$now = time(); // call "time()" once to avoid skipping to the next second mid run

$ago = $now - $last_downtime_ts;

$last_downtime = 'N/A';
if ( $ago < $now )
{
    switch ( $last_downtime_ts )
    {
        case $ago < (60): // seconds
            $last_downtime = round( $ago ) . ' secs';
            break;

        case $ago < (60*60): // minutes
            $last_downtime = round( $ago/60 ) . ' mins';
            break;

        case $ago < (60*60*24*3): //  hours
            $last_downtime = round( $ago/60/24/3, 1 ) . ' hrs';
            break;

        case $ago < (60*60*24*30): // days
            $last_downtime = round( $ago/60/60/30, 1 ) . ' days';
            break;

        case $ago < (60*60*24*365): // months
            $last_downtime = round( $ago/60/60/365, 1 ) . ' mnths';
            break;

        default: // years?
            $last_downtime = 'Long ago';
            break;
    }
}

// format for a custom Monitor Widget on geckoboard.com
$data = array(
    'status' => $status, 'downTime' => $last_downtime, 'responseTime' => $response_time . ' ms',
);

echo json_encode($data);
