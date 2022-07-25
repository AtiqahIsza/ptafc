<table style="border-color: #000000; border-style: solid;">
    <thead>
    <tr>
        <th colspan="34" style="vertical-align: middle; text-align: center;">
            <strong>Claim Details Report</strong>
        </th>
    </tr>
    <tr>
        <td colspan="34">&nbsp;</td>
    </tr>
    <tr>
        <th colspan="34">
            <strong>Network Operator: MARALINER</strong>
        </th>
    </tr>
    <tr>
        <th colspan="34">
            <strong>Network Area: {{  $networkArea }}</strong>
        </th>
    </tr>
    <tr>
        <th colspan="34">
            <strong> Reporting Period: {{ $dateFrom }} - {{ $dateTo }} </strong>
        </th>
    </tr>
    <tr>
        <th colspan="34">
            <strong>Date Printed: {{ Carbon\Carbon::now() }}</strong>
        </th>
    </tr>
    </thead>

    <tbody>
    @foreach($reports as $key => $reportValue)
        @if(array_key_exists("allRoute",$reportValue))
            @foreach($reportValue['allRoute'] as $key1 => $allRoute)
                @if(array_key_exists("data",$allRoute))
                    @foreach($allRoute['data'] as $key2 => $dataPerDate)
                        @if(array_key_exists('inbound_data',$dataPerDate))
                            @if(!empty($dataPerDate['inbound_data']))
                                <tr>
                                    <td colspan="34">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td rowspan="2" style="text-align: center;"><strong>Route No.</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>OD</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>IB/OB</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>Trip No.</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>Service Date</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>Start Point</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>RPH No</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>Bus Plate Number</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>Bus Age</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>Charge/KM</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>Driver Number</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>Bus Stop Travel</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>Travel (KM)</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>Total Claim</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>Travel (KM) GPS</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>Total Claim GPS</strong></td>
                                    <td colspan="5" style="text-align: center;"><strong>Verified Data</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>Start Point</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>Service Start Time</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>Actual Start Time</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>Sales Start Time</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>Service End Time</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>Actual End Time</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>Sales End Time</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>Punctuality</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>Passengers Boarding Count</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>Total Sales Amount (RM)</strong></td>
                                    <td colspan="3" style="text-align: center;"><strong>ETM Boarding Passenger Count</strong></td>
                                </tr>
                                <tr>
                                    <td style="text-align: center;"><strong>Status</strong></td>
                                    <td style="text-align: center;"><strong>Status of The Trip (Duplicate Trip, Outside Schedule, No GPS Tracking, Breakdown, Replacement)</strong></td>
                                    <td style="text-align: center;"><strong>KM as per BOP</strong></td>
                                    <td style="text-align: center;"><strong>Claim as per BOP (RM)</strong></td>
                                    <td style="text-align: center;"><strong>Missed Trip If No GPS Tracking</strong></td>
                                    <td style="text-align: center;"><strong>Total On</strong></td>
                                    <td style="text-align: center;"><strong>Adult</strong></td>
                                    <td style="text-align: center;"><strong>Concession</strong></td>
                                </tr>
                                @foreach($dataPerDate['inbound_data'] as $key4 => $tripInbound)
                                    @if($key4=='total_inbound')
                                        <tr>
                                            <td colspan="11" style="text-align: right;">
                                                <strong>Total ({{$key2}} - {{$key1}} . {{ $allRoute['route_name'] }})</strong>
                                            </td>
                                            <td><strong>{{$tripInbound['total_bus_stop_in']}}</strong></td>
                                            <td><strong>{{$tripInbound['total_travel_in']}}</strong></td>
                                            <td><strong>{{$tripInbound['total_claim_in']}}</strong></td>
                                            <td><strong>{{$tripInbound['total_travel_gps_in']}}</strong></td>
                                            <td><strong>{{$tripInbound['total_claim_gps_in']}}</strong></td>
                                            <td colspan="13">&nbsp;</td>
                                            <td><strong>{{$tripInbound['total_count_passenger_in']}}</strong></td>
                                            <td><strong>{{$tripInbound['total_sales_in']}}</strong></td>
                                            <td><strong>{{$tripInbound['total_total_in']}}</strong></td>
                                            <td><strong>{{$tripInbound['total_adult_in']}}</strong></td>
                                            <td><strong>{{$tripInbound['total_concession_in']}}</strong></td>
                                        </tr>
                                    @else
                                        @foreach($tripInbound as $key8 => $dataIn)
                                            <tr>
                                                <td style="text-align: center;">{{ $key1 }}</td>
                                                <td style="text-align: center;">{{ $key4 }}</td>
                                                <td style="text-align: center;">{{ $dataIn['trip_type'] }}</td>
                                                <td style="text-align: center;">{{ $dataIn['trip_no']  }}</td>
                                                <td style="text-align: center;">{{ $key2  }}</td>
                                                <td style="text-align: center;">{{ $dataIn['start_point'] }}</td>
                                                <td style="text-align: center;">{{ $dataIn['rph_no'] }}</td>
                                                <td style="text-align: center;">{{ $dataIn['bus_plate_no']  }}</td>
                                                <td style="text-align: center;">{{ $dataIn['bus_age'] }}</td>
                                                <td style="text-align: center;">{{ $dataIn['charge_km']}}</td>
                                                <td style="text-align: center;">{{ $dataIn['driver_id'] }}</td>
                                                <td>{{ $dataIn['bus_stop_travel'] }}</td>
                                                <td>{{ $dataIn['travel'] }}</td>
                                                <td>{{ $dataIn['claim'] }}</td>
                                                <td>{{ $dataIn['travel_gps'] }}</td>
                                                <td>{{ $dataIn['claim_gps'] }}</td>
                                                <td style="text-align: center;">{{ $dataIn['status'] }}</td>
                                                <td style="text-align: center;"></td>
                                                <td style="text-align: center;">{{ $dataIn['travel_BOP'] }}</td>
                                                <td style="text-align: center;">{{ $dataIn['claim_BOP'] }}</td>
                                                <td style="text-align: center;"></td>
                                                <td style="text-align: center;">{{ $dataIn['start_point_time'] }}</td>
                                                <td style="text-align: center;">{{ $dataIn['service_start'] }}</td>
                                                <td style="text-align: center;">{{ $dataIn['actual_start'] }}</td>
                                                <td style="text-align: center;">{{ $dataIn['sales_start'] }}</td>
                                                <td style="text-align: center;">{{ $dataIn['service_end'] }}</td>
                                                <td style="text-align: center;">{{ $dataIn['actual_end'] }}</td>
                                                <td style="text-align: center;">{{ $dataIn['sales_end'] }}</td>
                                                <td style="text-align: center;">{{ $dataIn['punctuality'] }}</td>
                                                <td>{{ $dataIn['pass_count'] }}</td>
                                                <td>{{ $dataIn['total_sales'] }}</td>
                                                <td>{{ $dataIn['total_on'] }}</td>
                                                <td>{{ $dataIn['adult'] }}</td>
                                                <td>{{ $dataIn['concession'] }}</td>
                                            </tr>
                                        @endforeach
                                    @endif
                                @endforeach
                            @endif
                        @endif

                        @if(array_key_exists('outbound_data',$dataPerDate))
                            @if(!empty(($dataPerDate['outbound_data'])))
                                <tr>
                                    <td colspan="34">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td rowspan="2" style="text-align: center;"><strong>Route No.</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>OD</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>IB/OB</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>Trip No.</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>Service Date</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>Start Point</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>RPH No</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>Bus Plate Number</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>Bus Age</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>Charge/KM</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>Driver Number</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>Bus Stop Travel</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>Travel (KM)</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>Total Claim</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>Travel (KM) GPS</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>Total Claim GPS</strong></td>
                                    <td colspan="5" style="text-align: center;"><strong>Verified Data</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>Start Point</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>Service Start Time</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>Actual Start Time</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>Sales Start Time</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>Service End Time</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>Actual End Time</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>Sales End Time</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>Punctuality</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>Passengers Boarding Count</strong></td>
                                    <td rowspan="2" style="text-align: center;"><strong>Total Sales Amount (RM)</strong></td>
                                    <td colspan="3" style="text-align: center;"><strong>ETM Boarding Passenger Count</strong></td>
                                </tr>
                                <tr>
                                    <td style="text-align: center;"><strong>Status</strong></td>
                                    <td style="text-align: center;"><strong>Status of The Trip (Duplicate Trip, Outside Schedule, No GPS Tracking, Breakdown, Replacement)</strong></td>
                                    <td style="text-align: center;"><strong>KM as per BOP = 38km</strong></td>
                                    <td style="text-align: center;"><strong>Claim as per BOP (RM)</strong></td>
                                    <td style="text-align: center;"><strong>Missed Trip If No GPS Tracking</strong></td>
                                    <td style="text-align: center;"><strong>Total On</strong></td>
                                    <td style="text-align: center;"><strong>Adult</strong></td>
                                    <td style="text-align: center;"><strong>Concession</strong></td>
                                </tr>

                                @foreach($dataPerDate['outbound_data'] as $key5 => $tripOutbound)
                                    @if($key5=='total_outbound')
                                        <tr>
                                            <td colspan="11" style="text-align: right;">
                                                <strong>Total ({{$key2}} - {{$key1}} . {{ $allRoute['route_name'] }})</strong>
                                            </td>
                                            <td><strong>{{$tripOutbound['total_bus_stop_out']}}</strong></td>
                                            <td><strong>{{$tripOutbound['total_travel_out']}}</strong></td>
                                            <td><strong>{{$tripOutbound['total_claim_out']}}</strong></td>
                                            <td><strong>{{$tripOutbound['total_travel_gps_out']}}</strong></td>
                                            <td><strong>{{$tripOutbound['total_claim_gps_out']}}</strong></td>
                                            <td colspan="13">&nbsp;</td>
                                            <td><strong>{{$tripOutbound['total_count_passenger_out']}}</strong></td>
                                            <td><strong>{{$tripOutbound['total_sales_out']}}</strong></td>
                                            <td><strong>{{$tripOutbound['total_total_out']}}</strong></td>
                                            <td><strong>{{$tripOutbound['total_adult_out']}}</strong></td>
                                            <td><strong>{{$tripOutbound['total_concession_out']}}</strong></td>
                                        </tr>
                                    @else
                                        @foreach($tripOutbound as $key9 => $dataOut)
                                            <tr>
                                                <td style="text-align: center;">{{ $key1 }}</td>
                                                <td style="text-align: center;">{{ $key5 }}</td>
                                                <td style="text-align: center;">{{ $dataOut['trip_type'] }}</td>
                                                <td style="text-align: center;">{{ $dataOut['trip_no']  }}</td>
                                                <td style="text-align: center;">{{ $key2 }}</td>
                                                <td style="text-align: center;">{{ $dataOut['start_point'] }}</td>
                                                <td style="text-align: center;">{{ $dataOut['rph_no'] }}</td>
                                                <td style="text-align: center;">{{ $dataOut['bus_plate_no']  }}</td>
                                                <td style="text-align: center;">{{ $dataOut['bus_age'] }}</td>
                                                <td style="text-align: center;">{{ $dataOut['charge_km']}}</td>
                                                <td style="text-align: center;">{{ $dataOut['driver_id'] }}</td>
                                                <td>{{ $dataOut['bus_stop_travel'] }}</td>
                                                <td>{{ $dataOut['travel'] }}</td>
                                                <td>{{ $dataOut['claim'] }}</td>
                                                <td>{{ $dataOut['travel_gps'] }}</td>
                                                <td>{{ $dataOut['claim_gps']  }}</td>
                                                <td style="text-align: center;">{{ $dataOut['status'] }}</td>
                                                <td style="text-align: center;"></td>
                                                <td style="text-align: center;">{{ $dataOut['travel_BOP'] }}</td>
                                                <td style="text-align: center;">{{ $dataOut['claim_BOP'] }}</td>
                                                <td style="text-align: center;"></td>
                                                <td style="text-align: center;">{{ $dataOut['start_point_time'] }}</td>
                                                <td style="text-align: center;">{{ $dataOut['service_start'] }}</td>
                                                <td style="text-align: center;">{{ $dataOut['actual_start'] }}</td>
                                                <td style="text-align: center;">{{ $dataOut['sales_start'] }}</td>
                                                <td style="text-align: center;">{{ $dataOut['service_end'] }}</td>
                                                <td style="text-align: center;">{{ $dataOut['actual_end'] }}</td>
                                                <td style="text-align: center;">{{ $dataOut['sales_end'] }}</td>
                                                <td style="text-align: center;">{{ $dataOut['punctuality'] }}</td>
                                                <td>{{ $dataOut['pass_count'] }}</td>
                                                <td>{{ $dataOut['total_sales'] }}</td>
                                                <td>{{ $dataOut['total_on'] }}</td>
                                                <td>{{ $dataOut['adult'] }}</td>
                                                <td>{{ $dataOut['concession'] }}</td>
                                            </tr>
                                        @endforeach 
                                    @endif
                                @endforeach
                            @endif
                        @endif

                        @if(array_key_exists('total_per_date',$dataPerDate))
                            @if(!empty(($dataPerDate['total_per_date'])))
                                <tr>
                                    <td colspan="11" style="text-align: right;">
                                        <strong>Total for Service Date: {{$key2}}</strong>
                                    </td>
                                @foreach($dataPerDate['total_per_date'] as $key6 => $totalPerDate)
                                    @if($key6=='total_count_passenger_date')
                                        <td colspan="13">&nbsp;</td>
                                    @endif
                                    <td><strong>{{$totalPerDate}}</strong></td>
                                @endforeach
                                </tr>
                                <tr>
                                    <td colspan="41">&nbsp;</td>
                                </tr>
                            @endif
                        @endif
                    @endforeach
                @endif
            @endforeach
        @endif
        @if(array_key_exists("grand",$reportValue))
            @if(!empty(($reportValue['grand'])))
                <tr>
                    <td colspan="11" style="text-align: right;">
                        <strong>Grand Total:</strong>
                    </td>
                    @foreach($reportValue['grand'] as $key7 => $grandValue)
                        @if($key7=='grand_count_passenger')
                            <td colspan="13">&nbsp;</td>
                        @endif
                        <td><strong>{{$grandValue}}</strong></td>
                    @endforeach
                </tr>
            @endif
        @endif
    @endforeach
    </tbody>
</table>
