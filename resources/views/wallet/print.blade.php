<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Receipt Wallet Top-Up</title>
    <!-- Styles -->
    {{--<link rel="stylesheet" href="{{ asset('css/app.css') }}">--}}
    {{--<link rel="stylesheet" href="{{ ltrim(elixir('assets/css/app.css'), '/') }}" />--}}
    {{--<link rel="stylesheet" href="{{ ltrim(public_path(asset('css/app.css'))) }}">--}}
    <style>
        #container {
            border: 12px solid #B22222;
            border-radius: 10px;
            margin-top: 15px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
<div>
    <h2 style="text-align: center">This receipt </h2>
    <div class="container">
        <table class="table table-bordered">
            <tbody>
            <tr>
                <td><strong>Driver ID</strong></td>
                <td>:</td>
                <td><span>{{ $driver_id }}</span></td>
            </tr>
            <tr>
                <td><strong>Driver Name</strong></td>
                <td>:</td>
                <td><span>{{ $driver_name }} </span></td>
            </tr>
            <tr>
                <td><strong>Value Top-up</strong></td>
                <td>:</td>
                <td><span>RM{{ $value }}</span></td>
            </tr>
            <tr>
                <td><strong>Wallet Balance</strong></td>
                <td>:</td>
                <td><span>RM{{ $balance }}</span></td>
            </tr>
            <tr>
                <td><strong>Date</strong></td>
                <td>:</td>
                <td><span>{{ $date }}</span></td>
            </tr>
            <tr>
                <td><strong>Created By</strong></td>
                <td>:</td>
                <td><span>{{ $created_by->full_name }}</span></td>
            </tr>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
{{--<!DOCTYPE html>
<html lang="en">
<head>
    <title>Receipt Wallet Top-Up</title>
    <!-- Styles -->
    <link rel="stylesheet" href="{{ ltrim(public_path(asset('css/app.css'))) }}">
</head>
<body>
<div class="card card-body border-3 shadow table-wrapper table-responsive">
    <h2 class="mb-4 h5">{{ __('Receipt Wallet Top-Up') }}</h2>
    <table class="table table-hover">
        <thead>
        <tr>
            <th class="border-gray-200">{{ __('Driver ID:') }}</th>
            <th class="border-gray-200">{{ __('Driver Name:') }}</th>
            <th class="border-gray-200">{{ __('Value Top-up:') }}</th>
            <th class="border-gray-200">{{ __('Wallet Balance:') }}</th>
            <th class="border-gray-200">{{ __('Date:') }}</th>
        </tr>
        </thead>
        <tbody>
            <tr>
                <td><span class="fw-normal">{{ $driver_id }}</span></td>
                <td><span class="fw-normal">{{ $driver_name }} </span></td>
                <td><span class="fw-normal">{{ $value }}</span></td>
                <td><span class="fw-normal">{{ $balance }}</span></td>
                <td><span class="fw-normal">{{ $date }}</span></td>
            </tr>
        </tbody>
    </table>
</div>
</body>
</html>--}}
