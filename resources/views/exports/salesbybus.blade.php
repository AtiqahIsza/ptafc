<table style="border-color: #000000; border-style: solid;">
    <thead>
    <tr>
        <th rowspan="2" colspan="11" style="vertical-align: middle; text-align: center;">
            <strong>Sales Details Report By Bus</strong>
        </th>
    </tr>
    <tr>
        <th colspan="11">
            &nbsp;
        </th>
    </tr>
    <tr>
        <th colspan="11">
            <strong> Network Area: {{ $company }}</strong>
        </th>
    </tr>
    <tr>
        <th colspan="11">
            <strong> Reporting Period: {{ $dateFrom }} - {{ $dateTo }} </strong>
        </th>
    </tr>
    <tr>
        <th colspan="11">
            <strong>Date Printed: {{ Carbon\Carbon::now() }}</strong>
        </th>
    </tr>
    </thead>
    <tbody>
    @foreach($reports as $key1 => $reportValue)
        @foreach($reportValue as $key2 => $allBusesArr)
            @if($key2=="grand_sales")
                <tr>
                    <td colspan="6" style="text-align: right;">
                        <strong>Grand Total Sales</strong>
                    </td>
                    @foreach($allBusesArr as $key11 => $grand)
                        <td><strong>{{ $grand }}</strong></td>
                    @endforeach
                </tr>
            @else
                @foreach($allBusesArr as $key3 => $perBus)
                    @foreach($perBus as $key4 => $allDates)
                        @if($key4=='total_sales_per_bus' && $allDates != NULL)
                            <tr>
                                <td colspan="6" style="text-align: right;">
                                    <strong>Total Sales For Bus No: {{$key3}}</strong>
                                </td>
                                @foreach($allDates as $key5 => $totalBus)
                                    <td><strong>{{ $totalBus }}</strong></td>
                                @endforeach
                            </tr>
                        @elseif($key4==0 && $allDates != NULL)
                            <tr>
                                <td colspan="11">&nbsp;</td>
                            </tr>
                            <tr>
                                <th colspan="11">
                                    <strong> Bus No: {{ $key3 }} TID: {{ $allDates }}</strong>
                                </th>
                            </tr>
                        @elseif($allDates != NULL)
                            @foreach($allDates as $key5 => $allTrips)
                                @foreach($allTrips as $key6 => $perTrip)
                                    @if($key6=='total_sales_per_trip')
                                        <tr>
                                            <td colspan="6" style="text-align: right;">
                                                <strong>Total Sales For Trip No: {{$tripNo}}</strong>
                                            </td>
                                            @foreach($perTrip as $key9 => $total)
                                                <td><strong>{{ $total }}</strong></td>
                                            @endforeach
                                        </tr>
                                    @elseif($key6=='all_tickets')
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
                                            <td style="text-align: center;"><strong>Total Farebox</strong></td>
                                        </tr>
                                        @php $i = 1; @endphp
                                        @if($perTrip!=NULL)
                                            @foreach($perTrip as $key8 => $perTicket)
                                                <tr>
                                                    <td style="text-align: center;">{{ $i++ }}</td>
                                                    @foreach($perTicket as $key10 => $ticketData)
                                                        <td style="text-align: center;">{{ $ticketData }}</td>
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td style="text-align: center;">{{ $i++ }}</td>
                                                <td colspan="10" style="text-align: center;"><strong>****NO SALES</strong></td>
                                            </tr>
                                        @endif
                                    @else
                                        @php $tripNo = $key6 @endphp
                                        <tr>
                                            <td colspan="11">&nbsp;</td>
                                        </tr>
                                        <tr>
                                            <td colspan="11">
                                                <strong>Trip Number: {{$perTrip['trip_number']}} Creation By: {{$perTrip['creation_by']}}</strong>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="11">
                                                <strong>Closed By: {{$perTrip['closed_by']}} Closed At: {{$perTrip['closed_at']}}</strong>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="11">
                                                <strong>Route Description: {{$perTrip['route_desc']}} Trip Type: {{$perTrip['trip_type']}}</strong>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="11">
                                                <strong>System Trip Details: {{$perTrip['trip_details']}}</strong>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            @endforeach
                        @endif
                    @endforeach
                @endforeach
            @endif
        @endforeach
    @endforeach
    </tbody>
</table>
