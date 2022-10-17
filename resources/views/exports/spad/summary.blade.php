<table style="border-color: #000000; border-style: solid;">
    <thead>
    <tr>
        <th colspan="24" style="vertical-align: middle; text-align: center;">
            <strong>Summary Report</strong>
        </th>
    </tr>
    <tr>
        <td colspan="24">&nbsp;</td>
    </tr>
    <tr>
        <th colspan="24">
            <strong>Network Operator: MARALINER</strong>
        </th>
    </tr>
    <tr>
        <th colspan="24">
            <strong>Network Area: {{ $networkArea }}</strong>
        </th>
    </tr>
    <tr>
        <th colspan="24">
            <strong> Reporting Period: {{ $dateFrom }} - {{ $dateTo }} </strong>
        </th>
    </tr>
    <tr>
        <th colspan="24">
            <strong>Date Printed: {{ Carbon\Carbon::now() }}</strong>
        </th>
    </tr>
    </thead>

    <tbody>
        <tr>
            <td colspan="24">&nbsp;</td>
        </tr>
        <tr>
            <td style="text-align: center;"><strong>No.</strong></td>
            <td style="text-align: center;"><strong>Route No.</strong></td>
            <td style="text-align: center;"><strong>OD</strong></td>
            <td style="text-align: center;"><strong>Ridership</strong></td>
            <td style="text-align: center;"><strong>Previous Month Ridership Collection</strong></td>
            <td style="text-align: center;"><strong>% Increase</strong></td>
            <td style="text-align: center;"><strong>Farebox Collection</strong></td>
            <td style="text-align: center;"><strong>Previous Month Farebox Collection</strong></td>
            <td style="text-align: center;"><strong>Increment Farebox Collection (%)</strong></td>
            <td style="text-align: center;"><strong>Average Fare per Pax (RM)</strong></td>
            <td style="text-align: center;"><strong>Number of Trips Planned</strong></td>
            <td style="text-align: center;"><strong>Number of Trips Made</strong></td>
            <td style="text-align: center;"><strong>Number of Trips Missed</strong></td>
            <td style="text-align: center;"><strong>Total KM Service Planned</strong></td>
            <td style="text-align: center;"><strong>Total KM Service Served</strong></td>
            <td style="text-align: center;"><strong>Total KM Service Planned By GPS</strong></td>
            <td style="text-align: center;"><strong>Total Early Departure</strong></td>
            <td style="text-align: center;"><strong>Total Late Departure</strong></td>
            <td style="text-align: center;"><strong>Total Early End</strong></td>
            <td style="text-align: center;"><strong>Total Late End</strong></td>
            <td style="text-align: center;"><strong>Total Breakdown During Operation</strong></td>
            <td style="text-align: center;"><strong>Total Bus In Used</strong></td>
            <td style="text-align: center;"><strong>Total Accidents Caused By Operator</strong></td>
            <td style="text-align: center;"><strong>Total Complaints</strong></td>
        </tr>

        @php $count=1; $newRoute=false; @endphp
        @foreach($reports as $key => $reportValue)
            @if(!property_exists($reportValue, 'route_number'))
                <tr>
                    <td colspan="3" style="text-align: right;">
                        <strong>Grand Total:</strong>
                    </td>
                    <td><strong>{{$reportValue->ridership}}</strong></td>
                    <td><strong>{{$reportValue->ridership_prev}}</strong></td>
                    <td><strong>{{$reportValue->ridership_increase}}</strong></td>
                    <td><strong>{{$reportValue->farebox}}</strong></td>
                    <td><strong>{{$reportValue->farebox_prev}}</strong></td>
                    <td><strong>{{$reportValue->farebox_increase}}</strong></td>
                    <td><strong>{{$reportValue->average_pax}}</strong></td>
                    <td><strong>{{$reportValue->trip_planned}}</strong></td>
                    <td><strong>{{$reportValue->trip_served}}</strong></td>
                    <td><strong>{{$reportValue->missed_trip}}</strong></td>
                    <td><strong>{{$reportValue->km_planned}}</strong></td>
                    <td><strong>{{$reportValue->km_served}}</strong></td>
                    <td><strong>{{$reportValue->km_served}}</strong></td>
                    <td><strong>{{$reportValue->early_depart}}</strong></td>
                    <td><strong>{{$reportValue->late_depart}}</strong></td>
                    <td><strong>{{$reportValue->early_end}}</strong></td>
                    <td><strong>{{$reportValue->late_end}}</strong></td>
                    <td><strong>{{$reportValue->breakdown}}</strong></td>
                    <td><strong>{{$reportValue->bus_deployed}}</strong></td>
                    <td><strong>{{$reportValue->accident}}</strong></td>
                    <td><strong>{{$reportValue->complaint}}</strong></td>
                </tr>
            @elseif(property_exists($reportValue, 'route_number') && !property_exists($reportValue, 'trip_code'))
                <tr>
                    <td colspan="3" style="text-align: right;">
                        <strong>Total for Route {{$reportValue->route_number}}:</strong>
                    </td>
                    <td><strong>{{$reportValue->ridership}}</strong></td>
                    <td><strong>{{$reportValue->ridership_prev}}</strong></td>
                    <td><strong>{{$reportValue->ridership_increase}}</strong></td>
                    <td><strong>{{$reportValue->farebox}}</strong></td>
                    <td><strong>{{$reportValue->farebox_prev}}</strong></td>
                    <td><strong>{{$reportValue->farebox_increase}}</strong></td>
                    <td><strong>{{$reportValue->average_pax}}</strong></td>
                    <td><strong>{{$reportValue->trip_planned}}</strong></td>
                    <td><strong>{{$reportValue->trip_served}}</strong></td>
                    <td><strong>{{$reportValue->missed_trip}}</strong></td>
                    <td><strong>{{$reportValue->km_planned}}</strong></td>
                    <td><strong>{{$reportValue->km_served}}</strong></td>
                    <td><strong>{{$reportValue->km_served}}</strong></td>
                    <td><strong>{{$reportValue->early_depart}}</strong></td>
                    <td><strong>{{$reportValue->late_depart}}</strong></td>
                    <td><strong>{{$reportValue->early_end}}</strong></td>
                    <td><strong>{{$reportValue->late_end}}</strong></td>
                    <td><strong>{{$reportValue->breakdown}}</strong></td>
                    <td><strong>{{$reportValue->bus_deployed}}</strong></td>
                    <td><strong>{{$reportValue->accident}}</strong></td>
                    <td><strong>{{$reportValue->complaint}}</strong></td>
                </tr>
                @php $count=1; $newRoute=true; @endphp
            @else
                @if($newRoute)
                    <tr>
                        <td colspan="24">&nbsp;</td>
                    </tr>
                    <tr>
                        <td style="text-align: center;"><strong>No.</strong></td>
                        <td style="text-align: center;"><strong>Route No.</strong></td>
                        <td style="text-align: center;"><strong>OD</strong></td>
                        <td style="text-align: center;"><strong>Ridership</strong></td>
                        <td style="text-align: center;"><strong>Previous Month Ridership Collection</strong></td>
                        <td style="text-align: center;"><strong>% Increase</strong></td>
                        <td style="text-align: center;"><strong>Farebox Collection</strong></td>
                        <td style="text-align: center;"><strong>Previous Month Farebox Collection</strong></td>
                        <td style="text-align: center;"><strong>Increment Farebox Collection (%)</strong></td>
                        <td style="text-align: center;"><strong>Average Fare per Pax (RM)</strong></td>
                        <td style="text-align: center;"><strong>Number of Trips Planned</strong></td>
                        <td style="text-align: center;"><strong>Number of Trips Made</strong></td>
                        <td style="text-align: center;"><strong>Number of Trips Missed</strong></td>
                        <td style="text-align: center;"><strong>Total KM Service Planned</strong></td>
                        <td style="text-align: center;"><strong>Total KM Service Served</strong></td>
                        <td style="text-align: center;"><strong>Total KM Service Planned By GPS</strong></td>
                        <td style="text-align: center;"><strong>Total Early Departure</strong></td>
                        <td style="text-align: center;"><strong>Total Late Departure</strong></td>
                        <td style="text-align: center;"><strong>Total Early End</strong></td>
                        <td style="text-align: center;"><strong>Total Late End</strong></td>
                        <td style="text-align: center;"><strong>Total Breakdown During Operation</strong></td>
                        <td style="text-align: center;"><strong>Total Bus In Used</strong></td>
                        <td style="text-align: center;"><strong>Total Accidents Caused By Operator</strong></td>
                        <td style="text-align: center;"><strong>Total Complaints</strong></td>
                    </tr>
                    @php $newRoute=false; @endphp
                @endif
                <tr>
                    <td style="text-align: center;">{{$count++}}</td>
                    <td style="text-align: center;">{{$reportValue->route_number}}</td>
                    <td style="text-align: center;">{{$reportValue->route_name}}</td>
                    <td>{{$reportValue->ridership}}</td>
                    <td>{{$reportValue->ridership_prev}}</td>
                    <td>{{$reportValue->ridership_increase}}</td>
                    <td>{{$reportValue->farebox}}</td>
                    <td>{{$reportValue->farebox_prev}}</td>
                    <td>{{$reportValue->farebox_increase}}</td>
                    <td>{{$reportValue->average_pax}}</td>
                    <td>{{$reportValue->trip_planned}}</td>
                    <td>{{$reportValue->trip_served}}</td>
                    <td>{{$reportValue->missed_trip}}</td>
                    <td>{{$reportValue->km_planned}}</td>
                    <td>{{$reportValue->km_served}}</td>
                    <td>{{$reportValue->km_served}}</td>
                    <td>{{$reportValue->early_depart}}</td>
                    <td>{{$reportValue->late_depart}}</td>
                    <td>{{$reportValue->early_end}}</td>
                    <td>{{$reportValue->late_end}}</td>
                    <td>{{$reportValue->breakdown}}</td>
                    <td>{{$reportValue->bus_deployed}}</td>
                    <td>{{$reportValue->accident}}</td>
                    <td>{{$reportValue->complaint}}</td>
                </tr>
            @endif
        @endforeach
    </tbody>

    {{-- <tbody>
        @foreach($reports as $key1 => $routeVal)
            @php $count=0; @endphp
            @foreach($routeVal as $key2 => $nameVal)
                @php $count++ @endphp
                @php $i=1; @endphp
                @if($count<count($routeVal))
                    <tr>
                        <td colspan="24">&nbsp;</td>
                    </tr>
                    <tr>
                        <td style="text-align: center;"><strong>No.</strong></td>
                        <td style="text-align: center;"><strong>Route No.</strong></td>
                        <td style="text-align: center;"><strong>OD</strong></td>
                        <td style="text-align: center;"><strong>Ridership</strong></td>
                        <td style="text-align: center;"><strong>Previous Month Ridership Collection</strong></td>
                        <td style="text-align: center;"><strong>% Increase</strong></td>
                        <td style="text-align: center;"><strong>Farebox Collection</strong></td>
                        <td style="text-align: center;"><strong>Previous Month Farebox Collection</strong></td>
                        <td style="text-align: center;"><strong>Increment Farebox Collection (%)</strong></td>
                        <td style="text-align: center;"><strong>Average Fare per Pax (RM)</strong></td>
                        <td style="text-align: center;"><strong>Number of Trips Planned</strong></td>
                        <td style="text-align: center;"><strong>Number of Trips Made</strong></td>
                        <td style="text-align: center;"><strong>Number of Trips Missed</strong></td>
                        <td style="text-align: center;"><strong>Total KM Service Planned</strong></td>
                        <td style="text-align: center;"><strong>Total KM Service Served</strong></td>
                        <td style="text-align: center;"><strong>Total KM Service Planned By GPS</strong></td>
                        <td style="text-align: center;"><strong>Total Early Departure</strong></td>
                        <td style="text-align: center;"><strong>Total Late Departure</strong></td>
                        <td style="text-align: center;"><strong>Total Early End</strong></td>
                        <td style="text-align: center;"><strong>Total Late End</strong></td>
                        <td style="text-align: center;"><strong>Total Breakdown During Operation</strong></td>
                        <td style="text-align: center;"><strong>Total Bus In Used</strong></td>
                        <td style="text-align: center;"><strong>Total Accidents Caused By Operator</strong></td>
                        <td style="text-align: center;"><strong>Total Complaints</strong></td>
                    </tr>
                    @foreach($nameVal as $key3 => $content)
                        @if($key3=='inbound_data')
                            @foreach($content as $key4 => $data)
                            <tr>
                                <td style="text-align: center;">{{ $i++ }}</td>
                                <td style="text-align: center;">{{ $key2 }}</td>
                                <td style="text-align: center;">{{ $key4 }}</td>
                                <td style="text-align: right;">{{ $data['ridership_in'] }}</td>
                                <td style="text-align: right;">{{ $data['prev_ridership_in'] }}</td>
                                <td style="text-align: right;">{{ $data['increase_ridership_in'] }}</td>
                                <td style="text-align: right;">{{ $data['farebox_in'] }}</td>
                                <td style="text-align: right;">{{ $data['prev_farebox_in'] }}</td>
                                <td style="text-align: right;">{{ $data['increase_farebox_in'] }}</td>
                                <td style="text-align: right;">{{ $data['average_fare_in'] }}</td>
                                <td style="text-align: right;">{{ $data['trip_planned_in'] }}</td>
                                <td style="text-align: right;">{{ $data['trip_made_in'] }}</td>
                                <td style="text-align: right;">{{ $data['trip_missed_in'] }}</td>
                                <td style="text-align: right;">{{ $data['km_planned_in'] }}</td>
                                <td style="text-align: right;">{{ $data['km_served_in'] }}</td>
                                <td style="text-align: right;">{{ $data['km_served_gps_in'] }}</td>
                                <td style="text-align: right;">{{ $data['early_departure_in'] }}</td>
                                <td style="text-align: right;">{{ $data['late_departure_in'] }}</td>
                                <td style="text-align: right;">{{ $data['early_end_in'] }}</td>
                                <td style="text-align: right;">{{ $data['late_end_in'] }}</td>
                                <td style="text-align: right;">{{ $data['breakdown_in'] }}</td>
                                <td style="text-align: right;">{{ $data['bus_used_in'] }}</td>
                                <td style="text-align: right;">{{ $data['accidents_in'] }}</td>
                                <td style="text-align: right;">{{ $data['complaints_in'] }}</td>
                            </tr>
                            @endforeach
                        @elseif($key3=='outbound_data')
                            @foreach($content as $key5 => $data)
                            <tr>
                                <td style="text-align: center;">{{ $i++ }}</td>
                                <td style="text-align: center;">{{ $key2 }}</td>
                                <td style="text-align: center;">{{ $key5 }}</td>
                                <td style="text-align: right;">{{ $data['ridership_out'] }}</td>
                                <td style="text-align: right;">{{ $data['prev_ridership_out'] }}</td>
                                <td style="text-align: right;">{{ $data['increase_ridership_out'] }}</td>
                                <td style="text-align: right;">{{ $data['farebox_out'] }}</td>
                                <td style="text-align: right;">{{ $data['prev_farebox_out'] }}</td>
                                <td style="text-align: right;">{{ $data['increase_farebox_out'] }}</td>
                                <td style="text-align: right;">{{ $data['average_fare_out'] }}</td>
                                <td style="text-align: right;">{{ $data['trip_planned_out'] }}</td>
                                <td style="text-align: right;">{{ $data['trip_made_out'] }}</td>
                                <td style="text-align: right;">{{ $data['trip_missed_out'] }}</td>
                                <td style="text-align: right;">{{ $data['km_planned_out'] }}</td>
                                <td style="text-align: right;">{{ $data['km_served_out'] }}</td>
                                <td style="text-align: right;">{{ $data['km_served_gps_out'] }}</td>
                                <td style="text-align: right;">{{ $data['early_departure_out'] }}</td>
                                <td style="text-align: right;">{{ $data['late_departure_out'] }}</td>
                                <td style="text-align: right;">{{ $data['early_end_out'] }}</td>
                                <td style="text-align: right;">{{ $data['late_end_out'] }}</td>
                                <td style="text-align: right;">{{ $data['breakdown_out'] }}</td>
                                <td style="text-align: right;">{{ $data['bus_used_out'] }}</td>
                                <td style="text-align: right;">{{ $data['accidents_out'] }}</td>
                                <td style="text-align: right;">{{ $data['complaints_out'] }}</td>
                            </tr>
                            @endforeach
                        @elseif($key3 == 'total')
                            <tr>
                                <td colspan="3" style="text-align: right;">
                                    <strong>Total for Route {{$key2}}:</strong>
                                </td>
                                @foreach($content as $key6 => $data)
                                    <td><strong>{{$data}}</strong></td>
                                @endforeach
                            </tr>
                        @endif
                    @endforeach
                @endif
            @endforeach
            <tr>
                <td colspan="3" style="text-align: right;">
                    <strong>Grand Total:</strong>
                </td>
                @foreach($routeVal['grand'] as $key7 => $data)
                    <td><strong>{{$data}}</strong></td>
                @endforeach
            </tr>
        @endforeach
    </tbody> --}}
</table>
