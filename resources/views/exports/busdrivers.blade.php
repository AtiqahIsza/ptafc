<table style="border-color: #000000; border-style: solid;">
    <thead>
    <tr>
        <th rowspan="2" colspan="7" style="vertical-align: middle; text-align: center;">
            <strong>Bus Drivers Details</strong>
        </th>
    </tr>
    </thead>
    <tbody>
    @foreach($data as $key1 => $values)
        <tr>
            <th colspan="7">
                &nbsp;
            </th>
        </tr>
        <tr>
            <td colspan="7"><strong>Company Name: {{ $key1 }}</strong></td>
        </tr>
        <tr>
            <td style="text-align: center;"><strong>No</strong></td>
            <td style="text-align: center;"><strong>Driver Name</strong></td>
            <td style="text-align: center;"><strong>Employee Number</strong></td>
            <td style="text-align: center;"><strong>Driver ID</strong></td>
            <td style="text-align: center;"><strong>Driver Role</strong></td>
            <td style="text-align: center;"><strong>Status</strong></td>
            <td style="text-align: center;"><strong>Driver Number (For PDA Login)</strong></td>
        </tr>
        @php $i=1; @endphp
        @if($values!=NULL)
            @foreach($values as $value)
                <tr>
                    <td style="text-align: center;">{{ $i++ }}</td>
                    <td>{{ $value->driver_name}}</td>
                    <td style="text-align: center;">{{ $value->employee_number}}</td>
                    <td style="text-align: center;">{{ $value->id_number}}</td>
                    @if($value->driver_role==1)
                        <td style="text-align: center;">Driver</td>
                    @elseif($value->driver_role==2)
                        <td style="text-align: center;">Inspector</td>
                    @else
                        <td style="text-align: center;">Administrator</td>
                    @endif
                    @if($value->status==1)
                        <td style="text-align: center;">Active</td>
                    @elseif($value->status==2)
                        <td style="text-align: center;">Inactive</td>
                    @else
                        <td style="text-align: center;">Blacklisted</td>
                    @endif
                    <td style="text-align: center;">{{ $value->driver_number }}</td>
                </tr>
            @endforeach
        @else
            <tr>
                <td colspan="7"><strong>No Records Found...</strong></td>
            </tr>
        @endif
    @endforeach 
    </tbody>
</table>
