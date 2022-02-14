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
                                <div id="map"></div>
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

    <!-- Map Script -->
    <script
        src="https://maps.googleapis.com/maps/api/js?key=&callback=initMap&v=weekly&channel=2"
        async></script>
    <script>
        // This example creates a 2-pixel-wide red polyline showing the path of
        // the first trans-Pacific flight between Oakland, CA, and Brisbane,
        // Australia which was made by Charles Kingsford Smith.
        function initMap() {
            const map = new google.maps.Map(document.getElementById("map"), {
                zoom: 3,
                center: { lat: 0, lng: -180 },
                mapTypeId: "terrain",
            });
            const flightPlanCoordinates = [
                { lat: 37.772, lng: -122.214 },
                { lat: 21.291, lng: -157.821 },
                { lat: -18.142, lng: 178.431 },
                { lat: -27.467, lng: 153.027 },
            ];
            const flightPath = new google.maps.Polyline({
                path: flightPlanCoordinates,
                geodesic: true,
                strokeColor: "#FF0000",
                strokeOpacity: 1.0,
                strokeWeight: 2,
            });

            flightPath.setMap(map);
        }
    </script>
@endsection

