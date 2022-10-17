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
        @php $count=1;@endphp
        @php $newCompany=false;@endphp
        @foreach($contents as $key1 => $reportValue)
            @if(!property_exists($reportValue, 'service_date') && !property_exists($reportValue, 'route_number') && !property_exists($reportValue, 'company_name'))
            <tr>
                <td colspan="3" style="text-align: right;">
                    <strong>Grand Total :</strong>
                </td>
                <td><strong>{{ $reportValue->trip_planned }}</strong></td>
                <td><strong>{{ $reportValue->km_planned }}</strong></td>
                <td><strong>{{ $reportValue->trip_served }}</strong></td>
                <td><strong>{{ $reportValue->km_served }}</strong></td>
                <td><strong>{{ $reportValue->missed_trip }}</strong></td>
                <td><strong>{{ $reportValue->trip_compliance }}</strong></td>
                <td><strong>{{ $reportValue->earlyLate }}</strong></td>
                <td><strong>0</strong></td>
                <td><strong>0</strong></td>
                <td><strong>{{ $reportValue->ridership }}</strong></td>
                <td><strong>{{ $reportValue->ridership }}</strong></td>
                <td><strong>{{ $reportValue->farebox}}</strong></td>
            </tr>
            @elseif(!property_exists($reportValue, 'service_date') && property_exists($reportValue, 'route_number'))
                <tr>
                    <td colspan="3" style="text-align: right;">
                        <strong>Total For {{ $reportValue->route_number }} - {{ $reportValue->route_name }}:</strong>
                    </td>
                    <td><strong>{{ $reportValue->trip_planned }}</strong></td>
                    <td><strong>{{ $reportValue->km_planned }}</strong></td>
                    <td><strong>{{ $reportValue->trip_served }}</strong></td>
                    <td><strong>{{ $reportValue->km_served }}</strong></td>
                    <td><strong>{{ $reportValue->missed_trip }}</strong></td>
                    <td><strong>{{ $reportValue->trip_compliance }}</strong></td>
                    <td><strong>{{ $reportValue->earlyLate }}</strong></td>
                    <td><strong>0</strong></td>
                    <td><strong>0</strong></td>
                    <td><strong>{{ $reportValue->ridership }}</strong></td>
                    <td><strong>{{ $reportValue->ridership }}</strong></td>
                    <td><strong>{{ $reportValue->farebox}}</strong></td>
                </tr>
            @elseif(!property_exists($reportValue, 'route_number') && property_exists($reportValue, 'company_name'))
                @php $newCompany=true;@endphp
                <tr>
                    <td colspan="3" style="text-align: right;">
                        <strong>Total For {{ $reportValue->company_name }}:</strong>
                    </td>
                    <td><strong>{{ $reportValue->trip_planned }}</strong></td>
                    <td><strong>{{ $reportValue->km_planned }}</strong></td>
                    <td><strong>{{ $reportValue->trip_served }}</strong></td>
                    <td><strong>{{ $reportValue->km_served }}</strong></td>
                    <td><strong>{{ $reportValue->missed_trip }}</strong></td>
                    <td><strong>{{ $reportValue->trip_compliance }}</strong></td>
                    <td><strong>{{ $reportValue->earlyLate }}</strong></td>
                    <td><strong>0</strong></td>
                    <td><strong>0</strong></td>
                    <td><strong>{{ $reportValue->ridership }}</strong></td>
                    <td><strong>{{ $reportValue->ridership }}</strong></td>
                    <td><strong>{{ $reportValue->farebox}}</strong></td>
                </tr>
            @else
                @if($newCompany==true)
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
                    @php $newCompany=false;@endphp
                @endif
                <tr>
                    <td style="text-align: center;">{{ $count++ }}</td>
                    <td style="text-align: center;">{{ $reportValue->service_date }}</td>
                    <td style="text-align: center;">{{ $reportValue->day }}</td>
                    <td>{{ $reportValue->trip_planned }}</td>
                    <td>{{ $reportValue->km_planned }}</td>
                    <td>{{ $reportValue->trip_served }}</td>
                    <td>{{ $reportValue->km_served }}</td>
                    <td>{{ $reportValue->missed_trip }}</td>
                    <td>{{ $reportValue->trip_compliance }}</td>
                    <td>{{ $reportValue->earlyLate }}</td>
                    <td>0</td>
                    <td>0</td>
                    <td>{{ $reportValue->ridership }}</td>
                    <td>{{ $reportValue->ridership }}</td>
                    <td>{{ $reportValue->farebox}}</td>
                </tr>
            @endif
        @endforeach
    </tbody>

    {{-- <tbody>
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
    </tbody> --}}
</table>
