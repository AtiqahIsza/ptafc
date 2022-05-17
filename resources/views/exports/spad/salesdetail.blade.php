<table style="border-color: #000000; border-style: solid;">
    <thead>
    <tr>
        <th colspan="18" style="vertical-align: middle; text-align: center;">
            <strong>Sales Details Report</strong>
        </th>
    </tr>
    <tr>
        <th colspan="18">&nbsp;</th>
    </tr>
    <tr>
        <th colspan="18">
            <strong>Network Operator: MARALINER</strong>
        </th>
    </tr>
    <tr>
        <th colspan="18">
            <strong>Network Area: {{ $networkArea }}</strong>
        </th>
    </tr>
    <tr>
        <th colspan="18">
            <strong> Reporting Period: {{ $dateFrom }} - {{ $dateTo }} </strong>
        </th>
    </tr>
    <tr>
        <th colspan="18">
            <strong>Date Printed: {{ Carbon\Carbon::now() }}</strong>
        </th>
    </tr>
    </thead>

    <tbody>
    <tr>
        <td colspan="18">&nbsp;</td>
    </tr>
    <tr>
        <td style="text-align: center;"><strong>No</strong></td>
        <td style="text-align: center;"><strong>Sales Date</strong></td>
        <td style="text-align: center;"><strong>Sales Time</strong></td>
        <td style="text-align: center;"><strong>Ticket No</strong></td>
        <td style="text-align: center;"><strong>From</strong></td>
        <td style="text-align: center;"><strong>To</strong></td>
        <td style="text-align: center;"><strong>Type</strong></td>
        <td style="text-align: center;"><strong>Price</strong></td>
        <td style="text-align: center;"><strong>Bus Reg No.</strong></td>
        <td style="text-align: center;"><strong>Route No.</strong></td>
        <td style="text-align: center;"><strong>IB/OB</strong></td>
        <td style="text-align: center;"><strong>Route Destination</strong></td>
        <td style="text-align: center;"><strong>System Trip Details</strong></td>
        <td style="text-align: center;"><strong>Trip Time</strong></td>
        <td style="text-align: center;"><strong>Trip No.</strong></td>
        <td style="text-align: center;"><strong>Driver ID</strong></td>
        <td style="text-align: center;"><strong>Driver Name</strong></td>
        <td style="text-align: center;"><strong>Payment Method</strong></td>
    </tr>
    @foreach($reports as $key1 => $reportValue)
        @foreach($reportValue as $key2 => $allTickets)
            <tr>
                <td>{{$key2}}</td>
                @foreach($allTickets as $key3 => $data)
                    <td>{{$data}}</td>
                @endforeach
            </tr>
        @endforeach
    @endforeach
    </tbody>
</table>
