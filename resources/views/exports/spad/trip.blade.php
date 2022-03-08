<table style="border-color: #000000; border-style: solid;">
    <thead>
    <tr>
        <th colspan="22" style="vertical-align: middle; text-align: center;">
            <strong>{{$routeNo}} Trip Report</strong>
        </th>
    </tr>
    <tr>
        <td colspan="22">&nbsp;</td>
    </tr>
    <tr>
        <th colspan="22">
            <strong>Network Operator: </strong>
        </th>
    </tr>
    <tr>
        <th colspan="22">
            <strong>Network Area: </strong>
        </th>
    </tr>
    <tr>
        <th colspan="22">
            <strong> Reporting Period: {{ $dateFrom }} - {{ $dateTo }} </strong>
        </th>
    </tr>
    <tr>
        <th colspan="22">
            <strong>Date Printed: {{ Carbon\Carbon::now() }}</strong>
        </th>
    </tr>
    <tr>
        <td colspan="22">&nbsp;</td>
    </tr>
    </thead>

    <tbody>
    @foreach($allDates as $allDate)
        <tr>
            <td rowspan="2" style="text-align: center;"><strong>Route No.</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>OD</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>No. of Trips</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Trip No</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Bus Plate Number</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Driver ID</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Service Date</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Start Point</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Service Start Time</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Actual Start Time</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Sales Start Time</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Service End Time</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Actual End Time</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Sales End Time</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Passengers Boarding Count</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Total Sales Amount (RM)</strong></td>
            <td colspan="10" style="text-align: center;"><strong>ETM Boarding Passenger Count</strong></td>
        </tr>
        <tr>
            <td><strong>Total On</strong></td>
            <td><strong>Transfer Count</strong></td>
            <td><strong>Monthly Pass</strong></td>
            <td><strong>Adult</strong></td>
            <td><strong>Child</strong></td>
            <td><strong>Senior</strong></td>
            <td><strong>Student</strong></td>
            <td><strong>OKU</strong></td>
            <td><strong>JKM</strong></td>
            <td><strong>MAIM</strong></td>
        </tr>

        @foreach($routes as $route)
            <tr>
                <td style="text-align: center;">{{ $route->route_number }}</td>
                <td style="text-align: center;">{{ $route->route_name  }}</td>
                <td style="text-align: center;">{{ $route->inbound_distance }}</td>
                <td style="text-align: center;">{{ $route->outbound_distance  }}</td>
                <td style="text-align: center;">{{ $route->inbound_distance }}</td>
                <td style="text-align: center;">{{ $route->outbound_distance  }}</td>
                <td style="text-align: center;">{{ $route->inbound_distance }}</td>
                <td style="text-align: center;">{{ $route->outbound_distance  }}</td>
                <td style="text-align: center;">{{ $route->route_number }}</td>
                <td style="text-align: center;">{{ $route->route_number }}</td>
                <td style="text-align: center;">{{ $route->route_number }}</td>
                <td style="text-align: center;">{{ $route->route_number }}</td>
                <td style="text-align: center;">{{ $route->route_number }}</td>
                <td style="text-align: center;">{{ $route->route_number }}</td>
                <td style="text-align: center;">{{ $route->route_number }}</td>
                <td style="text-align: center;">{{ $route->route_number }}</td>
                <td style="text-align: center;">{{ $route->route_number }}</td>
                <td style="text-align: center;">{{ $route->route_number }}</td>
                <td style="text-align: center;">{{ $route->route_target }}</td>
                <td style="text-align: center;">{{ $route->route_target }}</td>
                <td style="text-align: center;">{{ $route->route_target }}</td>
                <td style="text-align: center;">{{ $route->route_target }}</td>
                <td style="text-align: center;">{{ $route->route_target }}</td>
                <td style="text-align: center;">{{ $route->route_target }}</td>
                <td style="text-align: center;">{{ $route->route_target }}</td>
                <td style="text-align: center;">{{ $route->route_target }}</td>
            </tr>
        @endforeach
        <tr>
            <td colspan="14" style="text-align: right;">
                <strong>Total For Route OD: {{$routeNo}}</strong>
            </td>
            <td><strong>Passengers Boarding Count</strong></td>
            <td><strong>Total Sales Amount (RM)</strong></td>
            <td><strong>Total On</strong></td>
            <td><strong>Transfer Count</strong></td>
            <td><strong>Monthly Pass</strong></td>
            <td><strong>Adult</strong></td>
            <td><strong>Child</strong></td>
            <td><strong>Senior</strong></td>
            <td><strong>Student</strong></td>
            <td><strong>OKU</strong></td>
            <td><strong>JKM</strong></td>
            <td><strong>MAIM</strong></td>
        </tr>
        <tr>
            <td colspan="14" style="text-align: right;">
                <strong>Total for Service Date: {{$allDate}}</strong>
            </td>
            <td><strong>Passengers Boarding Count</strong></td>
            <td><strong>Total Sales Amount (RM)</strong></td>
            <td><strong>Total On</strong></td>
            <td><strong>Transfer Count</strong></td>
            <td><strong>Monthly Pass</strong></td>
            <td><strong>Adult</strong></td>
            <td><strong>Child</strong></td>
            <td><strong>Senior</strong></td>
            <td><strong>Student</strong></td>
            <td><strong>OKU</strong></td>
            <td><strong>JKM</strong></td>
            <td><strong>MAIM</strong></td>
        </tr>
        <tr>
            <td colspan="14">&nbsp;</td>
        </tr>
    @endforeach
    <tr>
        <td colspan="14" style="text-align: right;">
            <strong>Total for Route {{$routeNo}}:</strong>
        </td>
        <td><strong>Passengers Boarding Count</strong></td>
        <td><strong>Total Sales Amount (RM)</strong></td>
        <td><strong>Total On</strong></td>
        <td><strong>Transfer Count</strong></td>
        <td><strong>Monthly Pass</strong></td>
        <td><strong>Adult</strong></td>
        <td><strong>Child</strong></td>
        <td><strong>Senior</strong></td>
        <td><strong>Student</strong></td>
        <td><strong>OKU</strong></td>
        <td><strong>JKM</strong></td>
        <td><strong>MAIM</strong></td>
    </tr>
    <tr>
        <td colspan="14" style="text-align: right;">
            <strong>Grand Total:</strong>
        </td>
        <td><strong>Passengers Boarding Count</strong></td>
        <td><strong>Total Sales Amount (RM)</strong></td>
        <td><strong>Total On</strong></td>
        <td><strong>Transfer Count</strong></td>
        <td><strong>Monthly Pass</strong></td>
        <td><strong>Adult</strong></td>
        <td><strong>Child</strong></td>
        <td><strong>Senior</strong></td>
        <td><strong>Student</strong></td>
        <td><strong>OKU</strong></td>
        <td><strong>JKM</strong></td>
        <td><strong>MAIM</strong></td>
    </tr>
    </tbody>
</table>
