<?php

// Put your GPS coordinates here:
$latitude = 48.000000; 
$longitude = 2.000000;

$data = file_get_contents("https://pokevision.com/map/scan/$latitude/$longitude");
$data = file_get_contents("https://pokevision.com/map/data/$latitude/$longitude");
$data = json_decode($data, true);
$data = $data['pokemon'];

$list = [];

foreach ($data as $pokemon) {
	
	$latitudeDiff = $pokemon['latitude'] - $latitude;
	$longitudeDiff = $pokemon['longitude'] - $longitude;
	
	$distance = sqrt(pow($latitudeDiff, 2) + pow($longitudeDiff, 2)) * 1000000;
	
	$list[ $distance ] = $pokemon;
	
}

ksort($list);

$sounded = false;

foreach ($list as $distance => $pokemon) {
	
	$distance = round($distance / 50);
	$name = getPokeName($pokemon['pokemonId']);
	
	if ($distance < 30) {
		
		$status = getStatus($pokemon);
		
		echo str_pad($distance.'m', 10);
		echo str_pad($name, 10);
		echo str_pad($status, 10);
		echo ($pokemon['expiration_time'] - time()).'s';
		echo "\n";
		
		if ($sounded === false and $status === 'new') {
			
			exec('mplayer pokesound.mp3 2> /dev/null');
			$sounded = true;
			
		}
		
	}
	
}

function getPokeName($id) {
	
	$pokedex = [];
	$data = file('pokedex');
	
	foreach ($data as $line) {
		
		$explode = explode(':', $line);
		$pokedex[ (int) $explode[0] ] = trim($explode[1]);
		
	}
	
	return $pokedex[ $id ];
	
}

function getStatus($pokemon) {
	
	$data = file('pokelog');
	$uid = getUid($pokemon);
	
	foreach ($data as $line) {
		
		$explode = explode(';', $line);
		
		if ($explode[1] === $uid) {
			return 'old';
		}
		
	}
	
	$line = date('Y-m-d H:i:s').';'.$uid.';'.getPokeName($pokemon['pokemonId'])."\n";
	
	file_put_contents('pokelog', $line, FILE_APPEND);
	
	return 'new';
	
}

function getUid($pokemon) {
	
	return md5($pokemon['longitude'].$pokemon['latitude'].$pokemon['pokemonId']);
	
}
