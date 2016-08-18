<?php
namespace App\Controllers;

class HomeController
{
  protected $renderer;

  public function __construct($renderer)
  {
    $this->renderer = $renderer;
    $this->db = new \App\Utils\DB;
  }

  public function index($request, $response, $args)
  {
    return $this->renderer->render($response, 'index.html', $args);
  }

  public function search()
  {
  	$name = $_GET['name'];
    return $this->db->searchPokemon($name);
  }

  public function saveLocation()
  {
  	$id = $_POST['pokemon_id'];
  	return $this->db->savePokemonLocation($id, $_POST['pokemon_lat'], $_POST['pokemon_lng']);
  }

  public function fetch()
  {
    return json_encode($this->db->fetchPokemons($_POST['north_east'], $_POST['south_west']));
  }
}
