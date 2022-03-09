<table style="border-color: #000000; border-style: solid;">
    <thead>
    <tr>
        <th rowspan="2" colspan="11" style="vertical-align: middle; text-align: center;">
            <strong>Sales Details Report By Bus</strong>
        </th>
    </tr>
    <tr>
        &nbsp;
    </tr>
    <tr>
        <td colspan="11">&nbsp;</td>
    </tr>
    <tr>
        <th colspan="11">
            <strong> Bus No: {{ $busNo->bus_registration_number }}</strong>
        </th>
    </tr>
    </thead>

    <tbody>
    @php $i = 1; @endphp
    @foreach($contents as $content)
        <tr>
            <td colspan="11">
                <strong>RHPN: {{$content->pda->imei}} Creation By: {{$content->trip->start_date}}</strong>
            </td>
        </tr>
        <tr>
            <td colspan="11">
                <strong>Closed By: {{$content->trip->end_date}} Closed At: </strong>
            </td>
        </tr>
        <tr>
            <td colspan="11">
                <strong>Route Description: {{$content->route->route_name}} </strong>
            </td>
        </tr>
        <tr>
            <td colspan="11">
                <strong>System Trip Details: ({{$content->trip->id}} -  {{$content->trip->start_date}} - {{$content->trip->end_date}})</strong>
            </td>
        </tr>
        <tr>
            <td style="text-align: center;"><strong>No</strong></td>
            <td style="text-align: center;"><strong>Sales Date</strong></td>
            <td style="text-align: center;"><strong>Ticket No</strong></td>
            <td style="text-align: center;"><strong>From</strong></td>
            <td style="text-align: center;"><strong>To</strong></td>
            <td style="text-align: center;"><strong>Type</strong></td>
            <td style="text-align: center;"><strong>Cash</strong></td>
            <td style="text-align: center;"><strong>Card</strong></td>
            <td style="text-align: center;"><strong>Touch N Go</strong></td>
            <td style="text-align: center;"><strong>Cancelled</strong></td>
            <td style="text-align: center;"><strong>By</strong></td>
        </tr>

        <tr>
            <td style="text-align: center;">{{ $i++ }}</td>
            <td style="text-align: center;">{{ $content->sales_date }}</td>
            <td style="text-align: center;">{{ $content->ticket_number }}</td>
            <td style="text-align: center;">{{ $content->fromstage_stage_id }}</td>
            <td style="text-align: center;">{{ $content->tostage_stage_id }}</td>
            <td style="text-align: center;">{{ $content->faretype }}</td>
            <td style="text-align: center;">{{ $stage->stage_order }}</td>
            <td style="text-align: center;">{{ $stage->stage_order }}</td>
            <td style="text-align: center;">{{ $stage->stage_order }}</td>
            <td style="text-align: center;">{{ $stage->stage_order }}</td>
            <td style="text-align: center;">{{ $stage->stage_order }}</td>
        </tr>

        <tr>
            <td colspan="6" style="text-align: right;">
                <strong>Total Sales For {{ $content->pda->imei }} </strong>
            </td>
            <td><strong>{{ $content->total_cash }}</strong></td>
            <td><strong>{{ $content->total_card}}</strong></td>
            <td><strong>{{ $content->total_touch_n_go }}</strong></td>
            <td><strong>{{ $content->total_cancelled }}</strong></td>
            <td><strong>{{ $content->total_by }}</strong></td>
        </tr>
        <tr>
            <td colspan="11">&nbsp;</td>
        </tr>
    @endforeach
    </tbody>
</table>
