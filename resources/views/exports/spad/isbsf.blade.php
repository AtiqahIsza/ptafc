<table style="border-color: #000000; border-style: solid;">
    <thead>
    <tr>
        <th colspan="{{$colspan}}" style="vertical-align: middle; text-align: center;">
            <strong>ISBSF Report</strong>
        </th>
    </tr>
    <tr>
        <td colspan="{{$colspan}}">&nbsp;</td>
    </tr>
    <tr>
        <th colspan="{{$colspan}}">
            <strong>Network Operator: </strong>
        </th>
    </tr>
    <tr>
        <th colspan="{{$colspan}}">
            <strong>Network Area: </strong>
        </th>
    </tr>
    <tr>
        <th colspan="{{$colspan}}">
            <strong> Reporting Period: {{ $dateFrom }} - {{ $dateTo }} </strong>
        </th>
    </tr>
    <tr>
        <th colspan="{{$colspan}}">
            <strong>Date Printed: {{ Carbon\Carbon::now() }}</strong>
        </th>
    </tr>
    <tr>
        <td colspan="{{$colspan}}">&nbsp;</td>
    </tr>
    </thead>

    <tbody>
    <tr>
        <td rowspan="2" style="text-align: center;"><strong>No</strong></td>
        <td rowspan="2" style="text-align: center;"><strong>Route</strong></td>
        <td colspan="{{$allDates->count()}}" style="text-align: center;"><strong>Day {{ $dateFrom->format('M Y') }}</strong></td>
        <td rowspan="2" style="text-align: center;"><strong>Total</strong></td>
    </tr>
    <tr>
        @foreach($allDates as $allDate)
            <td style="text-align: center;"><strong>{{$allDate->day}}</strong></td>
        @endforeach
    </tr>

    @php $i=1 @endphp
    @foreach($contents as $key1 => $value)
        <tr>
            <td style="text-align: center;">{{ $i++ }}</td>
            <td colspan="{{$colspan - 1}}" style="text-align: center;">{{ $key1['route_name'] }}</td>

            @foreach($key1['content'] as $key2 => $content)
                @foreach($key2['planned_trip'] as $key3 => $data)
                    <td style="text-align: center;">{{ $key3 }}</td>
                @endforeach
            @endforeach
        </tr>
    @endforeach
    </tbody>
</table>
