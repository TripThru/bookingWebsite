<?php
/*
 ******************************************************************************
 *
 * Copyright (C) 2013 T Dispatch Ltd
 *
 * Licensed under the GPL License, Version 3.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.gnu.org/licenses/gpl-3.0.html
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 ******************************************************************************
*/

defined('INDEX_CALL') or die('You cannot access this page directly.');

$td = new TripThru();
//Check if user already logged in
if (!$td->Account_checkLogin()) {
    header('Location:' . $td->getHomeUrl());
    exit;
}
?>
<style>
#trip-info {
margin-top: 10px;
margin-bottom: 10px;
text-indent: 15px;
font-size: 16px;
white-space: nowrap;
overflow: hidden;
text-overflow: ellipsis;
}

#trip-info p {
	margin: 0px;
	padding: 0;
}

#trip-info div {
	float: left;
}
</style>


<div id="maincol" >
    <!--TRACKING INFO CONTAINER-->
    <div class="account_fields_cont vehicle_tracking_page box-container">
        <h1>Track your vehicle</h1>
		<div id="trip-info">
            <div>
                <p>
                    <span style='font-weight: bold;'>TripID: </span>
                    <span id="selectedTripID" />
                </p>
            </div>
            <br />
            <div>
                <p>
                    <span style='font-weight: bold;'>Network ID: </span>
                    <span id="selectedNetworkID" />
                </p>
            </div>
            <br />
			<div>
				<p>
					<span style='font-weight: bold;'>Passenger: </span>
					<span id="selectedTripPassengerName" />
				</p>
			</div>
			<div>
				<p>
					<span style='font-weight: bold;'>Pickup time: </span>
					<span id="selectedTripPickupTime" />
				</p>
			</div>
			<br />
			<div>
				<p>
					<span style='font-weight: bold;'>Pickup: </span>
					<span id="selectedTripPickupLocation" />
				</p>
			</div>
			<br />
			<div>
				<p>
					<span style='font-weight: bold;'>Origin: </span>
					<span id="selectedTripOriginatingNetwork"></span>
				</p>
			</div>
			<div>
				<p>
					<span style='font-weight: bold;'>Servicing: </span>
					<span id="selectedTripServicingNetwork"></span>
				</p>
			</div>
			<br />
			<div>
				<p>
					<span style='font-weight: bold;'>Status: </span>
					<span id="selectedTripStatus">Select a trip to track</span>
				</p>
			</div>
			<div>
				<p>
					<span style='font-weight: bold;'>ETA: </span>
					<span id="selectedTripETA" />
				</p>
			</div>
			<div>
				<p>
					<span style='font-weight: bold;'>Fare: </span>$
					<span id="selectedTripFare" />
				</p>
			</div>
			<br />
			<div>
				<p>
					<span style='font-weight: bold;'>Driver: </span>
					<span id="selectedTripDriverName" />
				</p>
				<p>
					<span style='font-weight: bold;'>Driver location: </span>
					<span id="selectedTripDriverLocation" />
				</p>
				<p>
					<span style='font-weight: bold;'>Drop off: </span>
					<span id="selectedTripDropoffLocation" />
				</p>
			</div>
		</div>
        <!--Tracking map-->
        <div id="map-canvas" class="tracking_map" ></div>
        <!--Tracking map-->
    </div>
    <!--TRACKING INFO CONTAINER-->

    <!--MAP CONTAINER-->
    <div id="right_float_cont">
        <div id="right_ad" class="box-container">
            <h2>Tips</h2>
            <p></p>
        </div>
        <?php
        //include 'map.php';
        ?>
    </div>
    <!--MAP CONTAINER-->
    <div style="clear:both"></div>
    <script type="text/javascript">
        function getURLParameter(name) {
            return decodeURIComponent((new RegExp('[?|&]' + name + '=' + '([^&;]+?)(&|#|;|$)').exec(location.search)||[,""])[1].replace(/\+/g, '%20'))||null;
        }


        $(function() {

            var pk = getURLParameter("pk");
			      var partnerId = getURLParameter("partnerId");
			      var pickup_location = { lat: getURLParameter('pickup_location_lat'),  lng: getURLParameter('pickup_location_lng') }; 
			      var dropoff_location = { lat: getURLParameter('dropoff_location_lat'),  lng: getURLParameter('dropoff_location_lng') }; 
			      var pickup_time = getURLParameter('pickup_time');
			      var fare = getURLParameter('fare');
			      var driverInitialLocation = null;

            if($(".vehicle_tracking_page").length)
            {
				
				var completed = false; 
                //Load maps first time
                $.post(window.location.pathname.replace(/^\/([^\/]*).*$/, '$1'),{
                    JSON:true,
                    TYPE:'getTrack',
                    bookingPk:pk,
					partnerId:partnerId
                },function(data){
					var stat = '';
					if(data.result_code == 404 || data.status == "completed") {
						setTripInfo(data);
						completed = true;
						return;
					}
					setTripInfo(data);
                    if(data.driver && data.driver.location != null && !$.isEmptyObject(data.driver.location))
                    {

						var pickupLocation = new google.maps.LatLng(pickup_location.lat, pickup_location.lng);
						var dropoffLocation = new google.maps.LatLng(dropoff_location.lat, dropoff_location.lng);
            var driverLocation;
						if(data.driver) {
						  driverLocation = new google.maps.LatLng(data.driver.location.lat, data.driver.location.lng);
							driverInitialLocation = new google.maps.LatLng(data.driver.location.lat,data.driver.location.lng);
						}
						

						var directionsDisplay = null;
						var directionsDisplay2 = null;
						
                        //Setup google maps for first time
                        var mapOptions = {
                            center: driverLocation,
                            zoom: 15,
                            mapTypeId: google.maps.MapTypeId.ROADMAP
                        };
                        map = new google.maps.Map(document.getElementById("map-canvas"), mapOptions);
                        
                        driverMarker = new google.maps.Marker({
                            position: driverLocation,
                            map: map,
                            draggable:false,
							icon: "http://chart.apis.google.com/chart?chst=d_map_pin_icon&chld=taxi|FFFF00",
							title: 'Driver'
                        });
						
						pickupMarker = new google.maps.Marker({
							position: pickupLocation,
							map: map,
							icon: "http://chart.apis.google.com/chart?chst=d_map_pin_icon&chld=home|FFFF00",
							title: 'Pickup'
						});
						
						dropoffMarker = new google.maps.Marker({
							position: dropoffLocation,
							map: map,
							icon: "http://chart.apis.google.com/chart?chst=d_map_pin_icon&chld=cafe|FFFF00",
							title: 'Destination'
						});

						initialMarker = new google.maps.Marker({
								position: driverInitialLocation,
								map: map,
								icon: "http://www.mricons.com/store/png/113267_25418_16_flag_icon.png",
								title: 'Initial'
							});
						                            var routes = [];
                                switch (data.status) {
                                    case "en_route":
                                        routes = [{ origin: driverInitialLocation, destination: driverLocation }];
                                        break;
                                    case "picked_up":
                                        routes = [{ origin: driverInitialLocation, destination: pickupLocation }, { origin: pickupLocation, destination: driverLocation }];
                                        break;
                                    case "completed":
                                        routes = [{ origin: driverInitialLocation, destination: pickupLocation }, { origin: pickupLocation, destination: dropoffLocation }];
                                        break;
                                }

                                var rendererOptions = {
                                    preserveViewport: true,
                                    suppressMarkers: true,
                                    polylineOptions: {
                                        strokeColor: "#8B0000",
                                        strokeOpacity: 0.8,
                                        strokeWeight: 5
                                    },
                                };

                                var rendererOptions2 = {
                                    preserveViewport: true,
                                    suppressMarkers: true,
                                    polylineOptions: {
                                        strokeColor: "#008000",
                                        strokeOpacity: 0.8,
                                        strokeWeight: 5
                                    },
                                };
                                var directionsService = new google.maps.DirectionsService();
                                var directionsService2 = new google.maps.DirectionsService();

                                var boleanFirst = true;

                                if (directionsDisplay != null) {
                                    directionsDisplay.setMap(null);
                                    directionsDisplay = null;
                                }
                                if (directionsDisplay2 != null) {
                                    directionsDisplay2.setMap(null);
                                    directionsDisplay2 = null;
                                }

                                routes.forEach(function (route) {
                                    var request = {
                                        origin: route.origin,
                                        destination: route.destination,
                                        travelMode: google.maps.TravelMode.DRIVING
                                    };

                                    if (boleanFirst) {
                                        directionsDisplay = new google.maps.DirectionsRenderer(rendererOptions);
                                        directionsDisplay.setMap(map);
                                    }
                                    else {
                                        directionsDisplay2 = new google.maps.DirectionsRenderer(rendererOptions2);
                                        directionsDisplay2.setMap(map);
                                    }

                                    if (boleanFirst) {
                                        directionsService.route(request, function (result, status) {
                                            if (status == google.maps.DirectionsStatus.OK) {
                                                directionsDisplay.setDirections(result);
                                            }
                                        });
                                        boleanFirst = false;
                                    } else {
                                        directionsService2.route(request, function (result, status) {
                                            if (status == google.maps.DirectionsStatus.OK) {
                                                directionsDisplay2.setDirections(result);
                                            }
                                        });
                                    }
                                });
                    }else{
                        $(".tracking_map").text("Driver location unavailable");
                    }
                },"json");
				
				var driverLocationInitial = null;

                //Reload map marker and location every 15 sec
				if(!completed){
					var updating = false;
					var loop = setInterval(function(){
						if(!updating)
						{
						updating = true;
						$.post(window.location.pathname.replace(/^\/([^\/]*).*$/, '$1'),{
							JSON:true,
							TYPE:'getTrack',
							bookingPk:pk,
							partnerId:partnerId
						},function(data){
							if(data.result_code == 'NotFound' || data.status == "Complete") {
								setTripInfo(data);
								updateMap(data);
								clearInterval(loop);
								return;
							} else {
								if(data.status){
									setTripInfo(data);
									updateMap(data);
									}
							}

							updating = false;
						},"json").error( function() {
							updating = false;
						});

						}

					},15000);
				}
				
				function setTripInfo(trip){
                    var pk = getURLParameter("pk");
                    var partnerId = getURLParameter("partnerId");
					var passengerName = trip.customer && trip.customer.name ? trip.customer.name : 'Not available';
					var pickupTime = pickup_time;
					var status = trip.status ? trip.status : (trip.result_code === 404 ? 'Complete' : 'Not available');
					var eta = trip.eta ? new Date(trip.eta) : 'Not available';
					var fareTag = Math.round(fare).toFixed(2);
					var driverName = trip.driver && trip.driver.name ? trip.driver.name : 'Not available';
                    var pickupLocationName = getAddress(pickup_location.lat,pickup_location.lng);
                    var dropoffLocationName = getAddress(dropoff_location.lat,dropoff_location.lng);
					var driverLocationName = trip.driver && trip.driver.location ? getAddress(trip.driver.location.lat,trip.driver.location.lng) : 'Not available';
					var originatingNetworkName = trip.originatingNetwork ? trip.originatingNetwork.name : 'Not available';
					var servicingNetworkName = trip.servicingNetwork ? trip.servicingNetwork.name : 'Not available';

                    $("#selectedTripID").hide().html(pk).fadeIn();
                    $("#selectedNetworkID").hide().html(partnerId).fadeIn();
					$("#selectedTripPassengerName").hide().html(passengerName).fadeIn();
					$("#selectedTripPickupTime").hide().html(pickupTime).fadeIn();
					$("#selectedTripPickupLocation").hide().html(pickupLocationName).fadeIn();
					$("#selectedTripStatus").hide().html(status).fadeIn();
					$("#selectedTripETA").hide().html(eta).fadeIn();
					$("#selectedTripFare").hide().html(fareTag).fadeIn();
					$("#selectedTripDropoffLocation").hide().html(dropoffLocationName).fadeIn();
					$("#selectedTripDriverName").hide().html(driverName).fadeIn();
					$("#selectedTripDriverLocation").hide().html(driverLocationName).fadeIn();
					$("#selectedTripOriginatingNetwork").hide().html(originatingNetworkName).fadeIn();
					$("#selectedTripServicingNetwork").hide().html(servicingNetworkName).fadeIn();
				}
				function getAddress(lat, lng) {
                    var urlJson = "http://maps.googleapis.com/maps/api/geocode/json?latlng=" + lat + "," + lng + "&sensor=false";
                    var json;

                    $.ajax({
                        url: urlJson,
                        dataType: 'json',
                        async: false,
                        success: function (data) {
                            json = data;
                        }
                    });
                    if (json.status === "OK") {
                        return json.results[0].formatted_address;
                    }
                    return "Not available";
                }
                var directionsDisplay = null;
                var directionsDisplay2 = null;
				function updateMap(data){
					if(data.driver && data.driver.location && !$.isEmptyObject(data.driver.location))
					{
						var driverLocation = new google.maps.LatLng(data.driver.location.lat, data.driver.location.lng);
						var pickupLocation = new google.maps.LatLng(pickup_location.lat, pickup_location.lng);
						var dropoffLocation = new google.maps.LatLng(dropoff_location.lat, dropoff_location.lng);

						if(!map){

							//Setup google maps for first time
							var mapOptions = {
								center: driverLocation,
								zoom: 15,
								mapTypeId: google.maps.MapTypeId.ROADMAP
							};
							map = new google.maps.Map(document.getElementById("map-canvas"), mapOptions);
							
							initialMarker = new google.maps.Marker({
								position: driverInitialLocation,
								map: map,
								icon: "http://www.mricons.com/store/png/113267_25418_16_flag_icon.png",
								title: 'Initial'
							});

							driverMarker = new google.maps.Marker({
								position: driverLocation,
								map: map,
								draggable:false,
								icon: "http://chart.apis.google.com/chart?chst=d_map_pin_icon&chld=taxi|FFFF00",
								title: 'Driver'
							});
							
							pickupMarker = new google.maps.Marker({
								position: pickupLocation,
								map: map,
								icon: "http://chart.apis.google.com/chart?chst=d_map_pin_icon&chld=home|FFFF00",
								title: 'Pickup'
							});
							
							dropoffMarker = new google.maps.Marker({
								position: dropoffLocation,
								map: map,
								icon: "http://chart.apis.google.com/chart?chst=d_map_pin_icon&chld=cafe|FFFF00",
								title: 'Destination'
							});


						}
						//Setup google maps center and new vehicle location
						driverMarker.setPosition(driverLocation);

							
                            var routes = [];
                                switch (data.status) {
                                    case "en_route":
                                        routes = [{ origin: driverInitialLocation, destination: driverLocation }];
                                        break;
                                    case "picked_up":
                                        routes = [{ origin: driverInitialLocation, destination: pickupLocation }, { origin: pickupLocation, destination: driverLocation }];
                                        break;
                                    case "completed":
                                        routes = [{ origin: driverInitialLocation, destination: pickupLocation }, { origin: pickupLocation, destination: dropoffLocation }];
                                        break;
                                }

                                var rendererOptions = {
                                    preserveViewport: true,
                                    suppressMarkers: true,
                                    polylineOptions: {
                                        strokeColor: "#8B0000",
                                        strokeOpacity: 0.8,
                                        strokeWeight: 5
                                    },
                                };

                                var rendererOptions2 = {
                                    preserveViewport: true,
                                    suppressMarkers: true,
                                    polylineOptions: {
                                        strokeColor: "#008000",
                                        strokeOpacity: 0.8,
                                        strokeWeight: 5
                                    },
                                };
                                var directionsService = new google.maps.DirectionsService();
                                var directionsService2 = new google.maps.DirectionsService();

                                var boleanFirst = true;

                                if (directionsDisplay != null) {
                                    directionsDisplay.setMap(null);
                                    directionsDisplay = null;
                                }
                                if (directionsDisplay2 != null) {
                                    directionsDisplay2.setMap(null);
                                    directionsDisplay2 = null;
                                }

                                routes.forEach(function (route) {
                                    var request = {
                                        origin: route.origin,
                                        destination: route.destination,
                                        travelMode: google.maps.TravelMode.DRIVING
                                    };

                                    if (boleanFirst) {
                                        directionsDisplay = new google.maps.DirectionsRenderer(rendererOptions);
                                        directionsDisplay.setMap(map);
                                    }
                                    else {
                                        directionsDisplay2 = new google.maps.DirectionsRenderer(rendererOptions2);
                                        directionsDisplay2.setMap(map);
                                    }

                                    if (boleanFirst) {
                                        directionsService.route(request, function (result, status) {
                                            if (status == google.maps.DirectionsStatus.OK) {
                                                directionsDisplay.setDirections(result);
                                            }
                                        });
                                        boleanFirst = false;
                                    } else {
                                        directionsService2.route(request, function (result, status) {
                                            if (status == google.maps.DirectionsStatus.OK) {
                                                directionsDisplay2.setDirections(result);
                                            }
                                        });
                                    }
                                });

						map.setCenter(driverLocation);
					}else{
						$(".tracking_map").text("Driver location unavailable");
					}
				}
            }
        });
    </script>
</div>
