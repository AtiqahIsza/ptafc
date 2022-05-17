<table style="border-color: #000000; border-style: solid;">
    <thead>
    <tr>
        <th colspan="7" style="vertical-align: middle; text-align: center;">
            <strong>Missed Trip Report</strong>
        </th>
    </tr>
    <tr>
        <th colspan="7">&nbsp;</th>
    </tr>
    <tr>
        <th colspan="7">
            <strong>Network Operator: MARALINER</strong>
        </th>
    </tr>
    <tr>
        <th colspan="7">
            <strong>Network Area: {{ $networkArea }}</strong>
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
    </thead>

    <tbody>

        @foreach($reports as $key1 => $values)
            @foreach($values as $key2 => $allRouteArr)
                @if($key2=="grand")
                    @if($allRouteArr!=NULL)
                        <tr>
                            <td colspan="6" style="text-align: right;">
                                <strong>Grand Total Trip Missed:</strong>
                            </td>
                            <td><strong>{{ $allRouteArr }}</strong></td>
                        </tr>
                    @endif
                @else
                    @foreach($allRouteArr as $key3 => $allRoutes)
                        @if($allRoutes!=NULL)
                            @foreach($allRoutes as $key4 => $allDatesArr)
                                @if($key4=="total_per_route")
                                    <tr>
                                        <td colspan="6" style="text-align: right;">
                                            <strong>Total Trip Missed For Route No: {{ $key3 }}</strong>
                                        </td>
                                        <td><strong>{{ $allDatesArr }}</strong></td>
                                    </tr>
                                    <tr>
                                        <td colspan="7">&nbsp;</td>
                                    </tr>
                                @else
                                    @foreach($allDatesArr as $key5 => $allDates)
                                        @foreach($allDates as $key6 => $allInOut)
                                            @if($key6=="total_per_date")
                                                @if($allInOut!=0)
                                                    <tr>
                                                        <td colspan="6" style="text-align: right;">
                                                            <strong>Total Trip Missed For Date: {{ $key5 }}</strong>
                                                        </td>
                                                        <td><strong>{{ $allInOut }}</strong></td>
                                                    </tr>
                                                @endif
                                            @else
                                                @if($allInOut!=NULL)
                                                    <tr>
                                                        <td colspan="7">&nbsp;</td>
                                                    </tr>
                                                    <tr>
                                                        <td style="text-align: center;"><strong>Route No.</strong></td>
                                                        <td style="text-align: center;"><strong>OD</strong></td>
                                                        <td style="text-align: center;"><strong>Trip Schedule Time</strong></td>
                                                        <td style="text-align: center;"><strong>Trip No.</strong></td>
                                                        <td style="text-align: center;"><strong>Bus Reg No.</strong></td>
                                                        <td style="text-align: center;"><strong>Vehicle Age</strong></td>
                                                        <td style="text-align: center;"><strong>KM Rate</strong></td>
                                                    </tr>
                                                    @foreach($allInOut as $key7 => $allTrips)
                                                        @if($key7=="total_per_inbound" || $key7=="total_per_outbound")
                                                            <tr>
                                                                <td colspan="6" style="text-align: right;">
                                                                    <strong>Total Trip Missed For ({{ $key5 }} - {{ $key3 }} {{ $key6 }}</strong>
                                                                </td>
                                                                <td><strong>{{ $allTrips }}</strong></td>
                                                            </tr>
                                                        @else
                                                            @foreach($allTrips as $key8 => $trips)
                                                            <tr>
                                                                <td style="text-align: center;"><strong>{{ $key3 }}</strong></td>
                                                                @foreach($trips as $key9 => $tripData)
                                                                    <td style="text-align: center;">{{$tripData}}</td>
                                                                @endforeach
                                                            </tr>
                                                            @endforeach
                                                        @endif
                                                    @endforeach
                                                @endif
                                            @endif
                                        @endforeach
                                    @endforeach
                                @endif
                            @endforeach
                        @endif
                    @endforeach
                @endif
            @endforeach
        @endforeach

    </tbody>
</table>
