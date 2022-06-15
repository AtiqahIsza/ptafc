<table style="border-color: #000000; border-style: solid;">
    <thead>
    <tr>
        <th rowspan="2" colspan="6" style="vertical-align: middle; text-align: center;">
            <strong>Routes Details</strong>
        </th>
    </tr>
    </thead>
    <tbody>
    @foreach($data as $key1 => $values)
        <tr>
            <th colspan="6">
                &nbsp;
            </th>
        </tr>
        <tr>
            <td colspan="6"><strong>Company Name: {{ $key1 }}</strong></td>
        </tr>
        <tr>
            <td style="text-align: center;"><strong>No</strong></td>
            <td style="text-align: center;"><strong>Route Number</strong></td>
            <td style="text-align: center;"><strong>Route Name</strong></td>
            <td style="text-align: center;"><strong>Inbound Distance</strong></td>
            <td style="text-align: center;"><strong>Outbound Distance</strong></td>
            <td style="text-align: center;"><strong>Status</strong></td>
        </tr>
        @php $i=1; @endphp
        @foreach($values as $value)
            <tr>
                <td style="text-align: center;">{{ $i++ }}</td>
                <td style="text-align: center;">{{ $value->route_number }}</td>
                <td>{{ $value->route_name }}</td>
                <td style="text-align: center;">{{ $value->inbound_distance }}</td>
                <td style="text-align: center;">{{ $value->outbound_distance }}</td>
                @if($value->status==1)
                    <td style="text-align: center;">ACTIVE</td>
                @else
                    <td style="text-align: center;">INACTIVE</td>
                @endif
            </tr>
        @endforeach
    @endforeach 
    </tbody>
</table>
