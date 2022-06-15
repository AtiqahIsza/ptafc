<table style="border-color: #000000; border-style: solid;">
    <thead>
    <tr>
        <th rowspan="2" colspan="5" style="vertical-align: middle; text-align: center;">
            <strong>Buses Details</strong>
        </th>
    </tr>
    </thead>
    <tbody>
    @foreach($data as $key1 => $values)
        <tr>
            <th colspan="5">
                &nbsp;
            </th>
        </tr>
        <tr>
            <td colspan="5"><strong>Company Name: {{ $key1 }}</strong></td>
        </tr>
        <tr>
            <td style="text-align: center;"><strong>No</strong></td>
            <td style="text-align: center;"><strong>Bus Registration Number</strong></td>
            <td style="text-align: center;"><strong>Bus Type</strong></td>
            <td style="text-align: center;"><strong>Bus Manufacturing Date</strong></td>
            <td style="text-align: center;"><strong>Age</strong></td>
        </tr>
        @php $i=1; @endphp
        @foreach($values as $value)
            <tr>
                <td style="text-align: center;">{{ $i++ }}</td>
                <td style="text-align: center;">{{ $value->bus_registration_number }}</td>
                @if($value->bus_type_id==1000)
                    <td style="text-align: center;">NORMAL</td>
                @else
                    <td style="text-align: center;">NOT AVAILABLE</td>
                @endif
                <td style="text-align: center;">{{ $value->bus_manufacturing_date }}</td>
                <td style="text-align: center;">{{ $value->bus_age }}</td>
            </tr>
        @endforeach
    @endforeach 
    </tbody>
</table>
