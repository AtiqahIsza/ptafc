<table style="border-color: #000000; border-style: solid;">
    <thead>
    <tr>
        <th colspan="22" style="vertical-align: middle; text-align: center;">
            <strong>{{$routeNo}} Top Boarding Report</strong>
        </th>
    </tr>
    <tr>
        <td colspan="22">&nbsp;</td>
    </tr>
    <tr>
        <th colspan="22">
            <strong>Network Operator: </strong>
        </th>
    </tr>
    <tr>
        <th colspan="22">
            <strong>Network Area: </strong>
        </th>
    </tr>
    <tr>
        <th colspan="22">
            <strong> Reporting Period: {{ $dateFrom }} - {{ $dateTo }} </strong>
        </th>
    </tr>
    <tr>
        <th colspan="22">
            <strong>Date Printed: {{ Carbon\Carbon::now() }}</strong>
        </th>
    </tr>
    <tr>
        <td colspan="22">&nbsp;</td>
    </tr>
    </thead>

    <tbody>
        <tr>
            <td rowspan="2" style="text-align: center;"><strong>Route No.</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Bus Stop ID</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Bus Stop ID Public</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Bus Stop Description</strong></td>
            <td colspan="10" style="text-align: center;"><strong>ETM Boarding Passenger Count (Inbound)</strong></td>
            <td colspan="10" style="text-align: center;"><strong>ETM Boarding Passenger Count (Outbound)</strong></td>
        </tr>
        <tr>
            <td><strong>Total On</strong></td>
            <td><strong>Monthly Pass</strong></td>
            <td><strong>Adult</strong></td>
            <td><strong>Child</strong></td>
            <td><strong>Senior</strong></td>
            <td><strong>Student</strong></td>
            <td><strong>OKU</strong></td>
            <td><strong>JKM</strong></td>
            <td><strong>MAIM</strong></td>
            <td><strong>Total On</strong></td>
            <td><strong>Monthly Pass</strong></td>
            <td><strong>Adult</strong></td>
            <td><strong>Child</strong></td>
            <td><strong>Senior</strong></td>
            <td><strong>Student</strong></td>
            <td><strong>OKU</strong></td>
            <td><strong>JKM</strong></td>
            <td><strong>MAIM</strong></td>
        </tr>

        @foreach($contents as $key1 => $value)
            @foreach($value[$routeNo] as $key2 => $data)
                @foreach($data as $key3 => $content)
                    <tr>
                        <td style="text-align: center;">{{ $routeNo }}</td>
                        <td style="text-align: center;">{{ $key3 }}</td>
                        <td style="text-align: center;">{{ $content['bus_stop_id'] }}</td>
                        <td style="text-align: center;">{{ $content['bus_stop_id_public'] }}</td>
                        <td style="text-align: center;">{{ $content['bus_stop_desc'] }}</td>
                        <td style="text-align: center;">{{ $content['inbound_total_on'] }}</td>
                        <td style="text-align: center;">{{ $content['inbound_monthly_pass'] }}</td>
                        <td style="text-align: center;">{{ $content['inbound_adult'] }}</td>
                        <td style="text-align: center;">{{ $content['inbound_child'] }}</td>
                        <td style="text-align: center;">{{ $content['inbound_senior'] }}</td>
                        <td style="text-align: center;">{{ $content['inbound_student'] }}</td>
                        <td style="text-align: center;">{{ $content['inbound_oku'] }}</td>
                        <td style="text-align: center;">{{ $content['inbound_jkm'] }}</td>
                        <td style="text-align: center;">{{ $content['inbound_main'] }}</td>
                        <td style="text-align: center;">{{ $content['outbound_total_on'] }}</td>
                        <td style="text-align: center;">{{ $content['outbound_monthly_pass'] }}</td>
                        <td style="text-align: center;">{{ $content['outbound_adult']  }}</td>
                        <td style="text-align: center;">{{ $content['outbound_child'] }}</td>
                        <td style="text-align: center;">{{ $content['outbound_senior'] }}</td>
                        <td style="text-align: center;">{{ $content['outbound_student'] }}</td>
                        <td style="text-align: center;">{{ $content['outbound_oku'] }}</td>
                        <td style="text-align: center;">{{ $content['outbound_jkm'] }}</td>
                        <td style="text-align: center;">{{ $content['outbound_main'] }}</td>
                    </tr>
                @endforeach
                @foreach($data['final'] as $key3 => $content)
                    <tr>
                        <td colspan="4" style="text-align: right;">
                            <strong>Total For Route No: {{$routeNo}}</strong>
                        </td>
                        <td style="text-align: center;"><strong>{{ $content['final_inbound_total_on'] }}</td>
                        <td style="text-align: center;"><strong>{{ $content['final_inbound_monthly_pass'] }}</td>
                        <td style="text-align: center;"><strong>{{ $content['final_inbound_adult'] }}</td>
                        <td style="text-align: center;"><strong>{{ $content['final_inbound_child'] }}</td>
                        <td style="text-align: center;"><strong>{{ $content['final_inbound_senior'] }}</td>
                        <td style="text-align: center;"><strong>{{ $content['final_inbound_student'] }}</td>
                        <td style="text-align: center;"><strong>{{ $content['final_inbound_oku'] }}</td>
                        <td style="text-align: center;"><strong>{{ $content['final_inbound_jkm'] }}</td>
                        <td style="text-align: center;"><strong>{{ $content['final_inbound_main'] }}</td>
                        <td style="text-align: center;"><strong>{{ $content['final_outbound_total_on'] }}</td>
                        <td style="text-align: center;"><strong>{{ $content['final_outbound_monthly_pass'] }}</td>
                        <td style="text-align: center;"><strong>{{ $content['final_outbound_adult']  }}</td>
                        <td style="text-align: center;"><strong>{{ $content['final_outbound_child'] }}</td>
                        <td style="text-align: center;"><strong>{{ $content['final_outbound_senior'] }}</td>
                        <td style="text-align: center;"><strong>{{ $content['final_outbound_student'] }}</td>
                        <td style="text-align: center;"><strong>{{ $content['final_outbound_oku'] }}</td>
                        <td style="text-align: center;"><strong>{{ $content['final_outbound_jkm'] }}</td>
                        <td style="text-align: center;"><strong>{{ $content['final_outbound_main'] }}</td>
                    </tr>
                    <tr>
                        <td colspan="4" style="text-align: right;">
                            <strong>Grand Total:</strong>
                        </td>
                        <td style="text-align: center;"><strong>{{ $content['final_inbound_total_on'] }}</strong></td>
                        <td style="text-align: center;"><strong>{{ $content['final_inbound_monthly_pass'] }}</strong></td>
                        <td style="text-align: center;"><strong>{{ $content['final_inbound_adult'] }}</strong></td>
                        <td style="text-align: center;"><strong>{{ $content['final_inbound_child'] }}</strong></td>
                        <td style="text-align: center;"><strong>{{ $content['final_inbound_senior'] }}</strong></td>
                        <td style="text-align: center;"><strong>{{ $content['final_inbound_student'] }}</strong></td>
                        <td style="text-align: center;"><strong>{{ $content['final_inbound_oku'] }}</strong></td>
                        <td style="text-align: center;"><strong>{{ $content['final_inbound_jkm'] }}</strong></td>
                        <td style="text-align: center;"><strong>{{ $content['final_inbound_main'] }}</strong></td>
                        <td style="text-align: center;"><strong>{{ $content['final_outbound_total_on'] }}</strong></td>
                        <td style="text-align: center;"><strong>{{ $content['final_outbound_monthly_pass'] }}</strong></td>
                        <td style="text-align: center;"><strong>{{ $content['final_outbound_adult']  }}</strong></td>
                        <td style="text-align: center;"><strong>{{ $content['final_outbound_child'] }}</strong></td>
                        <td style="text-align: center;"><strong>{{ $content['final_outbound_senior'] }}</strong></td>
                        <td style="text-align: center;"><strong>{{ $content['final_outbound_student'] }}</strong></td>
                        <td style="text-align: center;"><strong>{{ $content['final_outbound_oku'] }}</strong></td>
                        <td style="text-align: center;"><strong>{{ $content['final_outbound_jkm'] }}</strong></td>
                        <td style="text-align: center;"><strong>{{ $content['final_outbound_main'] }}</strong></td>
                    </tr>
                @endforeach
            @endforeach
        @endforeach
    </tbody>
</table>
