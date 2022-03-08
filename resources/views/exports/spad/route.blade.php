<table style="border-color: #000000; border-style: solid;">
    <thead>
    <tr>
        <th colspan="22" style="vertical-align: middle; text-align: center;">
            <strong>{{$routeNo}} Route Report</strong>
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
    <tr>
        <td rowspan="2" style="text-align: center;"><strong>Route No.</strong></td>
        <td rowspan="2" style="text-align: center;"><strong>OD</strong></td>
        <td rowspan="2" style="text-align: center;"><strong>Total KM Planned</strong></td>
        <td rowspan="2" style="text-align: center;"><strong>Total KM Served</strong></td>
        <td rowspan="2" style="text-align: center;"><strong>Total KM Served GPS</strong></td>
        <td rowspan="2" style="text-align: center;"><strong>Number of Scheduled Trips</strong></td>
        <td rowspan="2" style="text-align: center;"><strong>Numberof Trips Made</strong></td>
        <td rowspan="2" style="text-align: center;"><strong>Passenger Boarding Count</strong></td>
        <td colspan="10" style="text-align: center;"><strong>ETM Boarding Passenger Count</strong></td>
        <td colspan="2" style="text-align: center;"><strong>Previous Highest Patronage</strong></td>
        <td colspan="2" style="text-align: center;"><strong>Previous Highest Sales</strong></td>
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
        <td><strong>Total Pax</strong></td>
        <td><strong>% Increase</strong></td>
        <td><strong>Total Sales Amount</strong></td>
        <td><strong>% Increase</strong></td>
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
        </tr>
    @endforeach
    <tr>
        <td colspan="2" style="text-align: right;">
            <strong>Total For Route No: {{$routeNo}}</strong>
        </td>
        <td><strong>Total KM Planned</strong></td>
        <td><strong>Total KM Served</strong></td>
        <td><strong>Total KM Served GPS</strong></td>
        <td><strong>Number of Scheduled Trips</strong></td>
        <td><strong>Number of Trips Made</strong></td>
        <td><strong>Passenger Boarding Count</strong></td>
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
        <td><strong>Total Pax</strong></td>
        <td><strong>% Increase</strong></td>
        <td><strong>Total Sales Amount</strong></td>
        <td><strong>% Increase</strong></td>
    </tr>
    <tr>
        <td colspan="2" style="text-align: right;">
            <strong>Grand Total:</strong>
        </td>
        <td><strong>Total KM Planned</strong></td>
        <td><strong>Total KM Served</strong></td>
        <td><strong>Total KM Served GPS</strong></td>
        <td><strong>Number of Scheduled Trips</strong></td>
        <td><strong>Number of Trips Made</strong></td>
        <td><strong>Passenger Boarding Count</strong></td>
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
        <td><strong>Total Pax</strong></td>
        <td><strong>% Increase</strong></td>
        <td><strong>Total Sales Amount</strong></td>
        <td><strong>% Increase</strong></td>
    </tr>
    </tbody>
</table>
