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
            <strong>Company: </strong>
        </th>
    </tr>
    <tr>
        <th colspan="11">
            <strong> Bus No: {{ $busNo->bus_registration_number }}</strong>
        </th>
    </tr>
    </thead>

    <tbody>
    @foreach($stages as $stage)
        <tr>
            <td colspan="11">
                <strong>RHPN:  Status:  Creation By: </strong>
            </td>
        </tr>
        <tr>
            <td colspan="11">
                <strong>Closed By: Closed At: </strong>
            </td>
        </tr>
        <tr>
            <td colspan="11">
                <strong>Route Description: </strong>
            </td>
        </tr>
        <tr>
            <td colspan="11">
                <strong>System Trip Details: </strong>
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

        @foreach($stages as $stage)
            <tr>
                <td style="text-align: center;">{{ $stage->stage_order }}</td>
                <td style="text-align: center;">{{ $stage->stage_order }}</td>
                <td style="text-align: center;">{{ $stage->stage_order }}</td>
                <td style="text-align: center;">{{ $stage->stage_order }}</td>
                <td style="text-align: center;">{{ $stage->stage_order }}</td>
                <td style="text-align: center;">{{ $stage->stage_order }}</td>
                <td style="text-align: center;">{{ $stage->stage_order }}</td>
                <td style="text-align: center;">{{ $stage->stage_order }}</td>
                <td style="text-align: center;">{{ $stage->stage_order }}</td>
                <td style="text-align: center;">{{ $stage->stage_order }}</td>
                <td style="text-align: center;">{{ $stage->stage_order }}</td>
            </tr>
        @endforeach
        <tr>
            <td colspan="6" style="text-align: right;">
                <strong>Total Sales For RHPN: </strong>
            </td>
            <td><strong>Total Cash</strong></td>
            <td><strong>Total Card</strong></td>
            <td><strong>Total Touch N Go</strong></td>
            <td><strong>Total Cancelled</strong></td>
            <td><strong>Total</strong></td>
        </tr>
        <tr>
            <td colspan="11">&nbsp;</td>
        </tr>
    @endforeach
    </tbody>
</table>
