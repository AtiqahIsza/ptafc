<table style="border-color: #000000; border-style: solid;">
    <thead>
    <tr>
        <th colspan="15" style="vertical-align: middle; text-align: center;">
            <strong>Daily Summary Report</strong>
        </th>
    </tr>
    <tr>
        <td colspan="15">&nbsp;</td>
    </tr>
    <tr>
        <th colspan="15">
            <strong>Network Area: {{ $networkArea }}</strong>
        </th>
    </tr>
    <tr>
        <th colspan="15">
            <strong> Reporting Period: {{ $dateFrom}} - {{ $dateTo }}</strong>
        </th>
    </tr>
    <tr>
        <th colspan="15">
            <strong>Date Printed: {{ Carbon\Carbon::now() }}</strong>
        </th>
    </tr>
    </thead>

    <tbody>
        @foreach($contents as $key1 => $reportValue)
            @php $count=1; @endphp
            @foreach($reportValue as $key2 => $allCompanies)
                @if($key2=="grand")
                    <tr>
                        <td colspan="3" style="text-align: right;">
                            <strong>Grand Total:</strong>
                        </td>
                        @foreach($allCompanies as $key3 => $grandTotal)
                            <td><strong>{{ $grandTotal }}</strong></td>
                        @endforeach
                    </tr>
                @else
                    @foreach($allCompanies as $key4 => $perCompany)
                        <tr>
                            <td colspan="15">&nbsp;</td>
                        </tr>
                        <tr>
                            <td style="text-align: center;"><strong>No.</strong></td>
                            <td style="text-align: center;"><strong>Date</strong></td>
                            <td style="text-align: center;"><strong>Day/Category</strong></td>
                            <td style="text-align: center;"><strong>Total Trip Planned </strong></td>
                            <td style="text-align: center;"><strong>Total KM Planned (KM)</strong></td>
                            <td style="text-align: center;"><strong>Total Trips Served</strong></td>
                            <td style="text-align: center;"><strong>Total KM Served (KM)</strong></td>
                            <td style="text-align: center;"><strong>Total Missed Trips</strong></td>
                            <td style="text-align: center;"><strong>Trip Compliance (%)</strong></td>
                            <td style="text-align: center;"><strong>Total Trips Late/Early Departure</strong></td>
                            <td style="text-align: center;"><strong>Total Breakdown</strong></td>
                            <td style="text-align: center;"><strong>Total Accidents</strong></td>
                            <td style="text-align: center;"><strong>Ridership (Based on passenger counter)</strong></td>
                            <td style="text-align: center;"><strong>Ridership (Based on ticket sales)</strong></td>
                            <td style="text-align: center;"><strong>Farebox Collection (RM)</strong></td>
                        </tr>
                        @foreach($perCompany as $key5 => $allRoutes)
                            @if($key5=="total_per_company")
                                <tr>
                                    <td colspan="3" style="text-align: right;">
                                        <strong>Total For {{ $key4 }}:</strong>
                                    </td>
                                    @foreach($allRoutes as $key6 => $companyTotal)
                                        <td><strong>{{ $companyTotal }}</strong></td>
                                    @endforeach
                                </tr>
                            @else
                                @foreach($allRoutes as $key7 => $allDates)
                                    @if($key7=="total_per_route")
                                        <tr>
                                            <td colspan="3" style="text-align: right;">
                                                <strong>Total For {{ $key5 }}:</strong>
                                            </td>
                                            @foreach($allDates as $key8 => $dateTotal)
                                                <td><strong>{{ $dateTotal }}</strong></td>
                                            @endforeach
                                        </tr>
                                    @else
                                        <tr>
                                            <td style="text-align: center;">{{ $count++ }}</td>
                                            <td style="text-align: center;">{{ $key7 }}</td>
                                            @foreach($allDates as $key9 => $dateData)
                                                @if($key9=="day")
                                                    <td style="text-align: center;">{{ $dateData }}</td>
                                                @else
                                                    <td style="text-align: right;">{{ $dateData }}</td>
                                                @endif
                                            @endforeach
                                        </tr>
                                    @endif
                                @endforeach
                            @endif
                        @endforeach
                    @endforeach
                @endif
            @endforeach
        @endforeach
    </tbody>

    {{-- <tbody>
        <tr>
            <td style="text-align: center;"><strong>No</strong></td>
            <td style="text-align: center;"><strong>Bus Registration Number</strong></td>
            <td style="text-align: center;"><strong>Route Name</strong></td>
            <td style="text-align: center;"><strong>Route Number</strong></td>
            <td style="text-align: center;"><strong>Actual Distance One Way (KM)</strong></td>
            <td style="text-align: center;"><strong>Number of Bus</strong></td>
            <td style="text-align: center;"><strong>Total Trip</strong></td>
            <td style="text-align: center;"><strong>Total Actual Distance</strong></td>
            <td style="text-align: center;"><strong>Total Fareship</strong></td>
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
    </tbody> --}}
</table>
