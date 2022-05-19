<table style="border-color: #000000; border-style: solid;">
    <thead>
    <tr>
        <th colspan="{{$colspan}}" style="vertical-align: middle; text-align: center;">
            <strong>Sale Report By Route</strong>
        </th>
    </tr>
    <tr>
        <td colspan="{{$colspan}}">&nbsp;</td>
    </tr>
    </thead>

    <tbody>
    <tr>
        <td rowspan="2">No</td>
        <td rowspan="2">From-To</td>

        @foreach($range as $ranges)
            <td colspan="2" style="vertical-align: middle; text-align: center;"><strong>{{$ranges}}</strong></td>
        @endforeach
        <td colspan="2" style="vertical-align: middle; text-align: center;"><strong>Total</strong></td>
    </tr>
    <tr>
        @foreach($range as $ranges)
            <td>Qty</td>
            <td>Sales</td>
        @endforeach
        <td>Qty</td>
        <td>Sales</td>
    </tr>

    @php $i=1 @endphp
    @foreach($reports as $key1 => $reportValue)
        @foreach($reportValue as $key2 => $perStage)
            <tr>
                <td>{{ $i++ }}</td>
                <td>{{ $key2 }}</td>
            @foreach($perStage as $key3 => $allDates)
                @if($key3=='all_date')
                    @foreach($allDates as $key4 => $perDates)
                        <td style="text-align: center;">{{ $perDates['qty'] }}</td>
                        <td style="text-align: center;"><strong>{{ $perDates['sales'] }}</strong></td>
                    @endforeach
                @else
                    <td style="text-align: center;">{{ $allDates['qty'] }}</td>
                    <td style="text-align: center;"><strong>{{ $allDates['sales'] }}</strong></td>
                @endif
            @endforeach
            </tr>
        @endforeach
    @endforeach
    <tr>
        <td colspan="2" style="text-align: right;"><strong>Grand Total:</strong></td>
        @foreach($grandTotal as $key1 => $value)
            @foreach($value as $key2 => $allStage)
                @if($key2=='grand_sales_by_route')
                    <td style="text-align: center;">{{ $allStage['grand_qty'] }}</td>
                    <td style="text-align: center;"><strong> {{ $allStage['grand_sales'] }} </strong></td>
                @else
                    @foreach($allStage as $key3 => $dates)
                        <td style="text-align: center;">{{ $dates['qty']}}</td>
                        <td style="text-align: center;"><strong>{{ $dates['sales'] }}</strong></td>
                    @endforeach
                @endif
            @endforeach
        @endforeach
    </tr>
    </tbody>
</table>
