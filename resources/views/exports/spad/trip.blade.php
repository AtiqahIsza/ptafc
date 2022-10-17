<table style="border-color: #000000; border-style: solid;">
    <thead>
    <tr>
        <th colspan="19" style="vertical-align: middle; text-align: center;">
            <strong>Trip Report</strong>
        </th>
    </tr>
    <tr>
        <th colspan="19">&nbsp;</th>
    </tr>
    <tr>
        <th colspan="19">
            <strong>Network Operator: MARALINER</strong>
        </th>
    </tr>
    <tr>
        <th colspan="19">
            <strong>Network Area: {{ $networkArea }}</strong>
        </th>
    </tr>
    <tr>
        <th colspan="19">
            <strong> Reporting Period: {{ $dateFrom }} - {{ $dateTo }} </strong>
        </th>
    </tr>
    <tr>
        <th colspan="19">
            <strong>Date Printed: {{ Carbon\Carbon::now() }}</strong>
        </th>
    </tr>
    </thead>

    <tbody>
        <tr>
            <td colspan="19">&nbsp;</td>
        </tr>
        <tr>
            <td rowspan="2" style="text-align: center;"><strong>Route No.</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>OD</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>No. of Trips</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Trip No</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Bus Plate Number</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Driver ID</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Service Date</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Start Point</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Service Start Time</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Actual Start Time</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Sales Start Time</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Service End Time</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Actual End Time</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Sales End Time</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Passengers Boarding Count</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Total Sales Amount (RM)</strong></td>
            <td colspan="3" style="text-align: center;"><strong>ETM Boarding Passenger Count</strong></td>
        </tr>
        <tr>
            <td><strong>Total On</strong></td>
            <td><strong>Adult</strong></td>
            <td><strong>Concession</strong></td>
        </tr>

        @php $pastRoute=NULL; $pastDate=NULL; $pastCode=NULL;  $lastKey = array_key_last($reports);@endphp
        @foreach($reports as $key1 => $reportValue)
            @php $newCode=false;@endphp
            @php $newRoute=false;@endphp

            @if($reportValue->id==$pastRoute && $pastRoute!=NULL)
                @if($reportValue->trip_code!=$pastCode)
                    @php $newCode=true;@endphp
                    @if($pastCode==1)
                        @foreach($totIns as $key2 => $totIn)
                            @if($totIn->id==$pastRoute && $totIn->service_date==$pastDate)
                                <tr>
                                    <td colspan="14" style="text-align: right;">
                                        <strong>Total For Route OD: {{ $totIn->route_name }}</strong>
                                    </td>
                                    <td><strong>{{ $totIn->ridership }}</strong></td>
                                    <td><strong>{{ $totIn->farebox }}</strong></td>
                                    <td><strong>{{ $totIn->ridership }}</strong></td>
                                    <td><strong>{{ $totIn->total_adult }}</strong></td>
                                    <td><strong>{{ $totIn->total_concession }}</strong></td>
                                </tr>
                                @php break; @endphp
                            @endif
                        @endforeach
                    @else
                        @foreach($totOuts as $key3 => $totOut)
                            @if($totOut->id==$pastRoute && $totOut->service_date==$pastDate)
                                <tr>
                                    <td colspan="14" style="text-align: right;">
                                        <strong>Total For Route OD: {{ $totOut->route_name }}</strong>
                                    </td>
                                    <td><strong>{{ $totOut->ridership }}</strong></td>
                                    <td><strong>{{ $totOut->farebox }}</strong></td>
                                    <td><strong>{{ $totOut->ridership }}</strong></td>
                                    <td><strong>{{ $totOut->total_adult }}</strong></td>
                                    <td><strong>{{ $totOut->total_concession }}</strong></td>
                                </tr>
                                @php break; @endphp
                            @endif
                        @endforeach     
                    @endif
                @endif
                
                @if($reportValue->service_date!=$pastDate && $pastDate!=NULL)
                    @foreach($totDates as $key4 => $totDate)
                        @if($totDate->id==$pastRoute && $totDate->service_date==$pastDate)
                            <tr>
                                <td colspan="14" style="text-align: right;">
                                    <strong>Total For Service Date: {{ $totDate->service_date }}</strong>
                                </td>
                                <td><strong>{{ $totDate->ridership }}</strong></td>
                                <td><strong>{{ $totDate->farebox }}</strong></td>
                                <td><strong>{{ $totDate->ridership }}</strong></td>
                                <td><strong>{{ $totDate->total_adult }}</strong></td>
                                <td><strong>{{ $totDate->total_concession }}</strong></td>
                            </tr>
                            @php break; @endphp
                        @endif
                    @endforeach
                @endif

            @elseif($reportValue->id!=$pastRoute && $pastRoute!=NULL)
                @php $newCode=true;@endphp
                @if($pastCode==1)
                    @foreach($totIns as $key5 => $totIn)
                        @if($totIn->id==$pastRoute && $totIn->service_date==$pastDate)
                            <tr>
                                <td colspan="14" style="text-align: right;">
                                    <strong>Total For Route OD: {{ $totIn->route_name }}</strong>
                                </td>
                                <td><strong>{{ $totIn->ridership }}</strong></td>
                                <td><strong>{{ $totIn->farebox }}</strong></td>
                                <td><strong>{{ $totIn->ridership }}</strong></td>
                                <td><strong>{{ $totIn->total_adult }}</strong></td>
                                <td><strong>{{ $totIn->total_concession }}</strong></td>
                            </tr>
                            @php break; @endphp
                        @endif
                    @endforeach
                @else
                    @foreach($totOuts as $key6 => $totOut)
                        @if($totOut->id==$pastRoute && $totOut->service_date==$pastDate)
                            <tr>
                                <td colspan="14" style="text-align: right;">
                                    <strong>Total For Route OD: {{ $totOut->route_name }}</strong>
                                </td>
                                <td><strong>{{ $totOut->ridership }}</strong></td>
                                <td><strong>{{ $totOut->farebox }}</strong></td>
                                <td><strong>{{ $totOut->ridership }}</strong></td>
                                <td><strong>{{ $totOut->total_adult }}</strong></td>
                                <td><strong>{{ $totOut->total_concession }}</strong></td>
                            </tr>
                            @php break; @endphp
                        @endif
                    @endforeach     
                @endif

                @foreach($totDates as $key7 => $totDate)
                    @if($totDate->id==$pastRoute && $totDate->service_date==$pastDate)
                        <tr>
                            <td colspan="14" style="text-align: right;">
                                <strong>Total For Service Date: {{ $totDate->service_date }}</strong>
                            </td>
                            <td><strong>{{ $totDate->ridership }}</strong></td>
                            <td><strong>{{ $totDate->farebox }}</strong></td>
                            <td><strong>{{ $totDate->ridership }}</strong></td>
                            <td><strong>{{ $totDate->total_adult }}</strong></td>
                            <td><strong>{{ $totDate->total_concession }}</strong></td>
                        </tr>
                        @php break; @endphp
                    @endif
                @endforeach
                
                @foreach($totRoutes as $key8 => $totRoute)
                    @if($totRoute->id==$pastRoute)
                        @php $newRoute=true;@endphp
                        <tr>
                            <td colspan="14" style="text-align: right;">
                                <strong>Total For Route No: {{ $totRoute->route_number }}</strong>
                            </td>
                            <td><strong>{{ $totRoute->ridership }}</strong></td>
                            <td><strong>{{ $totRoute->farebox }}</strong></td>
                            <td><strong>{{ $totRoute->ridership }}</strong></td>
                            <td><strong>{{ $totRoute->total_adult }}</strong></td>
                            <td><strong>{{ $totRoute->total_concession }}</strong></td>
                        </tr>
                        @php break; @endphp
                    @endif
                @endforeach
            @endif

            @if($newCode==true || $newRoute==true)
                <tr>
                    <td colspan="19">&nbsp;</td>
                </tr>
                <tr>
                    <td rowspan="2" style="text-align: center;"><strong>Route No.</strong></td>
                    <td rowspan="2" style="text-align: center;"><strong>OD</strong></td>
                    <td rowspan="2" style="text-align: center;"><strong>No. of Trips</strong></td>
                    <td rowspan="2" style="text-align: center;"><strong>Trip No</strong></td>
                    <td rowspan="2" style="text-align: center;"><strong>Bus Plate Number</strong></td>
                    <td rowspan="2" style="text-align: center;"><strong>Driver ID</strong></td>
                    <td rowspan="2" style="text-align: center;"><strong>Service Date</strong></td>
                    <td rowspan="2" style="text-align: center;"><strong>Start Point</strong></td>
                    <td rowspan="2" style="text-align: center;"><strong>Service Start Time</strong></td>
                    <td rowspan="2" style="text-align: center;"><strong>Actual Start Time</strong></td>
                    <td rowspan="2" style="text-align: center;"><strong>Sales Start Time</strong></td>
                    <td rowspan="2" style="text-align: center;"><strong>Service End Time</strong></td>
                    <td rowspan="2" style="text-align: center;"><strong>Actual End Time</strong></td>
                    <td rowspan="2" style="text-align: center;"><strong>Sales End Time</strong></td>
                    <td rowspan="2" style="text-align: center;"><strong>Passengers Boarding Count</strong></td>
                    <td rowspan="2" style="text-align: center;"><strong>Total Sales Amount (RM)</strong></td>
                    <td colspan="3" style="text-align: center;"><strong>ETM Boarding Passenger Count</strong></td>
                </tr>
                <tr>
                    <td><strong>Total On</strong></td>
                    <td><strong>Adult</strong></td>
                    <td><strong>Concession</strong></td>
                </tr>      
            @endif
            <tr>
                <td style="text-align: center;">{{ $reportValue->route_number }}</td>
                <td style="text-align: center;">{{ $reportValue->route_name }}</td>
                <td style="text-align: center;">{{ $reportValue->no_of_trip }}</td>
                <td style="text-align: center;">{{ $reportValue->trip_no }}</td>
                <td style="text-align: center;">{{ $reportValue->bus_registration_number }}</td>
                <td style="text-align: center;">{{ $reportValue->driver_id }}</td>
                <td style="text-align: center;">{{ $reportValue->service_date }}</td>
                <td style="text-align: center;">{{ $reportValue->start_point }}</td>
                <td style="text-align: center;">{{ $reportValue->service_start }}</td>
                <td style="text-align: center;">{{ $reportValue->actual_start }}</td>
                <td style="text-align: center;">{{ $reportValue->sales_start }}</td>
                <td style="text-align: center;">{{ $reportValue->service_end }}</td>
                <td style="text-align: center;">{{ $reportValue->actual_end }}</td>
                <td style="text-align: center;">{{ $reportValue->sales_end }}</td>
                <td>{{ $reportValue->ridership }}</td>
                <td>{{ $reportValue->farebox }}</td>
                <td>{{ $reportValue->ridership  }}</td>
                <td>{{ $reportValue->total_adult }}</td>
                <td>{{ $reportValue->total_concession }}</td>
            </tr>
            @php $pastRoute=$reportValue->id; $pastDate=$reportValue->service_date; $pastCode=$reportValue->trip_code;@endphp


            @if($lastKey==$key1)
                @if($pastCode==1)
                    @foreach($totIns as $key9 => $totIn)
                        @if($totIn->id==$pastRoute && $totIn->service_date==$pastDate)
                            <tr>
                                <td colspan="14" style="text-align: right;">
                                    <strong>Total For Route OD: {{ $totIn->route_name }}</strong>
                                </td>
                                <td><strong>{{ $totIn->ridership }}</strong></td>
                                <td><strong>{{ $totIn->farebox }}</strong></td>
                                <td><strong>{{ $totIn->ridership }}</strong></td>
                                <td><strong>{{ $totIn->total_adult }}</strong></td>
                                <td><strong>{{ $totIn->total_concession }}</strong></td>
                            </tr>
                            @php break; @endphp
                        @endif
                    @endforeach
                @else
                    @foreach($totOuts as $key5 => $totOut)
                        @if($totOut->id==$pastRoute && $totOut->service_date==$pastDate)
                            <tr>
                                <td colspan="14" style="text-align: right;">
                                    <strong>Total For Route OD: {{ $totOut->route_name }}</strong>
                                </td>
                                <td><strong>{{ $totOut->ridership }}</strong></td>
                                <td><strong>{{ $totOut->farebox }}</strong></td>
                                <td><strong>{{ $totOut->ridership }}</strong></td>
                                <td><strong>{{ $totOut->total_adult }}</strong></td>
                                <td><strong>{{ $totOut->total_concession }}</strong></td>
                            </tr>
                            @php break; @endphp
                        @endif
                    @endforeach     
                @endif

                @foreach($totDates as $key3 => $totDate)
                    @if($totDate->id==$pastRoute && $totDate->service_date==$pastDate)
                        <tr>
                            <td colspan="14" style="text-align: right;">
                                <strong>Total For Service Date: {{ $totDate->service_date }}</strong>
                            </td>
                            <td><strong>{{ $totDate->ridership }}</strong></td>
                            <td><strong>{{ $totDate->farebox }}</strong></td>
                            <td><strong>{{ $totDate->ridership }}</strong></td>
                            <td><strong>{{ $totDate->total_adult }}</strong></td>
                            <td><strong>{{ $totDate->total_concession }}</strong></td>
                        </tr>
                        @php break; @endphp
                    @endif
                @endforeach
                
                @foreach($totRoutes as $key2 => $totRoute)
                    @if($totRoute->id==$pastRoute)
                        @php $newRoute=true;@endphp
                        <tr>
                            <td colspan="14" style="text-align: right;">
                                <strong>Total For Route No: {{ $totRoute->route_number }}</strong>
                            </td>
                            <td><strong>{{ $totRoute->ridership }}</strong></td>
                            <td><strong>{{ $totRoute->farebox }}</strong></td>
                            <td><strong>{{ $totRoute->ridership }}</strong></td>
                            <td><strong>{{ $totRoute->total_adult }}</strong></td>
                            <td><strong>{{ $totRoute->total_concession }}</strong></td>
                        </tr>
                        @php break; @endphp
                    @endif
                @endforeach
            @endif
        @endforeach

        @foreach($totGrands as $key6 => $grand)
            <tr>
                <td colspan="14" style="text-align: right;">
                    <strong>Grand Total:</strong>
                </td>
                <td><strong>{{ $grand->ridership }}</strong></td>
                <td><strong>{{ $grand->farebox_grand }}</strong></td>
                <td><strong>{{ $grand->ridership }}</strong></td>
                <td><strong>{{ $grand->total_adult }}</strong></td>
                <td><strong>{{ $grand->total_concession }}</strong></td>
            </tr>
        @endforeach
    </tbody>


    {{-- allTrip in one --}}
    {{-- <tbody>
        <tr>
            <td colspan="19">&nbsp;</td>
        </tr>
        <tr>
            <td rowspan="2" style="text-align: center;"><strong>Route No.</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>OD</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>No. of Trips</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Trip No</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Bus Plate Number</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Driver ID</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Service Date</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Start Point</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Service Start Time</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Actual Start Time</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Sales Start Time</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Service End Time</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Actual End Time</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Sales End Time</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Passengers Boarding Count</strong></td>
            <td rowspan="2" style="text-align: center;"><strong>Total Sales Amount (RM)</strong></td>
            <td colspan="3" style="text-align: center;"><strong>ETM Boarding Passenger Count</strong></td>
        </tr>
        <tr>
            <td><strong>Total On</strong></td>
            <td><strong>Adult</strong></td>
            <td><strong>Concession</strong></td>
        </tr>

        @php $newCode=false;@endphp
        @foreach($reports as $key1 => $reportValue)
            @if(property_exists($reportValue, 'farebox_grand'))
                <tr>
                    <td colspan="14" style="text-align: right;">
                        <strong>Grand Total:</strong>
                    </td>
                    <td><strong>{{ $reportValue->ridership }}</strong></td>
                    <td><strong>{{ $reportValue->farebox_grand }}</strong></td>
                    <td><strong>{{ $reportValue->ridership }}</strong></td>
                    <td><strong>{{ $reportValue->total_adult }}</strong></td>
                    <td><strong>{{ $reportValue->total_concession }}</strong></td>
                </tr>
            @elseif(property_exists($reportValue, 'route_number_route'))
                <tr>
                    <td colspan="14" style="text-align: right;">
                        <strong>Total For Route No: {{ $reportValue->route_number_route }}:</strong>
                    </td>
                    <td><strong>{{ $reportValue->ridership }}</strong></td>
                    <td><strong>{{ $reportValue->farebox }}</strong></td>
                    <td><strong>{{ $reportValue->ridership }}</strong></td>
                    <td><strong>{{ $reportValue->total_adult }}</strong></td>
                    <td><strong>{{ $reportValue->total_concession }}</strong></td>
                </tr>
            @elseif(property_exists($reportValue, 'route_number_date'))
                <tr>
                    <td colspan="14" style="text-align: right;">
                        <strong>Total For Service Date: {{ $reportValue->service_date }}:</strong>
                    </td>
                    <td><strong>{{ $reportValue->ridership }}</strong></td>
                    <td><strong>{{ $reportValue->farebox }}</strong></td>
                    <td><strong>{{ $reportValue->ridership }}</strong></td>
                    <td><strong>{{ $reportValue->total_adult }}</strong></td>
                    <td><strong>{{ $reportValue->total_concession }}</strong></td>
                </tr>
            @elseif(property_exists($reportValue, 'route_name_code'))
                @php $newCode=true;@endphp
                <tr>
                    <td colspan="14" style="text-align: right;">
                        <strong>Total For Route OD: {{ $reportValue->route_name_code }}:</strong>
                    </td>
                    <td><strong>{{ $reportValue->ridership }}</strong></td>
                    <td><strong>{{ $reportValue->farebox }}</strong></td>
                    <td><strong>{{ $reportValue->ridership }}</strong></td>
                    <td><strong>{{ $reportValue->total_adult }}</strong></td>
                    <td><strong>{{ $reportValue->total_concession }}</strong></td>
                </tr>
            @else
                @if($newCode==true)
                    <tr>
                        <td colspan="19">&nbsp;</td>
                    </tr>
                    <tr>
                        <td rowspan="2" style="text-align: center;"><strong>Route No.</strong></td>
                        <td rowspan="2" style="text-align: center;"><strong>OD</strong></td>
                        <td rowspan="2" style="text-align: center;"><strong>No. of Trips</strong></td>
                        <td rowspan="2" style="text-align: center;"><strong>Trip No</strong></td>
                        <td rowspan="2" style="text-align: center;"><strong>Bus Plate Number</strong></td>
                        <td rowspan="2" style="text-align: center;"><strong>Driver ID</strong></td>
                        <td rowspan="2" style="text-align: center;"><strong>Service Date</strong></td>
                        <td rowspan="2" style="text-align: center;"><strong>Start Point</strong></td>
                        <td rowspan="2" style="text-align: center;"><strong>Service Start Time</strong></td>
                        <td rowspan="2" style="text-align: center;"><strong>Actual Start Time</strong></td>
                        <td rowspan="2" style="text-align: center;"><strong>Sales Start Time</strong></td>
                        <td rowspan="2" style="text-align: center;"><strong>Service End Time</strong></td>
                        <td rowspan="2" style="text-align: center;"><strong>Actual End Time</strong></td>
                        <td rowspan="2" style="text-align: center;"><strong>Sales End Time</strong></td>
                        <td rowspan="2" style="text-align: center;"><strong>Passengers Boarding Count</strong></td>
                        <td rowspan="2" style="text-align: center;"><strong>Total Sales Amount (RM)</strong></td>
                        <td colspan="3" style="text-align: center;"><strong>ETM Boarding Passenger Count</strong></td>
                    </tr>
                    <tr>
                        <td><strong>Total On</strong></td>
                        <td><strong>Adult</strong></td>
                        <td><strong>Concession</strong></td>
                    </tr>
                    @php $newCode=false;@endphp
                @endif
                <tr>
                    <td style="text-align: center;">{{ $reportValue->route_number }}</td>
                    <td style="text-align: center;">{{ $reportValue->route_name }}</td>
                    <td style="text-align: center;">{{ $reportValue->no_of_trip }}</td>
                    <td style="text-align: center;">{{ $reportValue->trip_no }}</td>
                    <td style="text-align: center;">{{ $reportValue->bus_registration_number }}</td>
                    <td style="text-align: center;">{{ $reportValue->driver_id }}</td>
                    <td style="text-align: center;">{{ $reportValue->service_date }}</td>
                    <td style="text-align: center;">{{ $reportValue->start_point }}</td>
                    <td style="text-align: center;">{{ $reportValue->service_start }}</td>
                    <td style="text-align: center;">{{ $reportValue->actual_start }}</td>
                    <td style="text-align: center;">{{ $reportValue->sales_start }}</td>
                    <td style="text-align: center;">{{ $reportValue->service_end }}</td>
                    <td style="text-align: center;">{{ $reportValue->actual_end }}</td>
                    <td style="text-align: center;">{{ $reportValue->sales_end }}</td>
                    <td>{{ $reportValue->ridership }}</td>
                    <td>{{ $reportValue->farebox }}</td>
                    <td>{{ $reportValue->ridership  }}</td>
                    <td>{{ $reportValue->total_adult }}</td>
                    <td>{{ $reportValue->total_concession }}</td>
                </tr>
            @endif
        @endforeach
    </tbody> --}}

    {{-- <tbody>
        @foreach($reports as $key1 => $reportValue)
            @if(array_key_exists("allRoute",$reportValue))
                @foreach($reportValue['allRoute'] as $key2 => $allDates)
                    @php $existTrip = false; @endphp
                    @foreach($allDates as $key3 => $dataPerDate)
                        @if($key3=='total_per_route')
                            @if($existTrip ==true)
                                <tr>
                                    <td colspan="14" style="text-align: right;">
                                        <strong>Total for Route No: {{ $key2 }}</strong>
                                    </td>
                                    @foreach($dataPerDate as $key4 => $totalPerDate)
                                        <td><strong>{{ $totalPerDate }}</strong></td>
                                    @endforeach
                                </tr>
                            @endif
                        @else
                            @if($dataPerDate!=NULL)
                                @php $existTrip = true; @endphp
                                @foreach($dataPerDate as $key4 => $allTrip)
                                    @php $i=0 @endphp
                                    @if($key4=='total_per_date')
                                        <tr>
                                            <td colspan="14" style="text-align: right;">
                                                <strong>Total for Service Date: {{ $key3 }}</strong>
                                            </td>
                                            @foreach($allTrip as $key5 => $totalDate)
                                                <td><strong>{{$totalDate}}</strong></td>
                                            @endforeach
                                        </tr>
                                    @else
                                        @foreach($allTrip as $key6 => $perTrip)
                                            @if($key6=='total')
                                                <tr>
                                                    <td colspan="14" style="text-align: right;">
                                                        <strong>Total For Route OD: {{ $key4 }}</strong>
                                                    </td>
                                                    @foreach($perTrip as $key7 => $totalRoute)
                                                        <td><strong>{{$totalRoute}}</strong></td>
                                                    @endforeach
                                                </tr>
                                            @else
                                                @if($i==0)
                                                    <tr>
                                                        <td colspan="19">&nbsp;</td>
                                                    </tr>
                                                    <tr>
                                                        <td rowspan="2" style="text-align: center;"><strong>Route No.</strong></td>
                                                        <td rowspan="2" style="text-align: center;"><strong>OD</strong></td>
                                                        <td rowspan="2" style="text-align: center;"><strong>No. of Trips</strong></td>
                                                        <td rowspan="2" style="text-align: center;"><strong>Trip No</strong></td>
                                                        <td rowspan="2" style="text-align: center;"><strong>Bus Plate Number</strong></td>
                                                        <td rowspan="2" style="text-align: center;"><strong>Driver ID</strong></td>
                                                        <td rowspan="2" style="text-align: center;"><strong>Service Date</strong></td>
                                                        <td rowspan="2" style="text-align: center;"><strong>Start Point</strong></td>
                                                        <td rowspan="2" style="text-align: center;"><strong>Service Start Time</strong></td>
                                                        <td rowspan="2" style="text-align: center;"><strong>Actual Start Time</strong></td>
                                                        <td rowspan="2" style="text-align: center;"><strong>Sales Start Time</strong></td>
                                                        <td rowspan="2" style="text-align: center;"><strong>Service End Time</strong></td>
                                                        <td rowspan="2" style="text-align: center;"><strong>Actual End Time</strong></td>
                                                        <td rowspan="2" style="text-align: center;"><strong>Sales End Time</strong></td>
                                                        <td rowspan="2" style="text-align: center;"><strong>Passengers Boarding Count</strong></td>
                                                        <td rowspan="2" style="text-align: center;"><strong>Total Sales Amount (RM)</strong></td>
                                                        <td colspan="3" style="text-align: center;"><strong>ETM Boarding Passenger Count</strong></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Total On</strong></td>
                                                        <td><strong>Adult</strong></td>
                                                        <td><strong>Concession</strong></td>
                                                    </tr>
                                                @endif
                                                @php $i++ @endphp
                                                <tr>
                                                    <td style="text-align: center;">{{ $key2 }}</td>
                                                    <td style="text-align: center;">{{ $key4 }}</td>
                                                    <td style="text-align: center;">{{ $key6 }}</td>
                                                    @foreach($perTrip as $key8 => $perTripData)
                                                        <td style="text-align: center;">{{ $perTripData }}</td>
                                                    @endforeach
                                                </tr>
                                            @endif
                                        @endforeach
                                    @endif
                                @endforeach
                            @endif
                        @endif
                    @endforeach
                @endforeach
            @endif
            @if(array_key_exists("grand",$reportValue))
                <tr>
                    <td colspan="14" style="text-align: right;">
                        <strong>Grand Total:</strong>
                    </td>
                    @foreach($reportValue['grand'] as $key9 => $grand)
                        <td><strong>{{$grand}}</strong></td>
                    @endforeach
                </tr>
            @endif
        @endforeach
    </tbody> --}}
</table>
