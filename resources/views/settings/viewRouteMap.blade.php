@extends('layouts.app')

@section('content')
    <!-- Map Script -->
    <script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCGDHu1sOYoepvEmSLmatyJVGNvCCONh48&libraries=drawing&callback=initMap&v=weekly&channel=2"> </script>
    <script>
        let map;
        const coords = [];

        function initMap() {
            let routeArr = <?php echo json_encode($routeMaps); ?>;
            for (i = 0; i < routeArr.length; i++) {
                coords[i] = new google.maps.LatLng(
                    parseFloat(routeArr[i]['latitude']),
                    parseFloat(routeArr[i]['longitude'])
                );
            }

            map = new google.maps.Map(document.getElementById("map"), {
                zoom: 13,
                center: coords[0], // Center the map on Malaysia.
            });

            const routeMap = new google.maps.Polygon({
                paths: coords,
                strokeColor: "#FF0000",
                strokeOpacity: 0.8,
                strokeWeight: 3,
                fillColor: "#FF0000",
                fillOpacity: 0.35,
            });

            routeMap.setMap(map);
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
                <h2>View Route Map for <span>{{ $route->route_name }}</span></h2>
            </div>
            <div class="card card-body border-0 shadow table-wrapper table-responsive">
                <table class="table table-borderless">
                    <thead>
                        <th class="border-gray-200">{{ __('Company Name:') }}</th>
                        <th class="border-gray-200"><span class="badge bg-primary">{{ $route->company->company_name }}</span></th>
                        <th>&nbsp;</th>
                        <th class="border-gray-200">{{ __('Sector Name:') }}</th>
                        <th class="border-gray-200"><span class="badge bg-primary">{{ $route->sector->sector_name }}</span></th>
                        <th>&nbsp;</th>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="2">&nbsp;</td>
                            <td style="background-color: #FF0000;width: 20px"> </td>
                            <td><strong>Route Map</strong></td>
                            <td colspan="2">&nbsp;</td>
                        </tr>
                        <tr>
                            <td colspan="6">&nbsp;</td>
                        </tr>
                        <tr>
                            <td colspan="6">
                                <div id="map"></div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="6">
                                <div class="d-block mb-md-0" style="position: relative">
                                    <input type="button" onclick="window.location='{{route("manageRoute")}}'"{{--onclick="window.history.back()"--}} class="btn btn-warning" value="Back">
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

