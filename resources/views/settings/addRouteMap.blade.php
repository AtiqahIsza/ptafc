@extends('layouts.app')

@section('content')
    <!-- Map Script -->
    <script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCGDHu1sOYoepvEmSLmatyJVGNvCCONh48&libraries=drawing&callback=initMap&v=weekly&channel=2"> </script>
    <script>
        let poly;
        let map;

        function initMap() {
            map = new google.maps.Map(document.getElementById("map"), {
                zoom: 7,
                center: { lat: 3.140853, lng: 101.693207 }, // Center the map on Malaysia.
            });
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
            });
        }
    </script>

    <div class="row">
        @if (session()->has('message'))
            <div class="alert alert-success">
                {{ session('message') }}
            </div>
        @endif
        <div class="main py-4">
            <div class="d-block mb-md-0" style="position: relative">
                <h2>Create Route Map for <span>{{ $route->route_name }}</span></h2>
            </div>
            <div class="card card-body border-0 shadow table-wrapper table-responsive">
                <!-- Form -->
                <form>
                    @csrf
                    <table class="table table-hover">
                        <thead>
                            <th colspan="2" class="border-gray-200">{{ __('Company Name: ' . $route->company->company_name) }}</th>
                            <th colspan="2" class="border-gray-200">{{ __('Route Name: ' . $route->route_number . ' ' . $route->route_name) }}</th>
                        </thead>
                        <tbody>
                        <tr>
                            <td colspan="4"><span class="fw-normal">Create Route Map here:</span></td>
                        </tr>
                        <tr>
                            <td colspan="4">
                                <div id="map" class="map"></div>
                                <input id="routeID" class="border-gray-200" type="hidden" value="{{ $route->id }}">
                            </td>
                        </tr>
                        <tr>
                            <td colspan="4">
                                <div class="d-block mb-md-0" style="position: relative">
                                    <button id="saveButton" onclick="saveBtnOnClick()" class="btn btn-primary">Save</button>
                                    <input type="button" onclick="window.history.back()" class="btn btn-warning" value="Back">
                                </div>
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
        //const path = poly.getPath();

        const saveBtnOnClick = () => {
            //e.preventDefault();
            loopMarker(poly);
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                url: "{{ route('storeRouteMap') }}",
                type: 'POST',
                data: {markers : markers},
                success: function (response) {
                    if(response) {
                        window.location = response.payload;
                    }
                },
                error: function (response) {
                    //console.log("Error " + response);
                }
            })
        }

        function loopMarker(poly) {
            const path = poly.getPath();
            path.forEach((point, sequence) => addMarker(point, sequence));
        }

        function addMarker(point, sequence){
            markerLastAdded = point;
            markers.push({
                lat: point.lat(),
                long: point.lng(),
                sequence: sequence,
                route_id: routeId
            });
        }
    </script>
@endsection

