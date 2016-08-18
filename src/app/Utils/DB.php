<?php
namespace App\Utils;

class DB
{
  private $client;

  public function __construct()
  {
    $this->client = new \GuzzleHttp\Client([
      'base_uri' => 'http://' . getenv('COUCH_USER') . ':' . getenv('COUCH_PASS') . '@' . getenv('BASE_URI')      
      ]);
  }

  public function searchPokemon($name)
  {
    $unicode_char = '\ufff0';
    $data = [
      'include_docs' => 'true',
      'start_key' => '"' . $name . '"',
      'end_key' => '"' . $name . json_decode('"' . $unicode_char .'"') . '"'
    ];
    $doc = $this->makeGetRequest('/pokespawn/_design/pokemon/_view/by_name', $data);
    if (count($doc->rows) > 0) {
      $data = [];
      foreach ($doc->rows as $row) {
        $data[] = [
          $row->key,
          $row->id
        ];
      }
      return json_encode($data);
    }
    $result = ['no_result' => true];
    return json_encode($result);
  }

  public function makeGetRequest($endpoint, $data = [])
  {
    if (!empty($data)) {
      $response = $this->client->request('GET', $endpoint, [
        'query' => $data
      ]);
    } else {
      $response = $this->client->request('GET', $endpoint);
    }
    return $this->handleResponse($response);
  }

  private function makePostRequest($endpoint, $data)
  {
    $response = $this->client->request('POST', $endpoint, [
  		'headers' => [
  		    'Content-Type' => 'application/json'
  		],
  		'body' => json_encode($data)
  	]);
    return $this->handleResponse($response);
  }

  private function handleResponse($response)
  {
    $doc = json_decode($response->getBody()->getContents());
    return $doc;
  }

  private function isValidCoordinates($lat = '', $lng = '')
  {
    $coords_pattern = '/^[+\-]?[0-9]{1,3}\.[0-9]{3,}\z/';
    if (preg_match($coords_pattern, $lat) && preg_match($coords_pattern, $lng)) {
      return true;
    }
    return false;
  }

  public function savePokemonLocation($id, $lat, $lng)
  {
    $pokemon = $this->makeGetRequest("/pokespawn/{$id}");
    if (!empty($pokemon->name) && $this->isValidCoordinates($lat, $lng)) {
      $lat = (double) $lat;
      $lng = (double) $lng;
      $data = [
    		'name' => $pokemon->name,
    		'sprite' => $pokemon->sprite,
    		'loc' => [$lat, $lng],
    		'doc_type' => 'pokemon_location'
    	];

      $this->makePostRequest('/pokespawn', $data);
    	$pokemon_data = [
        'type' => 'ok',
    		'lat' => $lat,
    		'lng' => $lng,
    		'name' => $pokemon->name,
    		'sprite' => $pokemon->sprite
    	];
    	return json_encode($pokemon_data);
    }
    return json_encode(['type' => 'fail']);
  }

  public function fetchPokemons($north_east, $south_west)
  {
    $north_east = array_map('doubleval', $north_east);
    $south_west = array_map('doubleval', $south_west);
    $data = [
      'start_range' => json_encode($south_west),
      'end_range' => json_encode($north_east),
      'limit' => 100
    ];
    $pokemons = $this->makeGetRequest('/pokespawn/_design/location/_spatial/points', $data);
    return $pokemons;
  }
}
