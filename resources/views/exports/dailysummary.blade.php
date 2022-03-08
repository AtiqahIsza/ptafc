<table style="border-color: #000000; border-style: solid;">
    <thead>
    <tr>
        <th colspan="12" style="vertical-align: middle; text-align: center;">
            <strong>Daily Details Report</strong>
        </th>
    </tr>
    <tr>
        <td colspan="12">&nbsp;</td>
    </tr>
    <tr>
        <th colspan="12">
            <strong>Date: {{$dateDaily}}</strong>
        </th>
    </tr>
    <tr>
        <th colspan="12">
            <strong> Company: </strong>
        </th>
    </tr>
    </thead>

    <tbody>
    <tr>
        <td style="text-align: center;"><strong>No</strong></td>
        <td style="text-align: center;"><strong>Bus Number</strong></td>
        <td style="text-align: center;"><strong>Route Name</strong></td>
        <td style="text-align: center;"><strong>Route Number</strong></td>
        <td style="text-align: center;"><strong>Number of Trip per Day(One way)</strong></td>
        <td style="text-align: center;"><strong>Actual Distance per Day (One way)</strong></td>
        <td style="text-align: center;"><strong>Number of Trip per Day</strong></td>
        <td style="text-align: center;"><strong>Actual Distance per Day</strong></td>
        <td style="text-align: center;"><strong>Total Operated Bus</strong></td>
        <td style="text-align: center;"><strong>Total Dead Distance</strong></td>
        <td style="text-align: center;"><strong>Total Distance</strong></td>
        <td style="text-align: center;"><strong>Income Based on Transport Letter</strong></td>
    </tr>

    @foreach($routes as $route)
        @php $i=1 @endphp
        @foreach($stages as $stage)
                <tr>
                    <td style="text-align: center;">{{ $i++ }}</td>
                    <td style="text-align: center;">{{ $stage->stage_name }}</td>
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
                </tr>
        @endforeach
        <tr>
            <td colspan="4" style="text-align: right;"><strong>Total</strong></td>
            <td style="text-align: center;"><strong>Trip</strong></td>
            <td style="text-align: center;"><strong>Actual Distance</strong></td>
            <td style="text-align: center;"><strong>Number of Trip</strong></td>
            <td style="text-align: center;"><strong>Actual Distance</strong></td>
            <td style="text-align: center;"><strong>Total Bus</strong></td>
            <td style="text-align: center;"><strong>Total Dead Distance</strong></td>
            <td style="text-align: center;"><strong>Total Distance</strong></td>
            <td style="text-align: center;"><strong>Income</strong></td>

        </tr>
    @endforeach
    <tr>
        <td colspan="4" style="text-align: right;">
            <strong>Final Total</strong>
        </td>
        <td style="text-align: center;"><strong>Trip</strong></td>
        <td style="text-align: center;"><strong>Actual Distance</strong></td>
        <td style="text-align: center;"><strong>Number of Trip</strong></td>
        <td style="text-align: center;"><strong>Actual Distance</strong></td>
        <td style="text-align: center;"><strong>Total Bus</strong></td>
        <td style="text-align: center;"><strong>Total Dead Distance</strong></td>
        <td style="text-align: center;"><strong>Total Distance</strong></td>
        <td style="text-align: center;"><strong>Income</strong></td>
    </tr>
    </tbody>
</table>
