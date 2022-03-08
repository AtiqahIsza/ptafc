<table style="border-color: #000000; border-style: solid;">
    <thead>
    <tr>
        <th colspan="16" style="vertical-align: middle; text-align: center;">
            <strong>Monthly Summary Report</strong>
        </th>
    </tr>
    <tr>
        <td colspan="16">&nbsp;</td>
    </tr>
    <tr>
        <th colspan="16">
            <strong>Date: {{$dateFrom}} - {{$dateTo}}</strong>
        </th>
    </tr>
    <tr>
        <th colspan="11">
            <strong> Company: </strong>
        </th>
    </tr>
    </thead>

    <tbody>
        <tr>
            <td rowspan="2" style="text-align: center;"><strong>No</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Route Name</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Route Number</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Total Trip per Day (One way only)</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Actual Distance per Month</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Dead Distance per Month</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Distance per Month</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Total Operated Bus</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Total Operated Day per Month</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Total Passenger per Month</strong></td>
            <td colspan="2" style="text-align: center;"><strong>Total Passenger per Peak Hour</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Frequency of Bus Entering Terminal per Month</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Total Driver</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Income Based on Transport Letter</strong></td>
        </tr>
        <tr>
            <td><strong>6AM - 9AM</strong></td>
            <td><strong>4PM - 8PM</strong></td>
        </tr>

        @php $i=0 @endphp
        @foreach($routes as $route)
            <tr>
                <td style="text-align: center;">{{ $i++ }}</td>
                <td style="text-align: center;">{{ $route->route_name }}</td>
                <td style="text-align: center;">{{ $route->route_number }}</td>
                <td style="text-align: center;">{{ $route->inbound_distance }}</td>
                <td style="text-align: center;">{{ $route->inbound_distance }}</td>
                <td style="text-align: center;">{{ $route->inbound_distance }}</td>
                <td style="text-align: center;">{{ $route->inbound_distance }}</td>
                <td style="text-align: center;">{{ $route->route_target }}</td>
                <td style="text-align: center;">{{ $route->route_target  }}</td>
                <td style="text-align: center;">{{ $route->route_target }}</td>
                <td style="text-align: center;">{{ $route->route_name }}</td>
                <td style="text-align: center;">{{ $route->route_name }}</td>
                <td style="text-align: center;">{{ $route->route_name }}</td>
                <td style="text-align: center;">{{ $route->route_name }}</td>
                <td style="text-align: center;">{{ $route->route_name }}</td>
            </tr>
        @endforeach
        <tr>
            <td colspan="3" style="text-align: right;">
                <strong>Total</strong>
            </td>
            <td style="text-align: center;"><strong>Total Trip </strong></td>
            <td style="text-align: center;"><strong>Total Distance</strong></td>
            <td style="text-align: center;"><strong>Total Distance</strong></td>
            <td style="text-align: center;"><strong>Total Distance</strong></td>
            <td style="text-align: center;"><strong>Total Operated Bus</strong></td>
            <td style="text-align: center;"><strong>Total Operated Day</strong></td>
            <td style="text-align: center;"><strong>Total Passenger</strong></td>
            <td style="text-align: center;"><strong>Peak Hour</strong></td>
            <td style="text-align: center;"><strong>Peak Hour</strong></td>
            <td style="text-align: center;"><strong>Frequency</strong></td>
            <td style="text-align: center;"><strong>Total Driver</strong></td>
            <td style="text-align: center;"><strong>Income</strong></td>
        </tr>
    </tbody>
</table>
