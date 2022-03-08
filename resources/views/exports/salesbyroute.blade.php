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
        @foreach($stages as $stage)
            <tr>
                <td style="text-align: center;">{{ $stage->stage_order }}</td> //No
                <td style="text-align: center;">{{ $stage->route_route_name }}</td> //From-to

                @foreach($stages as $stage)
                    <td style="text-align: center;">{{ $stage->stage_name }}</td> //Qty
                    <td style="text-align: center;">{{ $stage->no_of_km}}</td> //Sales
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>
