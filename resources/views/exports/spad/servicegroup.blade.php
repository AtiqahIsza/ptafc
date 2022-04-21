<table style="border-color: #000000; border-style: solid;">
    <thead>
    <tr>
        <th colspan="7" style="vertical-align: middle; text-align: center;">
            <strong>Service Group Report</strong>
        </th>
    </tr>
    <tr>
        <td colspan="7">&nbsp;</td>
    </tr>
    <tr>
        <th colspan="7">
            <strong>Network Operator: MARALINER</strong>
        </th>
    </tr>
    <tr>
        <th colspan="7">
            <strong>Network Area: {{$networkArea}}</strong>
        </th>
    </tr>
    <tr>
        <th colspan="7">
            <strong> Reporting Period: {{ $dateFrom }} - {{ $dateTo }} </strong>
        </th>
    </tr>
    <tr>
        <th colspan="7">
            <strong>Date Printed: {{ Carbon\Carbon::now() }}</strong>
        </th>
    </tr>
    <tr>
        <td colspan="7">&nbsp;</td>
    </tr>
    </thead>

    <tbody>
    <tr>
        <td rowspan="2" style="text-align: center;"><strong>Service Group</strong></td>
        <td rowspan="2" style="text-align: center;"><strong>Number of Scheduled Trips</strong></td>
        <td rowspan="2" style="text-align: center;"><strong>Number of Trips Made</strong></td>
        <td rowspan="2" style="text-align: center;"><strong>Passengers Boarding Count</strong></td>
        <td colspan="3" style="text-align: center;"><strong>ETM Boarding Passenger Count</strong></td>
    </tr>
    <tr>
        <td style="text-align: center;"><strong>Total On</strong></td>
        <td style="text-align: center;"><strong>Adult</strong></td>
        <td style="text-align: center;"><strong>Concession</strong></td>
    </tr>

    @foreach($reports as $key1 => $value)
        <tr>
            <td style="text-align: center;">MARALINER</td>
            <td style="text-align: center;">{{ $value['num_scheduled_trip'] }}</td>
            <td style="text-align: center;">{{ $value['num_trip_made']  }}</td>
            <td style="text-align: center;">{{ $value['count_passenger_board'] }}</td>
            <td style="text-align: center;">{{ $value['count_passenger_board'] }}</td>
            <td style="text-align: center;">{{ $value['num_adult']  }}</td>
            <td style="text-align: center;">{{ $value['num_concession'] }}</td>
        </tr>
        <tr>
            <td style="text-align: right;"><strong>Total:</strong></td>
            <td style="text-align: center;"><strong>{{ $value['num_scheduled_trip'] }}</strong></td>
            <td style="text-align: center;"><strong>{{ $value['num_trip_made']  }}</strong></td>
            <td style="text-align: center;"><strong>{{ $value['count_passenger_board'] }}</strong></td>
            <td style="text-align: center;"><strong>{{ $value['count_passenger_board'] }}</strong></td>
            <td style="text-align: center;"><strong>{{ $value['num_adult']  }}</strong></td>
            <td style="text-align: center;"><strong>{{ $value['num_concession'] }}</strong></td>
        </tr>
    @endforeach
    </tbody>
</table>
