<table style="border-color: #000000; border-style: solid;">
    <thead>
    <tr>
        <th colspan="16" style="vertical-align: middle; text-align: center;">
            <strong>Monthly Summary Report</strong>
        </th>
    </tr>
    <tr>
        <td colspan="16"></td>
    </tr>
    <tr>
        <th colspan="16">
            <strong>Date: {{$dateFrom}} - {{$dateTo}}</strong>
        </th>
    </tr>
    <tr>
        <th colspan="16">
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
            <td rowspan="2" style="text-align: center;"><strong>Salary of Driver</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Income Based on Transport Letter</strong></td>
        </tr>
        <tr>
            <td><strong>6AM-9AM</strong></td>
            <td><strong>4PM-8PM</strong></td>
        </tr>

        @php $i=1 @endphp
        @foreach($contents as $key1 => $value)
            @foreach($value['perRoute'] as $key2 => $data)
                <tr>
                    <td style="text-align: center;">{{ $i++ }}</td>
                    <td style="text-align: center;">{{ $data['route_name'] }}</td>
                    <td style="text-align: center;">{{ $data['route_number'] }}</td>
                    <td style="text-align: center;">{{ $data['trip_per_day']  }}</td>
                    <td style="text-align: center;">{{ $data['actual_distance_per_month']  }}</td>
                    <td style="text-align: center;">{{ $data['dead_distance_per_month']  }}</td>
                    <td style="text-align: center;">{{ $data['distance_per_month']  }}</td>
                    <td style="text-align: center;">{{ $data['operated_bus']  }}</td>
                    <td style="text-align: center;">{{ $data['operated_day']   }}</td>
                    <td style="text-align: center;">{{ $data['number_passenger']  }}</td>
                    <td style="text-align: center;">{{ $data['number_passenger_peak1']  }}</td>
                    <td style="text-align: center;">{{ $data['number_passenger_peak2']  }}</td>
                    <td style="text-align: center;"></td>
                    <td style="text-align: center;">{{ $data['number_bus_driver']  }}</td>
                    <td style="text-align: center;"></td>
                    <td style="text-align: center;"></td>
                </tr>
            @endforeach
            <tr>
                <td colspan="3" style="text-align: right;">
                    <strong>Total</strong>
                </td>
                <td style="text-align: center;"><strong>{{ $value['total']['total_trip_per_day'] }}</strong></td>
                <td style="text-align: center;"><strong>{{ $value['total']['total_actual_distance_per_month'] }}</strong></td>
                <td style="text-align: center;"><strong>{{ $value['total']['total_dead_distance_per_month'] }}</strong></td>
                <td style="text-align: center;"><strong>{{ $value['total']['total_distance_per_month'] }}</strong></td>
                <td style="text-align: center;"><strong>{{ $value['total']['total_operated_bus'] }}</strong></td>
                <td style="text-align: center;"><strong>{{ $value['total']['total_operated_day'] }}</strong></td>
                <td style="text-align: center;"><strong>{{ $value['total']['total_number_passenger'] }}</strong></td>
                <td style="text-align: center;"><strong>{{ $value['total']['total_number_passenger_peak1'] }}</strong></td>
                <td style="text-align: center;"><strong>{{ $value['total']['total_number_passenger_peak2'] }}</strong></td>
                <td style="text-align: center;"><strong></strong></td>
                <td style="text-align: center;"><strong>{{ $value['total']['total_number_bus_driver'] }}</strong></td>
                <td style="text-align: center;"><strong></strong></td>
                <td style="text-align: center;"><strong></strong></td>
            </tr>
        @endforeach

    </tbody>
</table>
