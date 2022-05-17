<table style="border-color: #000000; border-style: solid;">
    <thead>
    <tr>
        <th colspan="14" style="vertical-align: middle; text-align: center;">
            <strong>Summary By Network Report</strong>
        </th>
    </tr>
    <tr>
        <th colspan="14">&nbsp;</th>
    </tr>
    <tr>
        <th colspan="14">
            <strong>Network Operator: MARALINER</strong>
        </th>
    </tr>
    <tr>
        <th colspan="14">
            <strong>Network Area: {{ $networkArea }}</strong>
        </th>
    </tr>
    <tr>
        <th colspan="14">
            <strong> Reporting Period: {{ $dateFrom }} - {{ $dateTo }} </strong>
        </th>
    </tr>
    <tr>
        <th colspan="14">
            <strong>Date Printed: {{ Carbon\Carbon::now() }}</strong>
        </th>
    </tr>
    </thead>

    <tbody>
    <tr>
        <td colspan="14">&nbsp;</td>
    </tr>
    <tr>
        <td colspan="14">TRUNK</td>
    </tr>
    <tr>
        <td style="text-align: center;"><strong>No.</strong></td>
        <td style="text-align: center;"><strong>Route No.</strong></td>
        <td style="text-align: center;"><strong>Total KM Planned (KM)</strong></td>
        <td style="text-align: center;"><strong>Total KM Served (KM)</strong></td>
        <td style="text-align: center;"><strong>Total Claim (RM)</strong></td>
        <td style="text-align: center;"><strong>Total Trips Planned</strong></td>
        <td style="text-align: center;"><strong>Total Trips Served</strong></td>
        <td style="text-align: center;"><strong>No. Of Buses Deployed</strong></td>
        <td style="text-align: center;"><strong>Total Missed Trips</strong></td>
        <td style="text-align: center;"><strong>Total Trips Late/Early Departure</strong></td>
        <td style="text-align: center;"><strong>Total Breakdown</strong></td>
        <td style="text-align: center;"><strong>Total Accidents</strong></td>
        <td style="text-align: center;"><strong>Ridership</strong></td>
        <td style="text-align: center;"><strong>Farebox Collection (RM)</strong></td>
    </tr>
    @foreach($reports as $key1 => $reportValue)
        @php $count=1; @endphp
        @foreach($reportValue as $key2 => $allRoutes)
            @if($key2=="grand")
                <tr>
                    <td colspan="2" style="text-align: right;">
                        <strong>Total For Network: TRUNK</strong>
                    </td>
                    @foreach($allRoutes as $key3 => $total)
                        <td><strong>{{ $total }}</strong></td>
                    @endforeach
                </tr>
            @else
                <tr>
                    <td style="text-align: center;">{{ $count++ }}</td>
                    <td style="text-align: center;">{{ $key2 }}</td>
                    @foreach($allRoutes as $key4 => $dataPerRoute)
                        <td style="text-align: right;">{{ $dataPerRoute }}</td>
                    @endforeach
                </tr>
            @endif
        @endforeach
    @endforeach
    </tbody>
</table>
