<table style="border-color: #000000; border-style: solid;">
    <thead>
    <tr>
        <th colspan="{{$colspan}}" style="vertical-align: middle; text-align: center;">
            <strong>ISBSF Report</strong>
        </th>
    </tr>
    <tr>
        <td colspan="{{$colspan}}">&nbsp;</td>
    </tr>
    <tr>
        <th colspan="{{$colspan}}">
            <strong>Network Operator: </strong>
        </th>
    </tr>
    <tr>
        <th colspan="{{$colspan}}">
            <strong>Network Area: </strong>
        </th>
    </tr>
    <tr>
        <th colspan="{{$colspan}}">
            <strong> Reporting Period: {{ $dateFrom }} - {{ $dateTo }} </strong>
        </th>
    </tr>
    <tr>
        <th colspan="{{$colspan}}">
            <strong>Date Printed: {{ Carbon\Carbon::now() }}</strong>
        </th>
    </tr>
    <tr>
        <td colspan="{{$colspan}}">&nbsp;</td>
    </tr>
    </thead>

    <tbody>
    <tr>
        <td rowspan="2" style="text-align: center;"><strong>No</strong></td>
        <td rowspan="2" style="text-align: center;"><strong>Route</strong></td>
        <td colspan="{{count($allDates)}}" style="text-align: center;"><strong>Day ({{ $month }})</strong></td>
        <td rowspan="2" style="text-align: center;"><strong>Total</strong></td>
    </tr>
    <tr>
        @foreach($days as $day)
            <td style="text-align: center;"><strong>{{$day}}</strong></td>
        @endforeach
    </tr>

    @php $i=1 @endphp
    @foreach($reports as $key1 => $allRoute)
        @foreach($allRoute as $key2 => $perRoute)
            <tr>
                <td style="text-align: center;">{{ $i++ }}</td>
                <td colspan="{{$colspan - 1}}"><strong>{{$key2}}</strong></td>
            </tr>
            @foreach($perRoute as $key3 => $allDate)
                <tr>
                    <td style="text-align: center;">&nbsp;</td>
                    @if($key3=='planned_trip')
                        <td><strong>Planned Trip</strong></td>
                    @elseif($key3=='completed_trip_in')
                        <td><strong>Completed Trip (Inbound)</strong></td>
                    @elseif($key3=='completed_trip_out')
                        <td><strong>Completed Trip (Outbound)</strong></td>
                    @elseif($key3=='total_completed_trip')
                        <td><strong>Total Completed Trip</strong></td>
                    @elseif($key3=='trip_compliance')
                        <td><strong>Trip Compliance (%)</strong></td>
                    @elseif($key3=='total_off_route')
                        <td><strong>Total Off Route</strong></td>
                    @elseif($key3=='route_compliance')
                        <td><strong>Route Compliance (%)</strong></td>
                    @elseif($key3=='distance_in')
                        <td><strong>KM 1 Way Inbound</strong></td>
                    @elseif($key3=='distance_out')
                        <td><strong>KM 1 Way Outbound</strong></td>
                    @elseif($key3=='total_distance_in')
                        <td><strong>Total KM Inbound</strong></td>
                    @elseif($key3=='total_distance_out')
                        <td><strong>Total KM Outbound</strong></td>
                    @elseif($key3=='total_distance')
                        <td><strong>Total KM</strong></td>
                    @elseif($key3=='total_trip_on_time')
                        <td><strong>Total Trip On Time</strong></td>
                    @elseif($key3=='punctuality')
                        <td><strong>Punctuality Adherence (%)</strong></td>
                    @elseif($key3=='total_trip_breakdown')
                        <td><strong>Total Trip Breakdown</strong></td>
                    @elseif($key3=='realibility')
                        <td><strong>Realibility Compliance (%)</strong></td>
                    @elseif($key3=='num_of_bus')
                        <td><strong>Number of Buses</strong></td>
                    @elseif($key3=='farebox')
                        <td><strong>Farebox</strong></td>
                    @elseif($key3=='ridership')
                        <td><strong>Ridership</strong></td>
                    @endif
                    @foreach($allDate as $key4 => $data)
                        <td style="text-align: center;">{{ $data }}</td>
                    @endforeach
                </tr>
            @endforeach
        @endforeach
    @endforeach
    </tbody>
</table>
