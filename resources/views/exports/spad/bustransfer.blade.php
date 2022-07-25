<table style="border-color: #000000; border-style: solid;">
    <thead>
    <tr>
        <th colspan="12" style="vertical-align: middle; text-align: center;">
            <strong>Bus Transfer Report</strong>
        </th>
    </tr>
    <tr>
        <th colspan="12">&nbsp;</th>
    </tr>
    <tr>
        <th colspan="12">
            <strong>Network Operator: MARALINER</strong>
        </th>
    </tr>
    <tr>
        <th colspan="12">
            <strong>Network Area: {{ $networkArea }}</strong>
        </th>
    </tr>
    <tr>
        <th colspan="12">
            <strong> Reporting Period: {{ $dateFrom }} - {{ $dateTo }} </strong>
        </th>
    </tr>
    <tr>
        <th colspan="12">
            <strong>Date Printed: {{ Carbon\Carbon::now() }}</strong>
        </th>
    </tr>
    </thead>

    <tbody>
        <tr>
            <td colspan="12">&nbsp;</td>
        </tr>
        <tr>
            <td style="text-align: center;"><strong>Route No.</strong></td>
            <td style="text-align: center;"><strong>OD</strong></td>
            <td style="text-align: center;"><strong>Date</strong></td>
            <td style="text-align: center;"><strong>Trip No.</strong></td>
            <td style="text-align: center;"><strong>Bus Registration Number</strong></td>
            <td style="text-align: center;"><strong>Type</strong></td>
            <td style="text-align: center;"><strong>Bus Stop Description</strong></td>
            <td style="text-align: center;"><strong>Bus Stop Travel</strong></td>
            <td style="text-align: center;"><strong>Total Transfer</strong></td>
            <td style="text-align: center;"><strong>Total On</strong></td>
            <td style="text-align: center;"><strong>Adult</strong></td>
            <td style="text-align: center;"><strong>Concession</strong></td>
        </tr>
        <tr>
            <td colspan="12">No Records Found...</td>
        </tr>
    </tbody>
</table>
