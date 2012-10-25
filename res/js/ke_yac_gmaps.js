var map;
var geocoder = new google.maps.Geocoder();
var latlng;
function gmap_init() {
	geocoder.geocode({ 'address': mapAddress}, function(results, status) {
		if (status == google.maps.GeocoderStatus.OK) {
			var myOptions = {
				zoom: mapZoom,
				center: results[0].geometry.location,
				mapTypeId: google.maps.MapTypeId.ROADMAP
			};
			var map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
			marker = new google.maps.Marker({
				position: results[0].geometry.location,
				map: map,
				draggable: false
			});
			var infowindow = new google.maps.InfoWindow({
				content: infocontent,
				maxWidth: 400
			  });
			  google.maps.event.addListener(marker, 'click', function() {
				infowindow.open(map,marker);
			  });
		}
		else alert(status);
	});
}

jQuery(window).load(function() {
	gmap_init();
});

