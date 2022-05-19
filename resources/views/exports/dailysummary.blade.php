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
            <strong> Company: {{$dateDaily}}</strong>
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
    @foreach($contents as $key1 => $data)
        @foreach($data['data'] as $key2 => $route)
            @foreach($route['sales'] as $key3 => $sales)
                @php $i=1 @endphp
                <tr>
                    <td style="text-align: center;">{{ $i++ }}</td>
                    <td style="text-align: center;">{{ $data['bus_no'] }}</td>
                    <td style="text-align: center;">{{ $data['route_name'] }}</td>
                    <td style="text-align: center;">{{ $data['route_number'] }}</td>
                    <td style="text-align: center;">{{ $data['count_trip'] }}</td>
                    <td style="text-align: center;">{{ $data['distance'] }}</td>
                    <td style="text-align: center;">{{ $data['count_bus'] }}</td>
                    <td style="text-align: center;">{{ $data['count_actual_trip'] }}</td>
                    <td style="text-align: center;">{{ $data['actual_distance'] }}</td>
                    <td style="text-align: center;">{{ $data['dead_distance']  }}</td>
                    <td style="text-align: center;">{{ $data['actual_distance'] }}</td>
                    <td style="text-align: center;">{{ $data['route_number'] }}</td>  <!--Income Based on Transport Letter-->
                </tr>
            @endforeach
            @foreach($route['total'] as $key4 => $total)
                <tr>
                    <td colspan="4" style="text-align: right;"><strong>Total</strong></td>
                    <td style="text-align: center;">{{ $total['total_count_trip'] }}</td>
                    <td style="text-align: center;">{{ $total['total_distance'] }}</td>
                    <td style="text-align: center;">{{ $total['total_count_bus'] }}</td>
                    <td style="text-align: center;">{{ $total['total_count_actual_trip'] }}</td>
                    <td style="text-align: center;">{{ $total['total_actual_distance'] }}</td>
                    <td style="text-align: center;">{{ $total['total_dead_distance']  }}</td>
                    <td style="text-align: center;">{{ $total['total_actual_distance'] }}</td>
                    <td style="text-align: center;">{{ $total['total_actual_distance'] }}</td>  <!--Income Based on Transport Letter-->
                </tr>
            @endforeach
        @endforeach
        @foreach($data['grand'] as $key5 => $grand)
            <tr>
                <td colspan="4" style="text-align: right;"><strong>Grand Total</strong></td>
                <td style="text-align: center;"><strong>{{ $grand['grand_count_trip'] }}</strong></td>
                <td style="text-align: center;"><strong>{{ $grand['grand_distance'] }}</strong></td>
                <td style="text-align: center;"><strong>{{ $grand['grand_count_bus'] }}</strong></td>
                <td style="text-align: center;"><strong>{{ $grand['grand_count_actual_trip'] }}</strong></td>
                <td style="text-align: center;"><strong>{{ $grand['grand_actual_distance'] }}</strong></td>
                <td style="text-align: center;"><strong>{{ $grand['grand_dead_distance']  }}</strong></td>
                <td style="text-align: center;"><strong>{{ $grand['grand_actual_distance'] }}</strong></td>
                <td style="text-align: center;"><strong>{{ $grand['grand_actual_distance'] }}</strong></td>  <!--Income Based on Transport Letter-->
            </tr>
        @endforeach
    @endforeach
    </tbody>
</table>
