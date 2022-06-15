@extends('layouts.app')

@section('content')
    <!-- Map Script -->
    <script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCGDHu1sOYoepvEmSLmatyJVGNvCCONh48&libraries=drawing&callback=initMap&v=weekly&channel=2"> </script>
    <script>
        let map;
        const coordBus = [];

        function initMap() {
            let forZoomArr = <?php echo json_encode($vehiclePosition); ?>;
            let forZoom = new google.maps.LatLng(
                    parseFloat(forZoomArr.latitude),
                    parseFloat(forZoomArr.longitude)
                );
                
            map = new google.maps.Map(document.getElementById("map"), {
                zoom: 20,
                center: forZoom// Center the map on Malaysia.
            });

            const vehicleMarker = new google.maps.Marker({
                position: forZoom,
                map: map,
            });

            let contentString =
                '<div id="content">' +
                '<span>'+ forZoomArr.speed +'</span>' +
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
                        <td><strong>Bus Registration No: </strong> {{ $viewedBus->bus_registration_number }}</td>
                        <td><strong>Date: </strong> {{ $viewedDate }}</td>
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

