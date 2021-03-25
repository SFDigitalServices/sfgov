// Reference: https://developers.google.com/maps/documentation/javascript/examples/places-autocomplete

function locationAutocomplete() {
  const input = document.getElementById("edit-location");
  const options = {
    componentRestrictions: { country: "us" },
    fields: ["geometry", "name", "formatted_address"],
    origin: { lat: 37.7576792, lng: -122.5078107 },
    strictBounds: false,
  };
  const autocomplete = new google.maps.places.Autocomplete(input, options);
  const infowindow = new google.maps.InfoWindow();
  const infowindowContent = document.getElementById("infowindow-content");
  infowindow.setContent(infowindowContent);

  autocomplete.addListener("place_changed", () => {
    infowindow.close();
    const place = autocomplete.getPlace();

    input.setAttribute("data-lat", place.geometry.location.lat());
    input.setAttribute("data-lng", place.geometry.location.lng());

    if (!place.geometry || !place.geometry.location) {
      window.alert("No details available for input: '" + place.name + "'");
      return;
    }
  });
}
