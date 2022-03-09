<table style="border-color: #000000; border-style: solid;">
    <thead>
    <tr>
        <th rowspan="2" colspan="{{$colspan}}" style="vertical-align: middle; text-align: center;">
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
    @php $i=0 @endphp
    @foreach($contents as $content)
        <tr>
            <td style="text-align: center;">{{ $i++ }}</td> //No
            <td style="text-align: center;">{{ $content['from_to'] }}</td> //From-to
            @foreach($range as $ranges)
                <td style="text-align: center;">{{ $content['quantity'] }}</td> //Qty
                <td style="text-align: center;">{{ $content['sales'] }}</td> //Sales
            @endforeach
        </tr>
    @endforeach
    <tr>
        <td colspan="2">Grand Total:</td>
        @foreach($grandTotal as $grand)
            <td style="text-align: center;">{{ $grand['tot_quantity'] }}</td> //Tot_Qty
            <td style="text-align: center;">{{ $grand['tot_sales'] }}</td> //Tot_Sales
        @endforeach
    </tr>
    </tbody>
</table>
