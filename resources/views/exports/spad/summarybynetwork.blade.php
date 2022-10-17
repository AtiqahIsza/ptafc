<table style="border-color: #000000; border-style: solid;">
    <thead>
    <tr>
        <th colspan="15" style="vertical-align: middle; text-align: center;">
            <strong>Summary By Network Report</strong>
        </th>
    </tr>
    <tr>
        <th colspan="15">&nbsp;</th>
    </tr>
    <tr>
        <th colspan="15">
            <strong>Network Operator: MARALINER</strong>
        </th>
    </tr>
    <tr>
        <th colspan="15">
            <strong>Network Area: {{ $networkArea }}</strong>
        </th>
    </tr>
    <tr>
        <th colspan="15">
            <strong> Reporting Period: {{ $dateFrom }} - {{ $dateTo }} </strong>
        </th>
    </tr>
    <tr>
        <th colspan="15">
            <strong>Date Printed: {{ Carbon\Carbon::now() }}</strong>
        </th>
    </tr>
    </thead>

    <tbody>
        <tr>
            <td colspan="15">&nbsp;</td>
        </tr>
        <tr>
            <td style="text-align: center;"><strong>No.</strong></td>
            <td style="text-align: center;"><strong>Route No.</strong></td>
            <td style="text-align: center;"><strong>Total KM Planned (KM)</strong></td>
            <td style="text-align: center;"><strong>Total KM Served (KM)</strong></td>
            <td style="text-align: center;"><strong>Total Claim (RM)</strong></td>
            <td style="text-align: center;"><strong>Total Trips Planned</strong></td>
            <td style="text-align: center;"><strong>Total Trips Served</strong></td>
            <td style="text-align: center;"><strong>No. Of Buses Deployed</strong></td>
            <td style="text-align: center;"><strong>Total Missed Trips</strong></td>
            <td style="text-align: center;"><strong>Total Trips Late/Early Departure</strong></td>
            <td style="text-align: center;"><strong>Total Breakdown</strong></td>
            <td style="text-align: center;"><strong>Total Accidents</strong></td>
            <td style="text-align: center;"><strong>Ridership</strong></td>
            <td style="text-align: center;"><strong>Cash Collection (RM)</strong></td>
            <td style="text-align: center;"><strong>Touch N Go Collection (RM)</strong></td>
        </tr>
        @php $count=1;@endphp
        @foreach($reports as $key1 => $reportValue)
            @if(!property_exists($reportValue, 'route_id') && !property_exists($reportValue, 'route_number'))
            <tr>
                <td colspan="2" style="text-align: right;">
                    <strong>Total For Network: {{ $networkArea }}</strong>
                </td>
                <td><strong>{{ $reportValue->km_planned }}</strong></td>
                <td><strong>{{ $reportValue->km_served }}</strong></td>
                <td><strong>{{ $reportValue->travel_claim }}</strong></td>
                <td><strong>{{ $reportValue->trip_planned }}</strong></td>
                <td><strong>{{ $reportValue->trip_served }}</strong></td>
                <td><strong>{{ $reportValue->bus_deployed }}</strong></td>
                <td><strong>{{ $reportValue->missed_trip }}</strong></td>
                <td><strong>{{ $reportValue->earlyLate }}</strong></td>
                <td><strong>0</strong></td>
                <td><strong>0</strong></td>
                <td><strong>{{ $reportValue->ridership }}</strong></td>
                <td><strong>{{ $reportValue->cash_collection }}</strong></td>
                <td><strong>{{ $reportValue->tng_collection }}</strong></td>
            </tr>
            @else
                <tr>
                    <td style="text-align: center;">{{ $count++ }}</td>
                    <td style="text-align: center;">{{ $reportValue->route_number }}</td>
                    <td>{{ $reportValue->km_planned }}</td>
                    <td>{{ $reportValue->km_served }}</td>
                    <td>{{ $reportValue->travel_claim }}</td>
                    <td>{{ $reportValue->trip_planned }}</td>
                    <td>{{ $reportValue->trip_served }}</td>
                    <td>{{ $reportValue->bus_deployed }}</td>
                    <td>{{ $reportValue->missed_trip }}</td>
                    <td>{{ $reportValue->earlyLate }}</td>
                    <td>0</td>
                    <td>0</td>
                    <td>{{ $reportValue->ridership }}</td>
                    <td>{{ $reportValue->cash_collection }}</td>
                    <td>{{ $reportValue->tng_collection }}</td>
                </tr>
            @endif
        @endforeach
    </tbody>

    {{-- <tbody>
        <tr>
            <td colspan="15">&nbsp;</td>
        </tr>
        <tr>
            <td style="text-align: center;"><strong>No.</strong></td>
            <td style="text-align: center;"><strong>Route No.</strong></td>
            <td style="text-align: center;"><strong>Total KM Planned (KM)</strong></td>
            <td style="text-align: center;"><strong>Total KM Served (KM)</strong></td>
            <td style="text-align: center;"><strong>Total Claim (RM)</strong></td>
            <td style="text-align: center;"><strong>Total Trips Planned</strong></td>
            <td style="text-align: center;"><strong>Total Trips Served</strong></td>
            <td style="text-align: center;"><strong>No. Of Buses Deployed</strong></td>
            <td style="text-align: center;"><strong>Total Missed Trips</strong></td>
            <td style="text-align: center;"><strong>Total Trips Late/Early Departure</strong></td>
            <td style="text-align: center;"><strong>Total Breakdown</strong></td>
            <td style="text-align: center;"><strong>Total Accidents</strong></td>
            <td style="text-align: center;"><strong>Ridership</strong></td>
            <td style="text-align: center;"><strong>Cash Collection (RM)</strong></td>
            <td style="text-align: center;"><strong>Touch N Go Collection (RM)</strong></td>
        </tr>
        @foreach($reports as $key1 => $reportValue)
            @php $count=1; @endphp
            @foreach($reportValue as $key2 => $allRoutes)
                @if($key2=="grand")
                    <tr>
                        <td colspan="2" style="text-align: right;">
                            <strong>Total For Network: MARALINER</strong>
                        </td>
                        @foreach($allRoutes as $key3 => $total)
                            <td><strong>{{ $total }}</strong></td>
                        @endforeach
                    </tr>
                @else
                    <tr>
                        <td style="text-align: center;">{{ $count++ }}</td>
                        <td style="text-align: center;">{{ $key2 }}</td>
                        @foreach($allRoutes as $key4 => $dataPerRoute)
                            <td style="text-align: right;">{{ $dataPerRoute }}</td>
                        @endforeach
                    </tr>
                @endif
            @endforeach
        @endforeach
    </tbody> --}}
</table>
