// Reference: https://developers.google.com/maps/documentation/javascript/examples/places-autocomplete

function locationAutocomplete() {
  const input = document.getElementById("edit-location");
  const neBound = new google.maps.LatLng(38.015782, -121.5566);
  const swBound = new google.maps.LatLng(37.003, -122.928461);
  const bayAreaBounds = new google.maps.LatLngBounds(swBound, neBound);
  const options = {
    componentRestrictions: { country: "US" },
    fields: ["geometry", "name"],
    strictBounds: false,
    bounds: bayAreaBounds,
  };
  const autocomplete = new google.maps.places.Autocomplete(input, options);
  const infoWindow = new google.maps.InfoWindow();

  autocomplete.addListener("place_changed", () => {
    infoWindow.close();
    const place = autocomplete.getPlace();

    input.setAttribute("data-lat", place.geometry.location.lat());
    input.setAttribute("data-lng", place.geometry.location.lng());
  });
}
