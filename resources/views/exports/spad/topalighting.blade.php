<table style="border-color: #000000; border-style: solid;">
    <thead>
    <tr>
        <th colspan="8" style="vertical-align: middle; text-align: center;">
            <strong>Top Alighting Report</strong>
        </th>
    </tr>
    <tr>
        <th colspan="8">&nbsp;</th>
    </tr>
    <tr>
        <th colspan="8">
            <strong>Network Operator: MARALINER</strong>
        </th>
    </tr>
    <tr>
        <th colspan="8">
            <strong>Network Area: {{$networkArea}}</strong>
        </th>
    </tr>
    <tr>
        <th colspan="8">
            <strong> Reporting Period: {{ $dateFrom }} - {{ $dateTo }} </strong>
        </th>
    </tr>
    <tr>
        <th colspan="8">
            <strong>Date Printed: {{ Carbon\Carbon::now() }}</strong>
        </th>
    </tr>
    </thead>

    <tbody>
    @foreach($reports as $key1 => $reportValue)
        @if(array_key_exists("allRoute",$reportValue))
            @foreach($reportValue['allRoute'] as $key2 => $allRoutes)
                @if($allRoutes!=NULL)
                    <tr>
                        <td colspan="8">&nbsp;</td>
                    </tr>
                    <tr>
                        <td rowspan="2" style="text-align: center;"><strong>Route No.</strong></td>
                        <td rowspan="2" style="text-align: center;"><strong>Bus Stop Description</strong></td>
                        <td colspan="3" style="text-align: center;"><strong>ETM Alighting Passenger Count (Inbound)</strong></td>
                        <td colspan="3" style="text-align: center;"><strong>ETM Alighting Passenger Count (Outbound)</strong></td>
                    </tr>
                    <tr>
                        <td><strong>Total Off</strong></td>
                        <td><strong>Adult</strong></td>
                        <td><strong>Concession</strong></td>
                        <td><strong>Total Off</strong></td>
                        <td><strong>Adult</strong></td>
                        <td><strong>Concession</strong></td>
                    </tr>
                    @foreach($allRoutes as $key3 => $allStage)
                        @if($key3=='total_per_route')
                            <tr>
                                <td colspan="2" style="text-align: right;">
                                    <strong>Total For Route No: {{$key2}}</strong>
                                </td>
                                @foreach($allStage as $key4 => $total)
                                    <td><strong>{{ $total }}</strong></td>
                                @endforeach
                            </tr>
                        @else
                            <tr>
                                <td style="text-align: center;">{{ $key2 }}</td>
                                <td style="text-align: center;">{{ $key3 }}</td>
                                @foreach($allStage as $key5 => $perStage)
                                    <td style="text-align: center;">{{ $perStage }}</td>
                                @endforeach
                            </tr>
                        @endif
                    @endforeach
                @endif
            @endforeach
        @endif
        @if(array_key_exists("grand",$reportValue))
            <tr>
                <td colspan="2" style="text-align: right;">
                    <strong>Grand Total:</strong>
                </td>
                @foreach($reportValue['grand'] as $key6 => $grand)
                    <td><strong>{{ $grand }}</strong></td>
                @endforeach
            </tr>
        @endif
    @endforeach
    </tbody>
</table>
