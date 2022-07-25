<table style="border-color: #000000; border-style: solid;">
    <thead>
    <tr>
        <th colspan="{{ $colspan }}" style="vertical-align: middle; text-align: center;">
            <strong>Average Summary Report</strong>
        </th>
    </tr>
    <tr>
        <td colspan="{{ $colspan }}">&nbsp;</td>
    </tr>
    <tr>
        <th colspan="{{ $colspan }}">
            <strong>Network Area: {{ $networkArea }}</strong>
        </th>
    </tr>
    <tr>
        <th colspan="{{ $colspan }}">
            <strong> Reporting Period: {{ $dateFrom}} - {{ $dateTo }}</strong>
        </th>
    </tr>
    <tr>
        <th colspan="{{ $colspan }}">
            <strong>Date Printed: {{ Carbon\Carbon::now() }}</strong>
        </th>
    </tr>
    </thead>

    <tbody>
        <tr>
            <td colspan="{{ $colspan }}">&nbsp;</td>
        </tr>
        <tr>
            <td style="text-align: center;">&nbsp;</td>
            <td style="text-align: center;">&nbsp;</td>
            <td style="text-align: center;">&nbsp;</td>
            @foreach ($routes as $route)
                <td style="text-align: center;" colspan="2"><strong>{{ $route->route_number }}</strong></td>
                <td style="text-align: center;">&nbsp;</td>
            @endforeach
            <td style="text-align: center;">&nbsp;</td>
        </tr>
        <tr>
            <td style="text-align: center;"><strong>No.</strong></td>
            <td style="text-align: center;"><strong>Date</strong></td>
            <td style="text-align: center;"><strong>Day/Category</strong></td>
            @foreach ($routes as $route)
                <td style="text-align: center;"><strong>Total Trip Planned</strong></td>
                <td style="text-align: center;"><strong>Total Trip Served</strong></td>
                <td style="text-align: center;">&nbsp;</td>
            @endforeach
            <td style="text-align: center;"><strong>AVERAGE (%)</strong></td>
        </tr>
        @foreach($reports as $key1 => $reportValue)
            @php $count=1; @endphp
            @foreach($reportValue as $key2 => $perDate)
                @if($key2=="data")
                    @foreach($perDate as $key3 => $allData)
                        <tr>
                            <td style="text-align: center;">{{ $count++ }}</td>
                            <td style="text-align: center;">{{ $key3 }}</td>
                            @foreach($allData as $key4 => $dateData)
                                @if($key4!="week")
                                    <td style="text-align: right;">{{ $dateData }}</td>
                                @else
                                    <td style="text-align: center;">{{ $dateData }}</td>
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                @else   
                    <tr>
                        <td colspan="3">&nbsp;</td>
                        @foreach($perDate as $key5 => $allData)
                            <td style="text-align: right;"><strong>{{ $allData }}</strong></td>
                        @endforeach
                        <td>&nbsp;</td>
                    </tr> 
                @endif
            @endforeach
        @endforeach
    </tbody>
</table>
