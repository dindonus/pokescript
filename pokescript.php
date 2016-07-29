<?php

/* Usage:
php pokescript.php place=sartrouville latitude=48.9414 longitude=2.1630
php pokescript.php place=marseille latitude=43.3003 longitude=5.4264
*/

define('MAXIMUM_RANGE', 1000);
define('MIDDLE_RANGE', 350);
define('CLOSE_RANGE', 40);

pokeScan(getArg('place'), getArg('latitude'), getArg('longitude'));

function pokeScan($place, $latitude, $longitude) {
	
	exec("touch $place.log");
	exec("touch $place.json");
	exec("touch $place.stats");
	
	$list = fetchList($latitude, $longitude);
	handleList($place, $list);
	generateStats($place);
	
}

function fetchList($latitude, $longitude) {
	
	$data = file_get_contents("https://pokevision.com/map/scan/$latitude/$longitude");
	$data = file_get_contents("https://pokevision.com/map/data/$latitude/$longitude");
	$data = json_decode($data, true);
	$data = $data['pokemon'];

	$list = [];

	foreach ($data as $pokemon) {
		
		$latitudeDiff = $pokemon['latitude'] - $latitude;
		$longitudeDiff = $pokemon['longitude'] - $longitude;
		
		$distance = sqrt(pow($latitudeDiff, 2) + pow($longitudeDiff, 2));
		$distance = round($distance * 60000);
		
		$list[ $distance ] = $pokemon;
		
	}

	ksort($list);
	
	return $list;

}

function handleList($place, $list) {

	$sounded = false;

	foreach ($list as $distance => $pokemon) {
		
		$name = getPokeName($pokemon['pokemonId']);
		
		if ($distance < MAXIMUM_RANGE) {
			
			$status = getStatus($place, $pokemon);
			$time = getRemaining($pokemon['expiration_time']);
			$rarity = getRariry($place, $name);
			
			if ($distance < MIDDLE_RANGE) {
				
				echo str_pad($distance.'m', 10);
				echo str_pad($name, 16);
				echo str_pad($rarity, 12);
				echo str_pad($time, 12);
				echo str_pad(getUid($pokemon), 16);
				echo "\n";
				
				if ($sounded === false and $status === 'new' and $rarity > 50) {
					
					exec('mplayer pokesound.mp3 2> /dev/null');
					
					$image = 'http://images.pocketgamer.co.uk/images/featimgs/pokemon-go-dex-'.strtolower($name).'.jpg';
					$image = file_get_contents($image);
					
					file_put_contents('/tmp/pokemon', $image);
					
					exec('eog /tmp/pokemon 2> /dev/null');
					
					$sounded = true;
					
				}
				
			}
			
			if ($status === 'new') {
				
				savePokestats($place, $pokemon, $distance);
				
			}
			
		}
		
	}

}

function getRemaining($time) {
	
	$remaining = $time - time();
	$minute = floor($remaining / 60);
	$remaining -= ($minute * 60);
	
	return $minute.' min';
	
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

function getStatus($place, $pokemon) {
	
	$data = file($place.'.log');
	$uid = getUid($pokemon);
	
	foreach ($data as $line) {
		
		$explode = explode(';', $line);
		
		if ($explode[1] === $uid) {
			return '-';
		}
		
	}
	
	$line = date('Y-m-d H:i').';';
	$line .= $uid.';';
	$line .= getPokeName($pokemon['pokemonId']).';';
	$line .= round($pokemon['longitude'], 5).';';
	$line .= round($pokemon['latitude'], 5).';';
	$line .= "\n";
	
	file_put_contents($place.'.log', $line, FILE_APPEND);
	
	return 'new';
	
}

function savePokestats($place, $pokemon, $distance) {
	
	$name = getPokeName($pokemon['pokemonId']);
	$data = file_get_contents($place.'.json');
	$data = json_decode($data, true);
	
	if ($data === null) {
		
		$data = [
			'CLOSE_RANGE' => ['Total' => 0],
			'MIDDLE_RANGE' => ['Total' => 0],
			'MAXIMUM_RANGE' => ['Total' => 0],
		];
		
	}
	
	foreach ($data as $range => $part) {
		
		if ($distance <= constant($range)) {
			
			if (isset($data[ $range ][ $name ]) === false) {
				$data[ $range ][ $name ] = 0;
			}
			
			$data[ $range ][ $name ]++;
			$data[ $range ]['Total']++;
			
			asort($data[ $range ]);
			
		}
		
	}
	
	file_put_contents($place.'.json', json_encode($data));
	
}

function generateStats($place) {
	
	sleep(1);
	
	$data = file_get_contents($place.'.json');
	$data = json_decode($data, true);
	
	$text = '';
	
	foreach ($data as $range => $list) {
		
		$text .= '----- '.constant($range)."m:\n";
		
		foreach ($list as $name => $count) {
			
			$text .= str_pad($name, 20);
			$text .= $count;
			$text .= "\n";
			
		}
		
		$text .= "\n\n";
		
	}
	
	file_put_contents($place.'.stats', $text);
	
}

function getUid($pokemon) {
	
	$key = md5($pokemon['longitude'].$pokemon['latitude'].$pokemon['pokemonId']);
	
	return substr($key, 0, 12);
	
}

function getRariry($place, $name) {
	
	$data = file_get_contents($place.'.json');
	$data = json_decode($data, true);
	
	if ($data === null or $data['MAXIMUM_RANGE']['Total'] < 100) {
		return '?';
	}
	
	if (isset($data['MAXIMUM_RANGE'][ $name ]) === false) {
		$data['MAXIMUM_RANGE'][ $name ] = 1;
	}
	
	return round($data['MAXIMUM_RANGE']['Total'] / $data['MAXIMUM_RANGE'][ $name ]);
	
}

function getArg($name, $default = null) {

	foreach ($_SERVER['argv'] as $arg) {
	
		list($nameArg) = explode('=', $arg);
		
		if ($name === $nameArg) {
		
			$value = substr($arg, mb_strlen($nameArg) + 1);
			
			if ($value === false) {
				return true;
			}
			
			return $value;
			
		}
		
	}
		
	return $default;
	
}
