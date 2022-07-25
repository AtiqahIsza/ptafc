<table style="border-color: #000000; border-style: solid;">
    <thead>
    <tr>
        <th colspan="14" style="vertical-align: middle; text-align: center;">
            <strong>Collection By Company Report</strong>
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
    @foreach($reports as $key => $reportValue)
        @foreach($reportValue as $key1 => $allCompany)
            @if($key1=="grand")
                <tr>
                    <td colspan="3" style="text-align: right;">
                        <strong>Grand Total: </strong>
                    </td>
                    @foreach($allCompany as $key2 => $grandTotal)
                        <td><strong>{{ $grandTotal }}</strong></td>
                    @endforeach
                </tr>
            @else
                @foreach($allCompany as $key3 => $perCompany)
                    <tr>
                        <td colspan="14">&nbsp;</td>
                    </tr>
                    <tr>
                        <td colspan="14"><strong>Company Name: {{ $key3 }}</strong></td>
                    </tr>
                    <tr>
                        <td style="text-align: center;"><strong>No.</strong></td>
                        <td style="text-align: center;"><strong>Route No.</strong></td>
                        <td style="text-align: center;"><strong>Route Name</strong></td>
                        <td style="text-align: center;"><strong>Total Claim (RM)</strong></td>
                        <td style="text-align: center;"><strong>Total Trips Planned</strong></td>
                        <td style="text-align: center;"><strong>Total Trips Served</strong></td>
                        <td style="text-align: center;"><strong>Total Missed Trips</strong></td>
                        <td style="text-align: center;"><strong>Total Trips Late/Early Departure</strong></td>
                        <td style="text-align: center;"><strong>Total Breakdown</strong></td>
                        <td style="text-align: center;"><strong>Total Accidents</strong></td>
                        <td style="text-align: center;"><strong>Ridership</strong></td>
                        <td style="text-align: center;"><strong>Cash Collection (RM)</strong></td>
                        <td style="text-align: center;"><strong>Card Collection (RM)</strong></td>
                        <td style="text-align: center;"><strong>Touch N Go Collection (RM)</strong></td>
                    </tr>
                    @php $count=1; @endphp
                    @foreach($perCompany as $key4 => $allRoutes)
                        @if($key4=="total_per_company")
                            <tr>
                                <td colspan="3" style="text-align: right;">
                                    <strong>Total For Company: {{ $key3 }}</strong>
                                </td>
                                @foreach($allRoutes as $key5 => $companyTotal)
                                    <td><strong>{{ $companyTotal }}</strong></td>
                                @endforeach
                            </tr>
                        @else
                            @foreach($allRoutes as $key6 => $perRoute)
                            <tr>
                                <td style="text-align: center;">{{ $count++ }}</td>
                                <td style="text-align: center;">{{ $key6 }}</td>
                                @foreach($perRoute as $key7 => $dataPerRoute)
                                    @if($key7=="route_name")
                                        <td style="text-align: center;">{{ $dataPerRoute }}</td>
                                    @else
                                        <td style="text-align: right;">{{ $dataPerRoute }}</td>
                                    @endif
                                @endforeach
                            </tr>
                            @endforeach
                        @endif
                    @endforeach
                @endforeach
            @endif
        @endforeach
    @endforeach
    </tbody>
</table>
