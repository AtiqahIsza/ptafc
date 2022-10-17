<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" href="{{ URL::asset('perak-transit-logo.ico') }}" type="image/x-icon"/>
    <title>Maraliner</title>

    <!-- Styles -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>
</head>

<body>
    <table class="table-bordered">
        <thead>
            <tr>
                <th colspan="9" style="vertical-align: middle; text-align: center;">
                    <strong>Claim Details GPS Report</strong>
                </th>
            </tr>
            <tr>
                <td colspan="9">&nbsp;</td>
            </tr>
            <tr>
                <th style="text-align: center;"><strong>No.</strong></th>
                <th style="text-align: center;"><strong>Bus Registration No</strong></th>
                <th style="text-align: center;"><strong>Creation Date</strong></th>
                <th style="text-align: center;"><strong>Speed (KM)</strong></th>
                <th style="text-align: center;"><strong>Latitude</strong></th>
                <th style="text-align: center;"><strong>Longitude</strong></th>
                <th style="text-align: center;"><strong>PHMS Status</strong></th>
                <th style="text-align: center;"><strong>PHMS Upload Date</strong></th>
                <th style="text-align: center;"><strong>Duration (Seconds)</strong></th>
            </tr>
        </thead>
        <tbody>
            @if($allGPS!=NULL)
                @foreach($allGPS as $key1 => $perGPS)
                    <tr>
                        <td style="text-align: center;">{{$key1}}</td>
                        @foreach($perGPS as $key2 => $gpsData)
                            <td style="text-align: center;">{{$gpsData}}</td>
                        @endforeach
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="9">
                        <strong>No Records Found...</strong>
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
</body>
</html>