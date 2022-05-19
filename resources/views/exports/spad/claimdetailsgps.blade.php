<table style="border-color: #000000; border-style: solid;">
    <thead>
    <tr>
        <th colspan="13" style="vertical-align: middle; text-align: center;">
            <strong>Claim Details GPS Report</strong>
        </th>
    </tr>
    <tr>
        <th colspan="13">&nbsp;</th>
    </tr>
    <tr>
        <th colspan="13">
            <strong>Network Operator: MARALINER</strong>
        </th>
    </tr>
    <tr>
        <th colspan="13">
            <strong>Network Area: {{ $networkArea }}</strong>
        </th>
    </tr>
    <tr>
        <th colspan="13">
            <strong> Reporting Period: {{ $dateFrom }} - {{ $dateTo }} </strong>
        </th>
    </tr>
    <tr>
        <th colspan="13">
            <strong>Date Printed: {{ Carbon\Carbon::now() }}</strong>
        </th>
    </tr>
    </thead>

    <tbody>
    @foreach($reports as $key1 => $reportValue)
        @if(array_key_exists("allRoute",$reportValue))
            <tr>
                <th colspan="13">
                    <strong> Trip: Trip ID - Route No - Route Name - IB/OB - Service Date {{ $dateFrom }} - {{ $dateTo }} </strong>
                </th>
            </tr>
            @foreach($reportValue['allRoute'] as $key2 => $allDates)
                @php $existTrip = false; @endphp
                @foreach($allDates as $key3 => $dataPerDate)
                    @if($key3=='total_per_route')
                        @if($existTrip ==true)
                            <tr>
                                <td colspan="14" style="text-align: right;">
                                    <strong>Total for Route No: {{ $key2 }}</strong>
                                </td>
                                @foreach($dataPerDate as $key4 => $totalPerDate)
                                    <td><strong>{{ $totalPerDate }}</strong></td>
                                @endforeach
                            </tr>
                        @endif
                    @else
                        @if($dataPerDate!=NULL)
                            @php $existTrip = true; @endphp
                            @foreach($dataPerDate as $key4 => $allTrip)
                                @if($key4=='total_per_date')
                                    <tr>
                                        <td colspan="14" style="text-align: right;">
                                            <strong>Total for Service Date: {{ $key3 }}</strong>
                                        </td>
                                        @foreach($allTrip as $key5 => $totalDate)
                                            <td><strong>{{$totalDate}}</strong></td>
                                        @endforeach
                                    </tr>
                                @else
                                    @foreach($allTrip as $key6 => $perTrip)
                                        @if($key6=='total')
                                            <tr>
                                                <td colspan="14" style="text-align: right;">
                                                    <strong>Total For Route OD: {{ $key4 }}</strong>
                                                </td>
                                                @foreach($perTrip as $key7 => $totalRoute)
                                                    <td><strong>{{$totalRoute}}</strong></td>
                                                @endforeach
                                            </tr>
                                        @else
                                            <tr>
                                                <td colspan="19">&nbsp;</td>
                                            </tr>
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
                                                <td colspan="3" style="text-align: center;"><strong>ETM Boarding Passenger Count</strong></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Total On</strong></td>
                                                <td><strong>Adult</strong></td>
                                                <td><strong>Concession</strong></td>
                                            </tr>
                                            <tr>
                                                <td style="text-align: center;">{{ $key2 }}</td>
                                                <td style="text-align: center;">{{ $key4 }}</td>
                                                <td style="text-align: center;">{{ $key6 }}</td>
                                                @foreach($perTrip as $key8 => $perTripData)
                                                    <td style="text-align: center;">{{ $perTripData }}</td>
                                                @endforeach
                                            </tr>
                                        @endif
                                    @endforeach
                                @endif
                            @endforeach
                        @endif
                    @endif
                @endforeach
            @endforeach
        @endif
        @if(array_key_exists("grand",$reportValue))
            <tr>
                <td colspan="14" style="text-align: right;">
                    <strong>Grand Total:</strong>
                </td>
                @foreach($reportValue['grand'] as $key9 => $grand)
                    <td><strong>{{$grand}}</strong></td>
                @endforeach
            </tr>
        @endif
    @endforeach
    </tbody>
</table>
