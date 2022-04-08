<table style="border-color: #000000; border-style: solid;">
    <thead>
    <tr>
        <th colspan="16" style="vertical-align: middle; text-align: center;">
            <strong>Route Report</strong>
        </th>
    </tr>
    <tr>
        <td colspan="16">&nbsp;</td>
    </tr>
    <tr>
        <th colspan="16">
            <strong>Network Operator: </strong>
        </th>
    </tr>
    <tr>
        <th colspan="16">
            <strong>Network Area: </strong>
        </th>
    </tr>
    <tr>
        <th colspan="16">
            <strong> Reporting Period: {{ $dateFrom }} - {{ $dateTo }} </strong>
        </th>
    </tr>
    <tr>
        <th colspan="16">
            <strong>Date Printed: {{ Carbon\Carbon::now() }}</strong>
        </th>
    </tr>
    <tr>
        <td colspan="16">&nbsp;</td>
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
        <td rowspan="2" style="text-align: center;"><strong>Number of Trips Made</strong></td>
        <td rowspan="2" style="text-align: center;"><strong>Passenger Boarding Count</strong></td>
        <td colspan="4" style="text-align: center;"><strong>ETM Boarding Passenger Count</strong></td>
        <td colspan="2" style="text-align: center;"><strong>Previous Highest Patronage</strong></td>
        <td colspan="2" style="text-align: center;"><strong>Previous Highest Sales</strong></td>
    </tr>
    <tr>
        <td><strong>Transfer Count</strong></td>
        <td><strong>Adult</strong></td>
        <td><strong>Concession</strong></td>
        <td><strong>Total On</strong></td>
        <td><strong>Total Pax</strong></td>
        <td><strong>% Increase</strong></td>
        <td><strong>Total Sales Amount</strong></td>
        <td><strong>% Increase</strong></td>
    </tr>

    @foreach($contents as $key1 => $value)
        <tr>
            @foreach($value[0] as $key2 => $dataIn)
            <td style="text-align: center;">{{ $dataIn }}</td>
            {{--<td style="text-align: center;">{{ $data['route_name']  }}</td>
            <td style="text-align: center;">{{ $data['num_km_planned'] }}</td>
            <td style="text-align: center;">{{ $data['num_km_served']  }}</td>
            <td style="text-align: center;">{{ $data['num_km_served_gps'] }}</td>
            <td style="text-align: center;">{{ $data['num_scheduled_trip']  }}</td>
            <td style="text-align: center;">{{ $data['num_trip_made'] }}</td>
            <td style="text-align: center;">{{ $data['count_passenger_board']  }}</td>
            <td style="text-align: center;">{{ $data['num_adult'] }}</td>
            <td style="text-align: center;">{{ $data['num_concession']  }}</td>
            <td style="text-align: center;">{{ $data['num_concession'] }}</td>
            <td style="text-align: center;">{{ $data['num_concession'] }}</td>
            <td style="text-align: center;">{{ $data['num_concession'] }}</td>
            <td style="text-align: center;">{{ $data['num_concession'] }}</td>--}}
            @endforeach
        </tr>
        <tr>
            @foreach($value[1] as $key3 => $dataOut)
                <td style="text-align: center;">{{ $dataOut }}</td>
            @endforeach
        </tr>
        <tr>
            <td colspan="2" style="text-align: right;"><strong>Grand Total:</strong></td>
            @foreach($value['total'] as $key4 => $total)
                <td style="text-align: center;">{{ $total }}</td>
                {{--<td style="text-align: center;">{{ $total['tot_num_km_planned'] }}</td>
                <td style="text-align: center;">{{ $total['tot_num_km_served']  }}</td>
                <td style="text-align: center;">{{ $total['tot_num_km_served_gps'] }}</td>
                <td style="text-align: center;">{{ $total['tot_num_scheduled_trip']  }}</td>
                <td style="text-align: center;">{{ $total['tot_num_trip_made'] }}</td>
                <td style="text-align: center;">{{ $total['tot_count_passenger_board']  }}</td>
                <td style="text-align: center;">{{ $total['tot_num_adult'] }}</td>
                <td style="text-align: center;">{{ $total['tot_num_concession']  }}</td>
                <td style="text-align: center;">{{ $total['tot_num_concession'] }}</td>
                <td style="text-align: center;">{{ $total['tot_num_concession'] }}</td>
                <td style="text-align: center;">{{ $total['tot_num_concession'] }}</td>
                <td style="text-align: center;">{{ $total['tot_num_concession'] }}</td>--}}
            @endforeach
        </tr>
    @endforeach
    </tbody>
</table>
