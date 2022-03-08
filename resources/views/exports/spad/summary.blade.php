<table style="border-color: #000000; border-style: solid;">
    <thead>
    <tr>
        <th colspan="24" style="vertical-align: middle; text-align: center;">
            <strong>{{$routeNo}} Summary Report</strong>
        </th>
    </tr>
    <tr>
        <td colspan="24">&nbsp;</td>
    </tr>
    <tr>
        <th colspan="24">
            <strong>Network Operator: </strong>
        </th>
    </tr>
    <tr>
        <th colspan="24">
            <strong>Network Area: </strong>
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
    <tr>
        <td colspan="24">&nbsp;</td>
    </tr>
    </thead>

    <tbody>
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

    @php $i=1 @endphp
    @foreach($routes as $route)
        <tr>
            <td style="text-align: center;">{{ $i++ }}</td>
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
        </tr>
    @endforeach
    <tr>
        <td colspan="3" style="text-align: right;">
            <strong>Total for Route {{$routeNo}}:</strong>
        </td>
        <td><strong>Ridership</strong></td>
        <td><strong>Previous Month Ridership Collection</strong></td>
        <td><strong>% Increase</strong></td>
        <td><strong>Farebox Collection</strong></td>
        <td><strong>Previous Month Farebox Collection</strong></td>
        <td><strong>Increment Farebox Collection (%)</strong></td>
        <td><strong>Average Fare per Pax (RM)</strong></td>
        <td><strong>Number of Trips Planned</strong></td>
        <td><strong>Number of Trips Made</strong></td>
        <td><strong>Number of Trips Missed</strong></td>
        <td><strong>Total KM Service Planned</strong></td>
        <td><strong>Total KM Service Served</strong></td>
        <td><strong>Total KM Service Planned By GPS</strong></td>
        <td><strong>Total Early Departure</strong></td>
        <td><strong>Total Late Departure</strong></td>
        <td><strong>Total Early End</strong></td>
        <td><strong>Total Late End</strong></td>
        <td><strong>Total Breakdown During Operation</strong></td>
        <td><strong>Total Bus In Used</strong></td>
        <td><strong>Total Accidents Caused By Operator</strong></td>
        <td><strong>Total Complaints</strong></td>
    </tr>
    <tr>
        <td colspan="3" style="text-align: right;">
            <strong>Grand Total:</strong>
        </td>
        <td><strong>Ridership</strong></td>
        <td><strong>Previous Month Ridership Collection</strong></td>
        <td><strong>% Increase</strong></td>
        <td><strong>Farebox Collection</strong></td>
        <td><strong>Previous Month Farebox Collection</strong></td>
        <td><strong>Increment Farebox Collection (%)</strong></td>
        <td><strong>Average Fare per Pax (RM)</strong></td>
        <td><strong>Number of Trips Planned</strong></td>
        <td><strong>Number of Trips Made</strong></td>
        <td><strong>Number of Trips Missed</strong></td>
        <td><strong>Total KM Service Planned</strong></td>
        <td><strong>Total KM Service Served</strong></td>
        <td><strong>Total KM Service Planned By GPS</strong></td>
        <td><strong>Total Early Departure</strong></td>
        <td><strong>Total Late Departure</strong></td>
        <td><strong>Total Early End</strong></td>
        <td><strong>Total Late End</strong></td>
        <td><strong>Total Breakdown During Operation</strong></td>
        <td><strong>Total Bus In Used</strong></td>
        <td><strong>Total Accidents Caused By Operator</strong></td>
        <td><strong>Total Complaints</strong></td>
    </tr>
    </tbody>
</table>
