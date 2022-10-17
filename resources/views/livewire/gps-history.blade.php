<div class="main py-4">
    <div class="d-block mb-md-0" style="position: relative">
        <h2>GPS History</h2>
    </div>
    <div class="d-block mb-md-0" style="position: relative">
        <select wire:model="selectedTrip" class="form-select fmxw-200 d-none d-md-inline"  >
            <option value="">Choose Trip</option>
            <option value="ALL">All Trips</option>
            @php $i=1 @endphp
            @foreach($trips as $trip)
                <option value="{{$trip->trip_id}}">Trip {{$i}}</option>
                @php $i++ @endphp
            @endforeach
        </select>
    </div>
    <br>
    @if(count($trips)>0)
        <div class="card card-body border-0 shadow table-wrapper table-responsive">
            <h2 class="mb-4 h5">BUS REGISTRATION NUMBER: {{$chosenBus->bus_registration_number}}</h2>
            <table class="table table-hovered">
                <tbody>
                    @if($tripDetails)
                        <tr>
                            <th style="text-align: center;">No.</th>
                            <th style="text-align: center;">Start Trip</th>
                            <th style="text-align: center;">End Trip</th>
                            <th style="text-align: center;">Route Number</th>
                            <th style="text-align: center;">Trip Type</th>
                            <th style="text-align: center;">Collection of Farebox (RM)</th>
                            <th style="text-align: center;">Collection of Ridership</th>
                        </tr>
                        @php $j=1 @endphp
                        @foreach ($tripDetails as $tripDetail)
                            <tr>
                                <td style="text-align: center;"> {{ $j++ }}</td>
                                <td style="text-align: center;"> {{ $tripDetail->start_trip }}</td>
                                <td style="text-align: center;"> {{ $tripDetail->end_trip }}</td>
                                @if($tripDetail->route_id!=NULL)
                                    <td style="text-align: center;"> {{ $tripDetail->Route->route_number }}</td>
                                @else
                                    <td style="text-align: center;">No Data</td>
                                @endif
                                @if($tripDetail->trip_code==1)
                                    <td style="text-align: center;">Inbound</td>
                                @else
                                    <td style="text-align: center;">Outbound</td>
                                @endif
                                <td style="text-align: center;"> {{ $tripDetail->total_adult_amount + $tripDetail->total_concession_amount}}</td>
                                <td style="text-align: center;"> {{ $tripDetail->total_adult + $tripDetail->total_concession }}</td>
                            </tr>
                        @endforeach
                    @endif
                    <tr>
                        <td colspan="7">
                            <div wire:ignore id="map"></div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="7">
                            <div class="d-block mb-md-0" style="position: relative">
                                <input type="button" onclick="window.history.back()" class="btn btn-warning" value="Back">
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    @else
        <div class="card card-body border-0 shadow table-wrapper table-responsive">
            <table class="table table-borderless">
                <tbody>
                    <tr>
                        <td colspan="2">
                            No records found...
                        </td>
                    </tr>
                    <tr>
                        <td colspan="7">
                            <div class="d-block mb-md-0" style="position: relative">
                                <input type="button" onclick="window.history.back()" class="btn btn-warning" value="Back">
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endif
</div>

@push('script')
<!-- Map Script -->
<script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCGDHu1sOYoepvEmSLmatyJVGNvCCONh48&libraries=drawing&callback=initMap&v=weekly&channel=2"> </script>
<script>
    let map;
    const coordBus = [];

    document.addEventListener("livewire:load", event => {
        if(event.detail){
            let forZoomArr = event.detail.vehiclePosition;
            let forZoom;

            if(forZoomArr.length >0){
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

                let vehicleArr = event.detail.vehiclePosition;
                for (i = 0; i < vehicleArr.length; i++) {
                    coordBus[i] = new google.maps.LatLng(
                        parseFloat(vehicleArr[i]['latitude']),
                        parseFloat(vehicleArr[i]['longitude'])
                    );
                    const vehicleMarker = new google.maps.Marker({
                        position: coordBus[i],
                        map: map,
                        icon: {
                                url: '/images/bus_marker.png',
                                scaledSize: new google.maps.Size(60, 60),
                            }
                    });

                    let contentString =
                        '<div id="content">' +
                        '<span>'+ vehicleArr[i]['bus_registration_number'] +'<br>' +
                            vehicleArr[i]['date_time'] +'<br>' +
                            vehicleArr[i]['speed'] +'</span>' +
                        '</div';

                    const infowindow = new google.maps.InfoWindow({
                        content: contentString,
                    });

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
            else{
                map = new google.maps.Map(document.getElementById("map"), {
                    zoom: 8,
                    center: {lat: 4.2105, lng: 101.9758} // Center the map on Malaysia.
                });
            }
        }else{
            map = new google.maps.Map(document.getElementById("map"), {
                zoom: 8,
                center: {lat: 4.2105, lng: 101.9758} // Center the map on Malaysia.
            });
        }
        
    });
</script>
@endpush
