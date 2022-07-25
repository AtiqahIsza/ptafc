<table style="border-color: #000000; border-style: solid;">
    <thead>
    <tr>
        <th colspan="14" style="vertical-align: middle; text-align: center;">
            <strong>Summary By Route Report</strong>
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
        @foreach($reports as $key1 => $reportValue)
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
                            <td colspan="14">&nbsp;</td>
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
</table>
