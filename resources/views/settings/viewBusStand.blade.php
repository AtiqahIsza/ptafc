@extends('layouts.app')

@section('content')
    <!-- Map Script -->
    <script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCGDHu1sOYoepvEmSLmatyJVGNvCCONh48&libraries=drawing&callback=initMap&v=weekly&channel=2"> </script>
    <script>
        let map;
        const coordPoly = [];
        const coordBus = [];

        function initMap() {
            let forZoomArr = <?php echo json_encode($busStand); ?>;
            let forZoom;

            for (j = 0; j < forZoomArr.length; j++) {
                forZoom = new google.maps.LatLng(
                    parseFloat(forZoomArr[j]['latitude']),
                    parseFloat(forZoomArr[j]['longitude'])
                );
            }
            map = new google.maps.Map(document.getElementById("map"), {
                zoom: 13,
                center: forZoom, // Center the map on Malaysia.
            });

            let routeArr = <?php echo json_encode($routeMaps); ?>;
            for (i = 0; i < routeArr.length; i++) {
                coordPoly[i] = new google.maps.LatLng(
                    parseFloat(routeArr[i]['latitude']),
                    parseFloat(routeArr[i]['longitude'])
                );
            }

            const routeMap = new google.maps.Polygon({
                paths: coordPoly,
                strokeColor: "#FF0000",
                strokeOpacity: 0.8,
                strokeWeight: 3,
                fillColor: "#FF0000",
                fillOpacity: 0.35,
            });
            routeMap.setMap(map);

            let  busStandArr = <?php echo json_encode($busStand); ?>;
            for (i = 0; i < busStandArr.length; i++) {
                coordBus[i] = new google.maps.LatLng(
                    parseFloat(busStandArr[i]['latitude']),
                    parseFloat(busStandArr[i]['longitude'])
                );
                const busStandMarker = new google.maps.Marker({
                    position: coordBus[i],
                    map: map,
                    icon: {
                        url: '/images/bus-stop_blue.png',
                        scaledSize: new google.maps.Size(50, 50),
                    }
                });

                //alert(busStandArr[i]['description'])
                let contentString =
                    '<div id="content">' +
                        '<span>'+ busStandArr[i]['description'] +'</span>' +
                    '</div';

                const infowindow = new google.maps.InfoWindow({
                    content: contentString,
                });

                //listener to click on info window
                busStandMarker.addListener("click", () => {
                    infowindow.open({
                        anchor: busStandMarker,
                        map,
                        shouldFocus: false,
                    });
                });

                const busStandCircle = new google.maps.Circle({
                    strokeColor: '#000000',
                    strokeOpacity: 0.8,
                    strokeWeight: 2,
                    fillColor: '#0C0E71',
                    fillOpacity: 0.35,
                    map: map,
                    center: coordBus[i],
                    radius: parseFloat(busStandArr[i]['radius']),
                });
            }

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
                <h2>View Bus Stand for Route <span>{{ $routes->route_name }}</span></h2>
            </div>
            <div class="card card-body border-0 shadow table-wrapper table-responsive">
                <table class="table table-borderless">
                    <tbody>
                    <tr>
                        <td>&nbsp;</td>
                        <td style="background-color: #FF0000;width: 20px"> </td>
                        <td><strong>Route Map</strong></td>
                        <td>&nbsp;</td>
                        <td style="width: 70px;">
                            <img src="{{ url('/images/bus-stop_blue.png') }}" alt="bus-stop" style="width: 70px;">
                        </td>
                        <td><strong>Bus Stand</strong></td>
                        <td>&nbsp;</td>
                        <td style="background-color: #0C0E71;width: 20px"> </td>
                        <td><strong>Radius of Bus Stand</strong></td>
                        @if ($updatedBy->updated_at != NULL && $updatedBy->updated_by != NULL)
                            <td class="border-gray-200">{{ __('Updated At: ' . $updatedBy->updated_at ) }}</td>
                            <td class="border-gray-200">{{ __('Updated By: ' . $updatedBy->updatedBy->username) }}</td>
                        @else
                            <td class="border-gray-200">{{ __('Updated At: -' ) }}</td>
                            <td class="border-gray-200">{{ __('Updated By: -') }}</td>
                        @endif
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td colspan="12">&nbsp;</td>
                    </tr>
                    <tr>
                        <td colspan="12">
                            <div id="map"></div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="12">
                            <div class="d-block mb-md-0" style="position: relative">
                                <input type="button" onclick="window.history.back()" class="btn btn-warning" value="Back">
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

