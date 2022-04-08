<table style="border-color: #000000; border-style: solid;">
    <thead>
    <tr>
        <th colspan="{{$colspan}}" style="vertical-align: middle; text-align: center;">
            <strong>Sale Report By Route</strong>
        </th>
    </tr>
    <tr>
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
            <td colspan="2"><strong>{{$ranges}}</strong></td>
        @endforeach
        <td><strong>Total</strong></td>
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
    @foreach($contents as $key1 => $value)
        <tr>
            <td style="text-align: center;">{{ $i++ }}</td>
            <td style="text-align: center;">{{ $value['from_to'] }}</td>
            @foreach($value['perDate'] as $key2 => $perDate)
            {{--@foreach($range as $ranges)--}}
                {{--@foreach($perDate as $perDates)--}}
                <td style="text-align: center;">{{ $perDate['quantity'] }}</td>
                <td style="text-align: center;"><strong>{{ $perDate['sales'] }}</strong></td>
                {{--@endforeach--}}
            @endforeach
            <td style="text-align: center;">{{ $value['total_quantity'] }}</td>
            <td style="text-align: center;"><strong>{{ $value['total_sales'] }}</strong></td>
        </tr>
    @endforeach
    <tr>
        <td colspan="2">Grand Total:</td>
        @foreach($grandTotal as $key1 => $value)
            @foreach($value['perDate'] as $key2 => $grand)
                <td style="text-align: center;">{{ $grand['grand_quantity'] }}</td>
                <td style="text-align: center;"><strong> {{ $grand['grand_sales'] }} </strong></td>
            @endforeach
            <td style="text-align: center;">{{ $value['grand_total_quantity'] }}</td>
            <td style="text-align: center;"><strong>{{ $value['grand_total_sales'] }}</strong></td>
        @endforeach
    </tr>
    </tbody>
</table>
