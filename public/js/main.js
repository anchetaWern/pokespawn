var modal = $('#add-pokemon').remodal();
var north_east;
var south_west;
var map;

var markers = [];

function initMap() {
  var min_zoomlevel = 18;
  map = new google.maps.Map(document.getElementById('map'), {
    center: {lat: -33.8688, lng: 151.2195},
    disableDefaultUI: true,
    zoom: min_zoomlevel,
    mapTypeId: 'roadmap'
  });

  marker = new google.maps.Marker({
    map: map,
    position: map.getCenter(),
    draggable: true
  });

  marker.addListener('click', function(){
    var position = marker.getPosition();
    $('#pokemon_lat').val(position.lat());
    $('#pokemon_lng').val(position.lng());
    modal.open();
  });

  var header = document.getElementById('header');
  var input = document.getElementById('place');
  var searchBox = new google.maps.places.SearchBox(input);
  map.controls[google.maps.ControlPosition.TOP_LEFT].push(header);

  map.addListener('bounds_changed', function() {
    searchBox.setBounds(map.getBounds());
  });

  map.addListener('zoom_changed', function() {
    if (map.getZoom() < min_zoomlevel) map.setZoom(min_zoomlevel);
  });

  map.addListener('dragend', function() {
    markers.forEach(function(marker) {
      marker.setMap(null);
    });
    markers = [];

    marker.setPosition(map.getCenter());
    fetchPokemon();
  });

  searchBox.addListener('places_changed', function() {
    var places = searchBox.getPlaces();

    if (places.length == 0) {
      return;
    }

    var bounds = new google.maps.LatLngBounds();
    var place = places[0];
    if (!place.geometry) {
      return;
    }

    marker.setPosition(place.geometry.location);
    if (place.geometry.viewport) {
      bounds.union(place.geometry.viewport);
    } else {
      bounds.extend(place.geometry.location);
    }

    map.fitBounds(bounds);
    fetchPokemon();

  });
}

function fetchPokemon(){

  var bounds = map.getBounds();
  var north_east = [bounds.getNorthEast().lat(), bounds.getNorthEast().lng()];
  var south_west = [bounds.getSouthWest().lat(), bounds.getSouthWest().lng()];

  $.post(
    '/fetch',
    {
      north_east: north_east,
      south_west: south_west
    },
    function(response){
      var response = JSON.parse(response);
      response.rows.forEach(function(row){
        var position = new google.maps.LatLng(row.geometry.coordinates[0], row.geometry.coordinates[1]);
        var poke_marker = new google.maps.Marker({
          map: map,
          title: row.value[0],
          position: position,
          icon: 'img/' + row.value[1]
        });

        var infowindow = new google.maps.InfoWindow({
          content: "<strong>" + row.value[0] + "</strong>"
        });
        poke_marker.addListener('click', function() {
          infowindow.open(map, poke_marker);
        });
        markers.push(poke_marker);
      });
    }
  );
}

new autoComplete({
  selector: '#pokemon_name',
  source: function(term, response){
    $.getJSON('/search?name=' + term, function(data){
      response(data);
    });
  },
  renderItem: function (item, search){
      search = search.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
      var re = new RegExp("(" + search.split(' ').join('|') + ")", "gi");
      return '<div class="autocomplete-suggestion" data-id="' + item[1] + '" data-val="' + item[0] + '">' + item[0].replace(re, "<b>$1</b>")+'</div>';
  },
  onSelect: function(e, term, item){
    $('#pokemon_id').val(item.getAttribute('data-id'));
  }
});

$('#save-location').click(function(e){
  $.post('/save-location', $('#add-pokemon-form').serialize(), function(response){
    var data = JSON.parse(response);
    if(data.type == 'ok'){
      var position = new google.maps.LatLng(data.lat, data.lng);
      var poke_marker = new google.maps.Marker({
        map: map,
        title: data.name,
        position: position,
        icon: 'img/' + data.sprite
      });

      var infowindow = new google.maps.InfoWindow({
        content: "<strong>" + data.name + "</strong>"
      });
      poke_marker.addListener('click', function() {
        infowindow.open(map, poke_marker);
      });

      markers.push(poke_marker);
    }
    modal.close();
    $('#pokemon_id, #pokemon_lat, #pokemon_lng, #pokemon_name').val('');
  });

});

$('#add-pokemon-form').submit(function(e){
  e.preventDefault();
})
