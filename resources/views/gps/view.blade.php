@extends('layouts.app')

@section('content')
    <!-- Map Script -->
    <script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCGDHu1sOYoepvEmSLmatyJVGNvCCONh48&libraries=drawing&callback=initMap&v=weekly&channel=2"> </script>
    <script>
        let map;
        const coordBus = [];

        function initMap() {
            let forZoomArr = <?php echo json_encode($vehiclePosition); ?>;
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

            let  vehicleArr = <?php echo json_encode($vehiclePosition); ?>;
            for (i = 0; i < vehicleArr.length; i++) {
                coordBus[i] = new google.maps.LatLng(
                    parseFloat(vehicleArr[i]['latitude']),
                    parseFloat(vehicleArr[i]['longitude'])
                );
                const vehicleMarker = new google.maps.Marker({
                    position: coordBus[i],
                    map: map,
                });

                let contentString =
                    '<div id="content">' +
                    '<span>'+ vehicleArr[i]['speed'] +'</span>' +
                    '</div';

                const infowindow = new google.maps.InfoWindow({
                    content: contentString,
                });

                //listener to click on info window
                vehicleMarker.addListener("click", () => {
                    infowindow.open({
                        anchor: vehicleMarker,
                        map,
                        shouldFocus: false,
                    });
                });
            }
            const polyView = new google.maps.Polygon({
                paths: coordBus,
                strokeColor: "#FF0000",
                strokeOpacity: 0.8,
                strokeWeight: 3,
                fillColor: "#FF0000",
                fillOpacity: 0.0,
            });
            polyView.setMap(map)

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
                <h2>View Vehicle Position History</h2>
            </div>
            <div class="card card-body border-0 shadow table-wrapper table-responsive">
                <table class="table table-borderless">
                    <tbody>
                    <tr>
                        <td><strong>Bus Registration No: </strong> {{ $buses->bus_registration_number }}</td>
                        <td><strong>Date: </strong> {{ $date }}</td>
                    </tr>
                    <tr>
                        @if($exist)
                            <td colspan="2">
                                <div id="map"></div>
                            </td>
                        @else
                            <td colspan="2">
                                <strong>No Records Found</strong>
                            </td>
                        @endif
                    </tr>
                    <tr>
                        <td colspan="2">
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

