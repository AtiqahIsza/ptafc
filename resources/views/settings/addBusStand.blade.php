@extends('layouts.app')

@section('content')
    <script async src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCGDHu1sOYoepvEmSLmatyJVGNvCCONh48&callback=initMap&libraries=&v=weekly&channel=2"></script>
    <script>
        let poly;
        let map;
        const coords = [];

        function initMap() {
            // Show polygon of selected route
            let routeArr = <?php echo json_encode($routeMaps); ?>;

            for (i = 0; i < routeArr.length; i++) {
                coords[i] = new google.maps.LatLng(
                    parseFloat(routeArr[i]['latitude']),
                    parseFloat(routeArr[i]['longitude'])
                );
            }

            map = new google.maps.Map(document.getElementById("map"), {
                zoom: 12,
                center: coords[0], // Center the map on 1st point of polygon route.
            });

            const routeMap = new google.maps.Polygon({
                paths: coords,
                strokeColor: "#FF0000",
                strokeOpacity: 0.8,
                strokeWeight: 3,
                fillColor: "#FF0000",
                fillOpacity: 0.35,
                clickable: false,
            });

            routeMap.setMap(map);

            poly = new google.maps.Polyline({
                strokeColor: "#000000",
                strokeOpacity: 1.0,
                strokeWeight: 3,
            });
            poly.setMap(map);
            // Add a listener for the click event
            map.addListener("click", addLatLng);

        }

        // Handles click events on a map, and adds a new point to the Polyline.
        function addLatLng(event) {
            const path = poly.getPath();

            // Because path is an MVCArray, we can simply append a new coordinate
            // and it will automatically appear.
            path.push(event.latLng);
            // Add a new marker at the new plotted point on the polyline.
            new google.maps.Marker({
                position: event.latLng,
                title: "#" + path.getLength(),
                map: map,
                icon: {
                    url: '/images/bus-stop_blue.png',
                    scaledSize: new google.maps.Size(50, 50),
                }
            });
        }
    </script>
    {{--<script>
        let poly;
        let map;
        const coords = [];

        function initMap() {
            map = new google.maps.Map(document.getElementById("map"), {
                zoom: 7,
                center: { lat: 3.140853, lng: 101.693207 }, // Center the map on Malaysia.
            });

            // Show polygon of selected route
            let routeArr = <?php echo json_encode($routeMaps); ?>;
            for (i = 0; i < routeArr.length; i++) {
                coords[i] = new google.maps.LatLng(
                    parseFloat(routeArr[i]['latitude']),
                    parseFloat(routeArr[i]['longitude'])
                );
            }

            const routeMap = new google.maps.Polygon({
                paths: coords,
                strokeColor: "#FF0000",
                strokeOpacity: 0.8,
                strokeWeight: 3,
                fillColor: "#FF0000",
                fillOpacity: 0.35,
            });

            routeMap.setMap(map);

            poly = new google.maps.Polyline({
                strokeColor: "#000000",
                strokeOpacity: 1.0,
                strokeWeight: 3,
            });
            poly.setMap(map);
            // Add a listener for the click event
            map.addListener("click", addLatLng);

        }

        // Handles click events on a map, and adds a new point to the Polyline.
        function addLatLng(event) {
            const path = poly.getPath();

            // Because path is an MVCArray, we can simply append a new coordinate
            // and it will automatically appear.
            path.push(event.latLng);
            // Add a new marker at the new plotted point on the polyline.
            circle = new google.maps.Circle({
                strokeColor: '#FF0000',
                strokeOpacity: 0.8,
                strokeWeight: 2,
                fillColor: '#FF0000',
                fillOpacity: 0.35,
                map: map,
                center: event.latLng,
                radius: 100,
            });
        }
    </script>--}}

    <div class="row">
        @if (session()->has('message'))
            <div class="alert alert-success">
                {{ session('message') }}
            </div>
        @endif
        <div class="main py-4">
            <div class="d-block mb-md-0" style="position: relative">
                <h2>Add Bus Stand</h2>
            </div>
            <div class="card card-body border-0 shadow table-wrapper table-responsive">
                <!-- Form -->
                <form>
                    @csrf
                    <table class="table table-hover">
                        <thead>
                            <th colspan="2" class="border-gray-200">{{ __('Company Name: ' . $routes->company->company_name) }}</th>
                            <th colspan="2" class="border-gray-200">{{ __('Route Name: ' . $routes->route_number . ' ' . $routes->route_name) }}</th>
                        </thead>
                        <tbody>
                        <tr>
                            <td><strong>Enter radius (in Meter):</strong></td>
                            <td colspan="3">
                                <input id="radius" class="border-gray-200" type="text" placeholder="Radius in Meter" required>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="4"><strong>Plot Bus Stand here:</strong></td>
                        </tr>
                        <tr>
                            <td colspan="4">
                                <div id="map" class="map"></div>
                                <input id="routeID" class="border-gray-200" type="hidden" value="{{ $routes->id }}">
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="text-align: left">
                                <input type="button" onclick="window.history.back()" class="btn btn-warning" value="Back">
                            </td>
                            <td colspan="2" style="text-align: right">
                                <button id="saveButton" onclick="saveBtnOnClick()" class="btn btn-gray-800">Save</button>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // This example creates an interactive map which constructs a polyline based on
        // user clicks. Note that the polyline only appears once its path property
        // contains two LatLng coordinates.
        let markerLastAdded = null;
        const markers = [];
        const buttonSave= $('#saveButton') //document.getElementById('saveButton');
        const routeId = $('#routeID').val(); //document.getElementById('routeID').value;
        let radiusInput;
        //const path = poly.getPath();

        const saveBtnOnClick = () => {
            //e.preventDefault();
            if($('#radius').val()=='') {
                alert('Please insert radius of bus stands');
            }else{
                radiusInput = $('#radius').val();
                loopMarker(poly);
                $.ajax({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    url: "{{ route('storeBusStand') }}",
                    type: 'POST',
                    data: {markers : markers},
                    success: function (response) {
                        console.log(response);
                    },
                    error: function (response) {
                        console.log("Error " + response);
                    }
                })
            }
        }

        function loopMarker(poly) {
            const path = poly.getPath();
            path.forEach((point, sequence) => addMarker(point, sequence));
        }

        //need to change radius below if radius above changed
        function addMarker(point, sequence){
            markerLastAdded = point;
            markers.push({
                lat: point.lat(),
                long: point.lng(),
                sequence: sequence,
                route_id: routeId,
                radius:radiusInput
            });
        }
    </script>
@endsection

