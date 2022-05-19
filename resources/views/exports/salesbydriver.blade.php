<table style="border-color: #000000; border-style: solid;">
    <thead>
    <tr>
        <th rowspan="2" colspan="28" style="vertical-align: middle; text-align: center;">
            <strong>Sales Details Report By Driver</strong>
        </th>
    </tr>
    <tr>
        <th colspan="11">
            &nbsp;
        </th>
    </tr>
    </thead>
    <tbody>
    @foreach($reports as $key1 => $reportValue)
        @foreach($reportValue as $key2 => $allCompanies)
            @if($key2=="grand_total")
                <tr>
                    <td colspan="10" style="text-align: right;">
                        <strong>Grand Total Sales</strong>
                    </td>
                    @foreach($allCompanies as $key3 => $grand)
                        <td><strong>{{ $grand }}</strong></td>
                    @endforeach
                </tr>
            @else
                @foreach($allCompanies as $key4 => $perCompany)
                    @foreach($perCompany as $key5 => $allDriver)
                        @if($key5=="total")
                            @if($allDriver != NULL)
                                <tr>
                                    <td colspan="10" style="text-align: right;">
                                        <strong>Total Sales For Company: {{$key4}}</strong>
                                    </td>
                                    @foreach($allDriver as $key6 => $totalCompany)
                                        <td><strong>{{ $totalCompany }}</strong></td>
                                    @endforeach
                                </tr>
                            @endif
                        @else
                            @if($allDriver != NULL)
                                @foreach($allDriver as $key7 => $allTrips)
                                    <tr>
                                        <td colspan="28">
                                            &nbsp;
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="10"><strong>Driver: {{ $key7 }}</strong></td>
                                        <td colspan="6" style="text-align: center;"><strong>Cash/Card</strong></td>
                                        <td colspan="6" style="text-align: center;"><strong>Touch N Go</strong></td>
                                        <td colspan="6" style="text-align: center;"><strong>Total</strong></td>
                                    </tr>
                                    <tr>
                                        <td rowspan="2" style="text-align: center;"><strong>No</strong></td>
                                        <td rowspan="2" style="text-align: center;"><strong>Company Name</strong></td>
                                        <td rowspan="2" style="text-align: center;"><strong>Bus No</strong></td>
                                        <td rowspan="2" style="text-align: center;"><strong>Creation Date</strong></td>
                                        <td rowspan="2" style="text-align: center;"><strong>Closed By</strong></td>
                                        <td rowspan="2" style="text-align: center;"><strong>Closed Date</strong></td>
                                        <td rowspan="2" style="text-align: center;"><strong>Route Description</strong></td>
                                        <td rowspan="2" style="text-align: center;"><strong>Trip Number</strong></td>
                                        <td rowspan="2" style="text-align: center;"><strong>Trip ID</strong></td>
                                        <td rowspan="2" style="text-align: center;"><strong>Status</strong></td>
                                        <td colspan="2" style="text-align: center;"><strong>Adult</strong></td>
                                        <td colspan="2" style="text-align: center;"><strong>Concession</strong></td>
                                        <td colspan="2" style="text-align: center;"><strong>Total</strong></td>
                                        <td colspan="2" style="text-align: center;"><strong>Adult</strong></td>
                                        <td colspan="2" style="text-align: center;"><strong>Concession</strong></td>
                                        <td colspan="2" style="text-align: center;"><strong>Total</strong></td>
                                        <td colspan="2" style="text-align: center;"><strong>Adult</strong></td>
                                        <td colspan="2" style="text-align: center;"><strong>Concession</strong></td>
                                        <td colspan="2" style="text-align: center;"><strong>Total</strong></td>
                                    </tr>
                                    <tr>
                                        <td style="text-align: center;"><strong>Quantity</strong></td>
                                        <td style="text-align: center;"><strong>Amount</strong></td>
                                        <td style="text-align: center;"><strong>Quantity</strong></td>
                                        <td style="text-align: center;"><strong>Amount</strong></td>
                                        <td style="text-align: center;"><strong>Quantity</strong></td>
                                        <td style="text-align: center;"><strong>Amount</strong></td>
                                        <td style="text-align: center;"><strong>Quantity</strong></td>
                                        <td style="text-align: center;"><strong>Amount</strong></td>
                                        <td style="text-align: center;"><strong>Quantity</strong></td>
                                        <td style="text-align: center;"><strong>Amount</strong></td>
                                        <td style="text-align: center;"><strong>Quantity</strong></td>
                                        <td style="text-align: center;"><strong>Amount</strong></td>
                                        <td style="text-align: center;"><strong>Quantity</strong></td>
                                        <td style="text-align: center;"><strong>Amount</strong></td>
                                        <td style="text-align: center;"><strong>Quantity</strong></td>
                                        <td style="text-align: center;"><strong>Amount</strong></td>
                                        <td style="text-align: center;"><strong>Quantity</strong></td>
                                        <td style="text-align: center;"><strong>Amount</strong></td>
                                    </tr>
                                    @if($allTrips != NULL)
                                        @foreach($allTrips as $key8 => $perTrips)
                                            @if($key8=="total")
                                                <tr>
                                                    <td colspan="10" style="text-align: right;">
                                                        <strong>Total Sales For Driver: {{$key7}}</strong>
                                                    </td>
                                                    @foreach($perTrips as $key9 => $totalDriver)
                                                        <td><strong>{{ $totalDriver }}</strong></td>
                                                    @endforeach
                                                </tr>
                                            @else
                                                @php $i=1;@endphp
                                                @foreach($perTrips as $key10 => $tripData)
                                                    <tr>
                                                        <td style="text-align: center;"> {{$i++}}</td>
                                                        @foreach($tripData as $key11 => $data)
                                                            <td style="text-align: center;">{{$data}}</td>
                                                        @endforeach
                                                    </tr>
                                                @endforeach
                                            @endif
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="28"> ****NO TRIP </td>
                                        </tr>
                                    @endif
                                @endforeach
                            @endif
                        @endif
                    @endforeach
                @endforeach
            @endif
        @endforeach
    @endforeach
    </tbody>
</table>
