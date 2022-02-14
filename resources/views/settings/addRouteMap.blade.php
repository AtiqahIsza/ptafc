@extends('layouts.app')

@section('content')
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
                            <th class="border-gray-200">{{ __('Company Name:') }}</th>
                            <th class="border-gray-200"><span class="badge bg-primary">{{ $route->company->company_name }}</span></th>
                            <th class="border-gray-200">{{ __('Sector Name:') }}</th>
                            <th class="border-gray-200"><span class="badge bg-primary">{{ $route->route_name }}</span></th>
                        </thead>
                        <tbody>
                        <tr>
                            <td colspan="4"><span class="fw-normal">Create Route Map here:</span></td>
                        </tr>
                        <tr>
                            <td colspan="4">
                                <div id="map" style="width: 300px; height: 300px;"></div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="4">
                                <button type="submit" class="btn btn-primary">Save</button>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </form>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script>
        // This example creates an interactive map which constructs a polyline based on
        // user clicks. Note that the polyline only appears once its path property
        // contains two LatLng coordinates.
        let poly;
        let map;

        function initMap() {
            map = new google.maps.Map(document.getElementById("map"), {
                zoom: 7,
                center: { lat: 41.879, lng: -87.624 }, // Center the map on Chicago, USA.
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
    <!-- Map Script -->
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyB41DRUbKWJHPxaFjMAwdrzWzbVKartNGg&callback=initMap&v=weekly&channel=2"
            async></script>
@endsection

