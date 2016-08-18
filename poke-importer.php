<?php
require 'vendor/autoload.php';

set_time_limit(0);

use PokePHP\PokeApi;
use Gregwar\Image\Image;

$api = new PokeApi;

$client = new GuzzleHttp\Client(['base_uri' => 'http://192.168.33.10:5984']);

$pokemons = $api->pokedex(2); //kanto region
$pokemon_data = json_decode($pokemons);

foreach ($pokemon_data->pokemon_entries as $row) {
	$pokemon = [
		'id' => $row->entry_number,
		'name' => $row->pokemon_species->name,
		'sprite' => "{$row->entry_number}.png",
		'doc_type' => "pokemon"
	];

	Image::open("https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/{$row->entry_number}.png")
	     ->resize(50, 50)
			 ->save('public/img/' . $row->entry_number . '.png');
			
	$client->request('POST', "/pokespawn", [
		'headers' => [
				'Content-Type' => 'application/json'
		],
		'body' => json_encode($pokemon)
	]);

	echo $row->pokemon_species->name . "\n";
}
echo "done!";
