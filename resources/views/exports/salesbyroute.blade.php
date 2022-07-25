<table style="border-color: #000000; border-style: solid;">
    <thead>
    <tr>
        <th colspan="{{$colspan}}" style="vertical-align: middle; text-align: center;">
            <strong>Sales Report By Route</strong>
        </th>
    </tr>
    <tr>
        <td colspan="{{$colspan}}">&nbsp;</td>
    </tr>
    <tr>
        <th colspan="{{$colspan}}">
            <strong>Network Area: {{ $networkArea }}</strong>
        </th>
    </tr>
    <tr>
        <th colspan="{{$colspan}}">
            <strong>Route: {{ $routeName }}</strong>
        </th>
    </tr>
    <tr>
        <th colspan="{{$colspan}}">
            <strong>Reporting Period: {{ $dateFrom }} - {{ $dateTo }} </strong>
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
        <td rowspan="3">No</td>
        <td rowspan="3">From-To</td>

        @foreach($range as $ranges)
            <td colspan="4" style="vertical-align: middle; text-align: center;"><strong>{{$ranges}}</strong></td>
        @endforeach
        <td colspan="4" style="vertical-align: middle; text-align: center;"><strong>Total</strong></td>
    </tr>
    <tr>
        @foreach($range as $ranges)
            <td rowspan="2">Qty</td>
            <td colspan="3" style="vertical-align: middle; text-align: center;">Sales</td>
        @endforeach
        <td rowspan="2">Qty</td>
        <td colspan="3" style="vertical-align: middle; text-align: center;">Sales</td>
    </tr>
    <tr>
        @foreach($range as $ranges)
            <td>Cash</td>
            <td>Card</td>
            <td>Touch N Go</td>
        @endforeach
        <td>Cash</td>
        <td>Card</td>
        <td>Touch N Go</td>
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
                        <td style="text-align: center;"><strong>{{ $perDates['cash'] }}</strong></td>
                        <td style="text-align: center;"><strong>{{ $perDates['card'] }}</strong></td>
                        <td style="text-align: center;"><strong>{{ $perDates['tngo'] }}</strong></td>
                    @endforeach
                @else
                    <td style="text-align: center;">{{ $allDates['qty'] }}</td>
                    <td style="text-align: center;"><strong>{{ $perDates['cash'] }}</strong></td>
                    <td style="text-align: center;"><strong>{{ $perDates['card'] }}</strong></td>
                    <td style="text-align: center;"><strong>{{ $perDates['tngo'] }}</strong></td>
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
                    <td style="text-align: center;"><strong> {{ $allStage['grand_cash'] }} </strong></td>
                    <td style="text-align: center;"><strong> {{ $allStage['grand_card'] }} </strong></td>
                    <td style="text-align: center;"><strong> {{ $allStage['grand_tngo'] }} </strong></td>
                @else
                    @foreach($allStage as $key3 => $dates)
                        <td style="text-align: center;">{{ $dates['qty']}}</td>
                        <td style="text-align: center;"><strong>{{ $dates['cash'] }}</strong></td>
                        <td style="text-align: center;"><strong>{{ $dates['card'] }}</strong></td>
                        <td style="text-align: center;"><strong>{{ $dates['tngo'] }}</strong></td>
                    @endforeach
                @endif
            @endforeach
        @endforeach
    </tr>
    </tbody>
</table>
