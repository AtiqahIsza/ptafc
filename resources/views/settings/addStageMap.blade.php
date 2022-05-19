@extends('layouts.app')

@section('content')
    <!-- Map Script -->
    <script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCGDHu1sOYoepvEmSLmatyJVGNvCCONh48&libraries=drawing&callback=initMap&v=weekly&channel=2"> </script>
    <script>
        let poly;
        let map;
        const coords = [];

        function initMap() {
            // Show polygon of selected route
            let routeArr = <?php echo json_encode($routeMaps); ?>;
            if (routeArr.length == 0) {
                alert('No route map defined. Please add route map first...');
                map = new google.maps.Map(document.getElementById("map"), {
                    zoom: 12,
                    center: { lat: 3.140853, lng: 101.693207 },
                });
            }else {
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
                    strokeColor: "#000000",
                    strokeOpacity: 0.8,
                    strokeWeight: 3,
                    fillColor: "#fff705",
                    fillOpacity: 0.50,
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
                <h2>Create Stage Map for <span>{{ $stage->stage_name }}</span></h2>
            </div>
            <div class="card card-body border-0 shadow table-wrapper table-responsive">
                <!-- Form -->
                <form>
                    @csrf
                    <table class="table table-hover">
                        <thead>
                        <th class="border-gray-200">{{ __('Company Name:') }}</th>
                        <th class="border-gray-200"><span class="badge bg-primary">{{ $stage->route->company->company_name }}</span></th>
                        <th class="border-gray-200">{{ __('Route Name:') }}</th>
                        <th class="border-gray-200"><span class="badge bg-primary">{{ $stage->route->route_name }}</span></th>
                        </thead>
                        <tbody>
                        <tr>
                            <td colspan="4"><span class="fw-normal">Create Stage Map here:</span></td>
                        </tr>
                        <tr>
                            <td colspan="4">
                                <div id="map" class="map"></div>
                                <input id="stageID" class="border-gray-200" type="hidden" value="{{ $stage->id }}">
                            </td>
                        </tr>
                        <tr>
                            <td>
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

    <script>
        // This example creates an interactive map which constructs a polyline based on
        // user clicks. Note that the polyline only appears once its path property
        // contains two LatLng coordinates.
        let markerLastAdded = null;
        const markers = [];
        const buttonSave= $('#saveButton') //document.getElementById('saveButton');
        const stageId = $('#stageID').val(); //document.getElementById('routeID').value;
        //const path = poly.getPath();

        const saveBtnOnClick = () => {
            //e.preventDefault();
            loopMarker(poly);
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                url: "{{ route('storeStageMap') }}",
                type: 'POST',
                data: {markers : markers},
                success: function (response) {
                    //console.log(response);
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
                stage_id: stageId
            });
        }
    </script>
@endsection

