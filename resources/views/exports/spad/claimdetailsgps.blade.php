<table style="border-color: #000000; border-style: solid;">
    <thead>
    <tr>
        <th colspan="9" style="vertical-align: middle; text-align: center;">
            <strong>Claim Details GPS Report</strong>
        </th>
    </tr>
    <tr>
        <th colspan="9">&nbsp;</th>
    </tr>
    <tr>
        <th colspan="9">
            <strong>Network Operator: MARALINER</strong>
        </th>
    </tr>
    <tr>
        <th colspan="9">
            <strong>Network Area: {{ $networkArea }}</strong>
        </th>
    </tr>
    <tr>
        <th colspan="9">
            <strong> Reporting Period: {{ $dateFrom }} - {{ $dateTo }} </strong>
        </th>
    </tr>
    <tr>
        <th colspan="9">
            <strong>Date Printed: {{ Carbon\Carbon::now() }}</strong>
        </th>
    </tr>
    </thead>

    <tbody>
    @foreach($reports as $key1 => $allCompany)
        @foreach($allCompany as $key2 => $allRoutes)
            @foreach($allRoutes as $key3 => $allDates)
                @foreach($allDates as $key4 => $allTrips)
                    @if($allTrips!=NULL)

                        <tr>
                            <td colspan="9">
                                &nbsp;
                            </td>
                        </tr>
                        <tr>
                            <td colspan="9">
                                <strong>Company Name: {{$key2}}</strong>
                            </td>
                        </tr>
                        @foreach($allTrips as $key6 => $allGPS)
                            <tr>
                                <td colspan="9">
                                    &nbsp;
                                </td>
                            </tr>
                            <tr>
                                <td colspan="9">
                                    <strong>Trip Details: {{$key6}}</strong>
                                </td>
                            </tr>
                            <tr>
                                <td style="text-align: center;"><strong>No.</strong></td>
                                <td style="text-align: center;"><strong>Bus Registration No</strong></td>
                                <td style="text-align: center;"><strong>Creation Date</strong></td>
                                <td style="text-align: center;"><strong>Speed (KM)</strong></td>
                                <td style="text-align: center;"><strong>Latitude</strong></td>
                                <td style="text-align: center;"><strong>Longitude</strong></td>
                                <td style="text-align: center;"><strong>PHMS Status</strong></td>
                                <td style="text-align: center;"><strong>PHMS Upload Date</strong></td>
                                <td style="text-align: center;"><strong>Duration (Minute)</strong></td>
                            </tr>
                            @if($allGPS!=NULL)
                                @foreach($allGPS as $key7 => $perGPS)
                                    <tr>
                                        <td style="text-align: center;">{{$key7}}</td>
                                        @foreach($perGPS as $key8 => $gpsData)
                                            <td style="text-align: center;">{{$gpsData}}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="9">
                                        <strong>No Records Found...</strong>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            @endforeach
        @endforeach
    @endforeach
    </tbody>
</table>
