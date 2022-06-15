<div class="main py-4">
    <div class="d-block mb-md-0" style="position: relative">
        <h2>GPS Last Received</h2>
    </div>
    <div class="d-block mb-md-0" style="position: relative">
        <select wire:model="selectedCompany" class="form-select fmxw-200 d-none d-md-inline"  >
            <option value="">Choose Company</option>
            <option value="ALL">All Companies</option>
            @foreach($companies as $company)
                <option value="{{$company->id}}">{{$company->company_name}}</option>
            @endforeach
        </select>

        <select wire:model="selectedBus" class="form-select fmxw-200 d-none d-md-inline"  >
            <option value="">Choose Bus</option>
            <option value="ALL">All Buses</option>
            @foreach($buses as $bus)
                <option value="{{$bus->id}}">{{$bus->bus_registration_number}}</option>
            @endforeach
        </select>
    </div>
    <br>
    <div class="card card-body border-0 shadow table-wrapper table-responsive">
        <table class="table table-borderless">
            <tbody>
                <tr>
                    <td colspan="2">
                        <div wire:poll.30s="forPolling" id="map"></div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

@push('script')
<!-- Map Script -->
<script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCGDHu1sOYoepvEmSLmatyJVGNvCCONh48&libraries=drawing&callback=initMap&v=weekly&channel=2"> </script>
<script>
    let map;
    const coordBus = [];
    const coordRedBus = [];
    const coordGreenBus = [];
    const coordYellowBus = [];

    document.addEventListener("livewire:load", event => { 
        if(event.detail){
            let greenGPSArr = event.detail.greenGPS;
            let yellowGPSArr = event.detail.yellowGPS;
            let redGPSArr = event.detail.redGPS;
            let forZoom = []; 
            let uniqueIcons = []; 
            let content = []; 
            var marker;

            // G 
            if(greenGPSArr.length>0){
                //alert('IM HERE in greenGPSArr');
                for (i = 0; i < greenGPSArr.length; i++) {
                    forZoom[i] = new google.maps.LatLng(
                        parseFloat(greenGPSArr[i]['latitude']),
                        parseFloat(greenGPSArr[i]['longitude'])
                    );
                    uniqueIcons[i] = {
                        url: '/images/bus_marker_green.png',
                        scaledSize: new google.maps.Size(60, 60),
                    }
                    content[i] =
                        '<div id="content">' +
                        '<span>'+ greenGPSArr[i]['bus_registration_number'] +'<br>' +
                            greenGPSArr[i]['date_time'] +'<br>' +
                            greenGPSArr[i]['speed'] +'</span>' +
                        '</div';
                }

                // G  & Y
                if(yellowGPSArr.length>0){
                    //alert('IM HERE in yellowGPSArr');
                    index = 0;
                    for (i = 0; i < yellowGPSArr.length; i++) {
                        forZoom[greenGPSArr.length + index] = new google.maps.LatLng(
                            parseFloat(yellowGPSArr[i]['latitude']),
                            parseFloat(yellowGPSArr[i]['longitude'])
                        );
                        uniqueIcons[greenGPSArr.length + index] = {
                            url: '/images/bus_marker_yellow.png',
                            scaledSize: new google.maps.Size(60, 60),
                        }
                        content[greenGPSArr.length + index] =
                            '<div id="content">' +
                            '<span>'+ yellowGPSArr[i]['bus_registration_number'] +'<br>' +
                                yellowGPSArr[i]['date_time'] +'<br>' +
                                yellowGPSArr[i]['speed'] +'</span>' +
                            '</div';
                        index++;
                    }

                    // G  & Y & R
                    let totalLength = greenGPSArr.length + yellowGPSArr.length; 
                    if(redGPSArr.length>0){
                       //alert('IM HERE in redGPSArr');
                        index = 0;
                        for (i = 0; i < redGPSArr.length; i++) {
                            forZoom[totalLength + index] = new google.maps.LatLng(
                                parseFloat(redGPSArr[i]['latitude']),
                                parseFloat(redGPSArr[i]['longitude'])
                            );
                            uniqueIcons[totalLength + index] = {
                                url: '/images/bus_marker_red.png',
                                scaledSize: new google.maps.Size(60, 60),
                            }
                            content[totalLength + index] =
                                '<div id="content">' +
                                '<span>'+ redGPSArr[i]['bus_registration_number'] +'<br>' +
                                    redGPSArr[i]['date_time'] +'<br>' +
                                    redGPSArr[i]['speed'] +'</span>' +
                                '</div';
                            index++;
                        }
                    }
                    // G  & Y & !R
                }
                // G  & !Y & R
                else{
                    if(redGPSArr.length>0){
                        //alert('IM HERE in redGPSArr');
                        index = 0;
                        for (i = 0; i < redGPSArr.length; i++) {
                            forZoom[greenGPSArr.length + index] = new google.maps.LatLng(
                                parseFloat(redGPSArr[i]['latitude']),
                                parseFloat(redGPSArr[i]['longitude'])
                            );
                            uniqueIcons[greenGPSArr.length + index] = {
                                url: '/images/bus_marker_red.png',
                                scaledSize: new google.maps.Size(60, 60),
                            }
                            content[greenGPSArr.length + index] =
                                '<div id="content">' +
                                '<span>'+ redGPSArr[i]['bus_registration_number'] +'<br>' +
                                    redGPSArr[i]['date_time'] +'<br>' +
                                    redGPSArr[i]['speed'] +'</span>' +
                                '</div';
                            index++;
                        }
                    }
                    // G  & !Y & !R
                }
            }
            // !G 
            else{
                // !G & Y
                if(yellowGPSArr.length>0){
                    //alert('IM HERE in yellowGPSArr');
                    for (i = 0; i < yellowGPSArr.length; i++) {
                        forZoom[i] = new google.maps.LatLng(
                            parseFloat(yellowGPSArr[i]['latitude']),
                            parseFloat(yellowGPSArr[i]['longitude'])
                        );
                        uniqueIcons[i] = {
                            url: '/images/bus_marker_yellow.png',
                            scaledSize: new google.maps.Size(60, 60),
                        }
                        content[i] =
                            '<div id="content">' +
                            '<span>'+ yellowGPSArr[i]['bus_registration_number'] +'<br>' +
                                yellowGPSArr[i]['date_time'] +'<br>' +
                                yellowGPSArr[i]['speed'] +'</span>' +
                            '</div';
                    }

                    // !G & Y & R
                    if(redGPSArr.length>0){
                        //alert('IM HERE in redGPSArr');
                        index = 0;
                        for (i = 0; i < redGPSArr.length; i++) {
                            forZoom[yellowGPSArr.length + index] = new google.maps.LatLng(
                                parseFloat(redGPSArr[i]['latitude']),
                                parseFloat(redGPSArr[i]['longitude'])
                            );
                            uniqueIcons[yellowGPSArr.length + index] = {
                                url: '/images/bus_marker_red.png',
                                scaledSize: new google.maps.Size(60, 60),
                            }
                            content[yellowGPSArr.length + index] =
                                '<div id="content">' +
                                '<span>'+ redGPSArr[i]['bus_registration_number'] +'<br>' +
                                    redGPSArr[i]['date_time'] +'<br>' +
                                    redGPSArr[i]['speed'] +'</span>' +
                                '</div';
                            index++;
                        }
                    }
                }
                //!G & !Y & R
                else{
                    if(redGPSArr.length>0){
                        //alert('IM HERE in redGPSArr');
                        for (i = 0; i < redGPSArr.length; i++) {
                            forZoom[i] = new google.maps.LatLng(
                                parseFloat(redGPSArr[i]['latitude']),
                                parseFloat(redGPSArr[i]['longitude'])
                            );
                            uniqueIcons[i] = {
                                url: '/images/bus_marker_red.png',
                                scaledSize: new google.maps.Size(60, 60),
                            }
                            content[i] =
                                '<div id="content">' +
                                '<span>'+ redGPSArr[i]['bus_registration_number'] +'<br>' +
                                    redGPSArr[i]['date_time'] +'<br>' +
                                    redGPSArr[i]['speed'] +'</span>' +
                                '</div';
                        }
                    }
                }
            }
            
            if(forZoom.length>0){
                //alert('IM HERE in forZoom');
                map = new google.maps.Map(document.getElementById("map"), {
                        zoom: 13,
                        center: forZoom[0], // Center the map on Malaysia.
                    });
                for (f = 0; f < forZoom.length; f++) {
                    const vehicleMarker = new google.maps.Marker({
                            position: forZoom[f],
                            map: map,
                            icon: uniqueIcons[f]
                        });

                    const infowindow = new google.maps.InfoWindow({
                            content: content[f],
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
            }
            else{
                //alert('IM HERE in else event.detail.gpses');
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

    document.addEventListener("livewire:poll", event => { 
        if(event.detail){
            var currZoom = map.getZoom();
            let greenGPSArr = event.detail.greenGPS;
            let yellowGPSArr = event.detail.yellowGPS;
            let redGPSArr = event.detail.redGPS;
            let forZoom = []; 
            let uniqueIcons = []; 
            let content = []; 
            var marker;

            // G 
            if(greenGPSArr.length>0){
                //alert('IM HERE in greenGPSArr');
                for (i = 0; i < greenGPSArr.length; i++) {
                    forZoom[i] = new google.maps.LatLng(
                        parseFloat(greenGPSArr[i]['latitude']),
                        parseFloat(greenGPSArr[i]['longitude'])
                    );
                    uniqueIcons[i] = {
                        url: '/images/bus_marker_green.png',
                        scaledSize: new google.maps.Size(60, 60),
                    }
                    content[i] =
                        '<div id="content">' +
                        '<span>'+ greenGPSArr[i]['bus_registration_number'] +'<br>' +
                            greenGPSArr[i]['date_time'] +'<br>' +
                            greenGPSArr[i]['speed'] +'</span>' +
                        '</div';
                }

                // G  & Y
                if(yellowGPSArr.length>0){
                    //alert('IM HERE in yellowGPSArr');
                    index = 0;
                    for (i = 0; i < yellowGPSArr.length; i++) {
                        forZoom[greenGPSArr.length + index] = new google.maps.LatLng(
                            parseFloat(yellowGPSArr[i]['latitude']),
                            parseFloat(yellowGPSArr[i]['longitude'])
                        );
                        uniqueIcons[greenGPSArr.length + index] = {
                            url: '/images/bus_marker_yellow.png',
                            scaledSize: new google.maps.Size(60, 60),
                        }
                        content[greenGPSArr.length + index] =
                            '<div id="content">' +
                            '<span>'+ yellowGPSArr[i]['bus_registration_number'] +'<br>' +
                                yellowGPSArr[i]['date_time'] +'<br>' +
                                yellowGPSArr[i]['speed'] +'</span>' +
                            '</div';
                        index++;
                    }

                    // G  & Y & R
                    let totalLength = greenGPSArr.length + yellowGPSArr.length; 
                    if(redGPSArr.length>0){
                       //alert('IM HERE in redGPSArr');
                       index = 0;
                        for (i = 0; i < redGPSArr.length; i++) {
                            forZoom[totalLength + index] = new google.maps.LatLng(
                                parseFloat(redGPSArr[i]['latitude']),
                                parseFloat(redGPSArr[i]['longitude'])
                            );
                            uniqueIcons[totalLength + index] = {
                                url: '/images/bus_marker_red.png',
                                scaledSize: new google.maps.Size(60, 60),
                            }
                            content[totalLength + index] =
                                '<div id="content">' +
                                '<span>'+ redGPSArr[i]['bus_registration_number'] +'<br>' +
                                    redGPSArr[i]['date_time'] +'<br>' +
                                    redGPSArr[i]['speed'] +'</span>' +
                                '</div';
                            index++;
                        }
                    }
                    // G  & Y & !R
                }
                // G  & !Y & R
                else{
                    if(redGPSArr.length>0){
                        //alert('IM HERE in redGPSArr');
                        index = 0;
                        for (i = 0; i < redGPSArr.length; i++) {
                            forZoom[greenGPSArr.length + index] = new google.maps.LatLng(
                                parseFloat(redGPSArr[i]['latitude']),
                                parseFloat(redGPSArr[i]['longitude'])
                            );
                            uniqueIcons[greenGPSArr.length + index] = {
                                url: '/images/bus_marker_red.png',
                                scaledSize: new google.maps.Size(60, 60),
                            }
                            content[greenGPSArr.length + index] =
                                '<div id="content">' +
                                '<span>'+ redGPSArr[i]['bus_registration_number'] +'<br>' +
                                    redGPSArr[i]['date_time'] +'<br>' +
                                    redGPSArr[i]['speed'] +'</span>' +
                                '</div';
                            index++;
                        }
                    }
                    // G  & !Y & !R
                }
            }
            // !G 
            else{
                // !G & Y
                if(yellowGPSArr.length>0){
                    //alert('IM HERE in yellowGPSArr');
                    for (i = 0; i < yellowGPSArr.length; i++) {
                        forZoom[i] = new google.maps.LatLng(
                            parseFloat(yellowGPSArr[i]['latitude']),
                            parseFloat(yellowGPSArr[i]['longitude'])
                        );
                        uniqueIcons[i] = {
                            url: '/images/bus_marker_yellow.png',
                            scaledSize: new google.maps.Size(60, 60),
                        }
                        content[i] =
                            '<div id="content">' +
                            '<span>'+ yellowGPSArr[i]['bus_registration_number'] +'<br>' +
                                yellowGPSArr[i]['date_time'] +'<br>' +
                                yellowGPSArr[i]['speed'] +'</span>' +
                            '</div';
                    }

                    // !G & Y & R
                    if(redGPSArr.length>0){
                        //alert('IM HERE in redGPSArr');
                        index = 0;
                        for (i = 0; i < redGPSArr.length; i++) {
                            forZoom[yellowGPSArr.length + index] = new google.maps.LatLng(
                                parseFloat(redGPSArr[i]['latitude']),
                                parseFloat(redGPSArr[i]['longitude'])
                            );
                            uniqueIcons[yellowGPSArr.length + index] = {
                                url: '/images/bus_marker_red.png',
                                scaledSize: new google.maps.Size(60, 60),
                            }
                            content[yellowGPSArr.length + index] =
                                '<div id="content">' +
                                '<span>'+ redGPSArr[i]['bus_registration_number'] +'<br>' +
                                    redGPSArr[i]['date_time'] +'<br>' +
                                    redGPSArr[i]['speed'] +'</span>' +
                                '</div';
                            index++;
                        }
                    }
                }
                //!G & !Y & R
                else{
                    if(redGPSArr.length>0){
                        //alert('IM HERE in redGPSArr');
                        for (i = 0; i < redGPSArr.length; i++) {
                            forZoom[i] = new google.maps.LatLng(
                                parseFloat(redGPSArr[i]['latitude']),
                                parseFloat(redGPSArr[i]['longitude'])
                            );
                            uniqueIcons[i] = {
                                url: '/images/bus_marker_red.png',
                                scaledSize: new google.maps.Size(60, 60),
                            }
                            content[i] =
                                '<div id="content">' +
                                '<span>'+ redGPSArr[i]['bus_registration_number'] +'<br>' +
                                    redGPSArr[i]['date_time'] +'<br>' +
                                    redGPSArr[i]['speed'] +'</span>' +
                                '</div';
                        }
                    }
                }
            }
            
            if(forZoom.length>0){
                //alert('IM HERE in forZoom');
                map = new google.maps.Map(document.getElementById("map"), {
                        zoom: currZoom,
                        center: forZoom[0], // Center the map on Malaysia.
                    });
                for (f = 0; f < forZoom.length; f++) {
                    const vehicleMarker = new google.maps.Marker({
                            position: forZoom[f],
                            map: map,
                            icon: uniqueIcons[f]
                        });

                    const infowindow = new google.maps.InfoWindow({
                            content: content[f],
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
            }
            else{
                //alert('IM HERE in else event.detail.gpses');
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
