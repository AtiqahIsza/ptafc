<table style="border-color: #000000; border-style: solid;">
    <thead>
    <tr>
        <th colspan="15" style="vertical-align: middle; text-align: center;">
            <strong>Route Report</strong>
        </th>
    </tr>
    <tr>
        <td colspan="15">&nbsp;</td>
    </tr>
    <tr>
        <th colspan="15">
            <strong>Network Operator: Maraliner</strong>
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
    <tr>
        <td colspan="15">&nbsp;</td>
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
        <td colspan="3" style="text-align: center;"><strong>ETM Boarding Passenger Count</strong></td>
        <td colspan="2" style="text-align: center;"><strong>Previous Highest Patronage</strong></td>
        <td colspan="2" style="text-align: center;"><strong>Previous Highest Sales</strong></td>
    </tr>
    <tr>
        <td><strong>Adult</strong></td>
        <td><strong>Concession</strong></td>
        <td><strong>Total On</strong></td>
        <td><strong>Total Pax</strong></td>
        <td><strong>% Increase</strong></td>
        <td><strong>Total Sales Amount</strong></td>
        <td><strong>% Increase</strong></td>
    </tr>

    @foreach($reports as $key1 => $reportValue)
        @foreach($reportValue as $key2 => $allRoute)
            @if($key2=="grand")
                <tr>
                    <td colspan="2" style="text-align: right;"><strong>Grand Total:</strong></td>
                    <td><strong>{{ $allRoute['grand_num_km_planned'] }}</strong></td>
                    <td><strong>{{ $allRoute['grand_num_km_served']  }}</strong></td>
                    <td><strong>{{ $allRoute['grand_num_km_served_gps'] }}</strong></td>
                    <td><strong>{{ $allRoute['grand_num_scheduled_trip']  }}</strong></td>
                    <td><strong>{{ $allRoute['grand_num_trip_made'] }}</strong></td>
                    <td><strong>{{ $allRoute['grand_count_passenger_board']  }}</strong></td>
                    <td><strong>{{ $allRoute['grand_total_on'] }}</strong></td>
                    <td><strong>{{ $allRoute['grand_num_adult'] }}</strong></td>
                    <td><strong>{{ $allRoute['grand_num_concession']  }}</strong></td>
                    <td><strong>{{ $allRoute['grand_total_pax'] }}</strong></td>
                    <td><strong>{{ $allRoute['grand_total_pax_increase'] }}</strong></td>
                    <td><strong>{{ $allRoute['grand_total_sales'] }}</strong></td>
                    <td><strong>{{ $allRoute['grand_total_sales_increase'] }}</strong></td>-
                </tr>
            @else
                <tr>
                    <td colspan="15">&nbsp;</td>
                </tr>
                @foreach($allRoute as $key3 => $perRoute)
                    @if($key3=='total')
                        <tr>
                            <td colspan="2" style="text-align: right;"><strong>Total For Route No.: {{ $key2 }}</strong></td>
                            @foreach($perRoute as $total)
                                <td><strong>{{$total}}</strong></td>
                            @endforeach
                        </tr>
                    @endif
                    @if($key3=='inbound' || $key3=='outbound')
                        <tr>
                            <td>{{ $key2 }}</td>
                            @foreach($perRoute as $data)
                                <td>{{ $data }}</td>
                            @endforeach
                        </tr>
                    @endif
                @endforeach
            @endif
        @endforeach
    @endforeach
    </tbody>
</table>
