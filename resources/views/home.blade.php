@extends('layouts.app')
@push('app_script')
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script src="https://code.highcharts.com/modules/series-label.js"></script>
    <script src="https://code.highcharts.com/modules/exporting.js"></script>
    <script src="https://code.highcharts.com/modules/export-data.js"></script>
    <script src="https://code.highcharts.com/modules/accessibility.js"></script>

    <!-- Chartist -->
    <script type="module" src="@@path/vendor/chartist/dist/chartist.min.js"></script>
    <script type="module" src="@@path/vendor/chartist-plugin-tooltips/dist/chartist-plugin-tooltip.min.js"></script>
    <script src="{{ asset('js/dashboard.js') }}" defer></script>
@endpush
@section('content')
    <br>
    <br>
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card bg-yellow-100 border-0 shadow">
                <div class="card-header d-sm-flex flex-row align-items-center flex-0">
                    <div class="d-block mb-3 mb-sm-0">
                        <div class="fs-5 fw-normal mb-2">
                            Sales Amount
                        </div>
                        <h2 class="fs-3 fw-extrabold">RM{{$grandCollection['grand_farebox']}}</h2>
                        <div class="small mt-2">
                            @if($grandCollection['grand_farebox_in'] > 0)
                                <span class="fas fa-angle-up text-success"></span>
                                <span class="text-success fw-bold">{{$grandCollection['grand_farebox_in']}}%</span>
                            @elseif($grandCollection['grand_farebox_in'] == 0)
                                <svg class="bi bi-dash text-gray-900" xmlns="http://www.w3.org/2000/svg"  width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M4 8a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7A.5.5 0 0 1 4 8z"/>
                                </svg>
                                <span class="text-gray-900 fw-bold">{{$grandCollection['grand_farebox_in']}}%</span>
                            @else
                                @php($positive = $grandCollection['grand_farebox_in'] * -1)
                                <span class="fas fa-angle-down text-danger"></span>
                                <span class="text-danger fw-bold">{{ $positive }}%</span>
                            @endif
                            <span class="fw-normal me-2">Since last month</span>
                        </div>
                    </div>
                    <div class="d-block mb-3 mb-sm-0">
                        <div class="fs-5 fw-normal mb-2">
                            Total Passenger
                        </div>
                        <h2 class="fs-3 fw-extrabold">{{$grandCollection['grand_ridership']}}</h2>
                        <div class="small mt-2">
                            @if($grandCollection['grand_ridership_in'] > 0)
                                <span class="fas fa-angle-up text-success"></span>
                                <span class="text-success fw-bold">{{$grandCollection['grand_ridership_in']}}%</span>
                            @elseif($grandCollection['grand_ridership_in'] == 0)
                                <svg class="bi bi-dash text-gray-900" xmlns="http://www.w3.org/2000/svg"  width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M4 8a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7A.5.5 0 0 1 4 8z"/>
                                </svg>
                                <span class="text-gray-900 fw-bold">{{$grandCollection['grand_ridership_in']}}%</span>
                            @else
                                @php($positive = $grandCollection['grand_ridership_in'] * -1)
                                <span class="fas fa-angle-down text-danger"></span>
                                <span class="text-danger fw-bold">{{ $positive }}%</span>
                            @endif
                            <span class="fw-normal me-2">Since last month</span>
                        </div>
                    </div>
                    <div class="d-block ms-auto">
                        <div class="d-flex align-items-center text-end mb-2">
                            <span class="dot rounded-circle me-2" style="background-color: #d70206;"></span>
                            <span class="fw-normal small">Sales (RM)</span>
                        </div>
                        <div class="d-flex align-items-center text-end">
                            <span class="dot rounded-circle me-2" style="background-color: #f05b4f;"></span>
                            <span class="fw-normal small">Ridership</span>
                        </div>
                        <br>
                        <div class="d-flex align-items-center text-end">
                            <small class="d-flex align-items-center">
                                {{ $missedTrips['start_date'] }} - {{ $missedTrips['end_date'] }}
                            </small>
                        </div>
                    </div>
                </div>
                <div class="card-body p-2">
                    <div class="bar-chart"></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-4 mb-4">
            <div class="card border-0 shadow">
                <div class="card-body">
                    <div class="row d-block d-xl-flex align-items-center">
                        <div class="col-12 col-xl-5 text-xl-center mb-3 mb-xl-0 d-flex align-items-center justify-content-xl-center">
                            <div class="icon-shape icon-shape-primary rounded me-4 me-sm-0">
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" class="bi bi-x-circle-fill" viewBox="0 0 16 16">
                                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM5.354 4.646a.5.5 0 1 0-.708.708L7.293 8l-2.647 2.646a.5.5 0 0 0 .708.708L8 8.707l2.646 2.647a.5.5 0 0 0 .708-.708L8.707 8l2.647-2.646a.5.5 0 0 0-.708-.708L8 7.293 5.354 4.646z"/>
                                </svg>
                            </div>
                            <div class="d-sm-none">
                                <h2 class="h5">Missed Trip</h2>
                                <h3 class="fw-extrabold mb-1">{{ $missedTrips['missed_trip'] }}</h3>
                            </div>
                        </div>
                        <div class="col-12 col-xl-7 px-xl-0">
                            <div class="d-none d-sm-block">
                                <h2 class="h5">Missed Trip</h2>
                                <h3 class="fw-extrabold mb-1">{{ $missedTrips['missed_trip'] }}</h3>
                            </div>
                            <small class="d-flex align-items-center">
                                {{ $missedTrips['start_date'] }} - {{ $missedTrips['end_date'] }}
                            </small>
                            <div class="small d-flex mt-1">
                                @if($missedTrips['increment'] > 0)
                                    <svg class="icon icon-xs text-danger" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                    <div>
                                        <span class="text-danger fw-bolder me-1">{{$missedTrips['increment'] }}%</span> Since last month
                                    </div>
                                @elseif($missedTrips['increment'] == 0)
                                    <svg class="bi bi-dash text-gray-900" xmlns="http://www.w3.org/2000/svg"  width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M4 8a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7A.5.5 0 0 1 4 8z"/>
                                    </svg>
                                    <span class="text-gray-900 fw-bold">{{$missedTrips['increment']}}%</span>
                                @else
                                    @php($positive = $missedTrips['increment'] * -1)
                                    <svg class="icon icon-xs text-success" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                    <div>
                                        <span class="text-success fw-bolder me-1">{{ $positive }}%</span> Since last month
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-4 mb-4">
            <div class="card border-0 shadow">
                <div class="card-body">
                    <div class="row d-block d-xl-flex align-items-center">
                        <div class="col-12 col-xl-5 text-xl-center mb-3 mb-xl-0 d-flex align-items-center justify-content-xl-center">
                            <div class="icon-shape icon-shape-secondary rounded me-4 me-sm-0">
                                <i class="fas fa-bus fa-fw" style="width:22px;height:22px"></i>
                                {{--<svg class="icon icon-md" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7-1a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd"></path>
                                </svg>--}}
                            </div>
                            <div class="d-sm-none">
                                <h2 class="fw-extrabold h5">Early/Late Trips</h2>
                                <h3 class="mb-1">{{ $earlyLateTrips['early_late_trip'] }}</h3>
                            </div>
                        </div>
                        <div class="col-12 col-xl-7 px-xl-0">
                            <div class="d-none d-sm-block">
                                <h2 class="h5">Early/Late Trips</h2>
                                <h3 class="fw-extrabold mb-1">{{ $earlyLateTrips['early_late_trip'] }}</h3>
                            </div>
                            <small class="d-flex align-items-center">
                                {{ $earlyLateTrips['start_date'] }} - {{ $earlyLateTrips['end_date'] }}
                            </small>
                            <div class="small d-flex mt-1">
                                @if($earlyLateTrips['increment'] > 0)
                                    <svg class="icon icon-xs text-danger" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                    <div>
                                        <span class="text-danger fw-bolder me-1">{{ $earlyLateTrips['increment'] }}%</span> Since last month
                                    </div>
                                @elseif($earlyLateTrips['increment'] == 0)
                                    <svg class="bi bi-dash text-gray-900" xmlns="http://www.w3.org/2000/svg"  width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M4 8a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7A.5.5 0 0 1 4 8z"/>
                                    </svg>
                                    <span class="text-gray-900 fw-bold">{{$earlyLateTrips['increment']}}%</span>
                                @else
                                    @php($positiveLate = $earlyLateTrips['increment'] * -1)
                                    <svg class="icon icon-xs text-success" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                    <div>
                                        <span class="text-success fw-bolder me-1">{{ $positiveLate }}%</span> Since last month
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-4 mb-4">
            <div class="card border-0 shadow">
                <div class="card-body">
                    <div class="row d-block d-xl-flex align-items-center">
                        <div class="col-12 col-xl-5 text-xl-center mb-3 mb-xl-0 d-flex align-items-center justify-content-xl-center">
                            <div class="icon-shape icon-shape-tertiary rounded me-4 me-sm-0">
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" class="bi bi-hand-thumbs-down-fill" viewBox="0 0 16 16">
                                    <path d="M6.956 14.534c.065.936.952 1.659 1.908 1.42l.261-.065a1.378 1.378 0 0 0 1.012-.965c.22-.816.533-2.512.062-4.51.136.02.285.037.443.051.713.065 1.669.071 2.516-.211.518-.173.994-.68 1.2-1.272a1.896 1.896 0 0 0-.234-1.734c.058-.118.103-.242.138-.362.077-.27.113-.568.113-.856 0-.29-.036-.586-.113-.857a2.094 2.094 0 0 0-.16-.403c.169-.387.107-.82-.003-1.149a3.162 3.162 0 0 0-.488-.9c.054-.153.076-.313.076-.465a1.86 1.86 0 0 0-.253-.912C13.1.757 12.437.28 11.5.28H8c-.605 0-1.07.08-1.466.217a4.823 4.823 0 0 0-.97.485l-.048.029c-.504.308-.999.61-2.068.723C2.682 1.815 2 2.434 2 3.279v4c0 .851.685 1.433 1.357 1.616.849.232 1.574.787 2.132 1.41.56.626.914 1.28 1.039 1.638.199.575.356 1.54.428 2.591z"/>
                                </svg>
                            </div>
                            <div class="d-sm-none">
                                <h2 class="fw-extrabold h5">Breakdown</h2>
                                <h3 class="mb-1">{{ $breakdownTrips['breakdown_trip'] }}</h3>
                            </div>
                        </div>
                        <div class="col-12 col-xl-7 px-xl-0">
                            <div class="d-none d-sm-block">
                                <h2 class="h5">Breakdown</h2>
                                <h3 class="fw-extrabold mb-1">{{ $breakdownTrips['breakdown_trip'] }}</h3>
                            </div>
                            <small class="d-flex align-items-center">
                                {{ $breakdownTrips['start_date'] }} - {{ $breakdownTrips['end_date'] }}
                            </small>
                            <div class="small d-flex mt-1">
                                @if($breakdownTrips['increment'] > 0)
                                    <svg class="icon icon-xs text-danger" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                    <div>
                                        <span class="text-danger fw-bolder me-1">{{ $breakdownTrips['increment'] }}%</span> Since last month
                                    </div>
                                @elseif($breakdownTrips['increment'] == 0)
                                    <svg class="bi bi-dash text-gray-900" xmlns="http://www.w3.org/2000/svg"  width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M4 8a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7A.5.5 0 0 1 4 8z"/>
                                    </svg>
                                    <div>
                                        <span class="text-gray-900 fw-bolder me-1">{{ $breakdownTrips['increment'] }}%</span> Since last month
                                    </div>
                                @else
                                    @php($positiveLate = $breakdownTrips['increment'] * -1)
                                    <svg class="icon icon-xs text-success" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                    <div>
                                        <span class="text-success fw-bolder me-1">{{ $positiveLate }}%</span> Since last month
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-xl-8">
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="card border-0 shadow">
                        <div class="card-header">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h2 class="fs-5 fw-bold mb-0">Collection of Farebox and Ridership by Companies</h2>
                                </div>
                            </div>
                        </div>
                        <div id="tableDivToShowData"></div>
                        <div class="table-responsive">
                            <table class="table align-items-center table-flush">
                                <thead class="thead-light">
                                <tr>
                                    <th class="border-bottom" scope="col">Company name</th>
                                    <th class="border-bottom" scope="col">Farebox</th>
                                    <th class="border-bottom" scope="col">Farebox Increment</th>
                                    <th class="border-bottom" scope="col">Ridership</th>
                                    <th class="border-bottom" scope="col">Ridership Increment</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($collectionByCompanies as $key => $value)
                                <tr>
                                    <th class="text-gray-900" scope="row">{{$value['company_name']}}</th>
                                    <td class="fw-bolder text-gray-500">{{$value['farebox']}}</td>
                                    <td class="fw-bolder text-gray-500">
                                        <div class="d-flex">
                                            @if($value['farebox_in']<0)
                                                @php($positive = $value['farebox_in'] * -1)
                                                <svg class="icon icon-xs text-danger me-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                    <path fill-rule="evenodd" d="M14.707 12.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 14.586V3a1 1 0 012 0v11.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                                {{$positive}}%
                                            @elseif($value['farebox_in']== 0)
                                                <svg class="bi bi-dash text-gray-900" xmlns="http://www.w3.org/2000/svg"  width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M4 8a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7A.5.5 0 0 1 4 8z"/>
                                                </svg>
                                                {{$value['farebox_in']}}%
                                            @else
                                                <svg class="icon icon-xs text-success me-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                    <path fill-rule="evenodd" d="M5.293 7.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L6.707 7.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                                {{$value['farebox_in']}}%
                                            @endif
                                        </div>
                                    </td>
                                    <td class="fw-bolder text-gray-500">{{$value['ridership']}}</td>
                                    <td class="fw-bolder text-gray-500">
                                        <div class="d-flex">
                                            @if($value['ridership_in']<0)
                                                @php($positive = $value['ridership_in'] * -1)
                                                <svg class="icon icon-xs text-danger me-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                    <path fill-rule="evenodd" d="M14.707 12.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 14.586V3a1 1 0 012 0v11.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                                {{$positive}}%
                                            @elseif($value['ridership_in']== 0)
                                                <svg class="bi bi-dash text-gray-900" xmlns="http://www.w3.org/2000/svg"  width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M4 8a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7A.5.5 0 0 1 4 8z"/>
                                                </svg>
                                                {{$value['ridership_in']}}%
                                            @else
                                                <svg class="icon icon-xs text-success me-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                    <path fill-rule="evenodd" d="M5.293 7.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L6.707 7.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                                {{$value['ridership_in']}}%
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{--<div class="col-12 col-xxl-6 mb-4">
                    <div class="card border-0 shadow">
                        <div class="card-header border-bottom d-flex align-items-center justify-content-between">
                            <h2 class="fs-5 fw-bold mb-0">Progress track</h2>
                            <a href="#" class="btn btn-sm btn-primary">See tasks</a>
                        </div>
                        <div class="card-body">
                            <div class="row mb-4">
                                <div class="col-auto">
                                    <svg class="icon icon-sm text-gray-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <div class="col">
                                    <div class="progress-wrapper">
                                        <div class="progress-info">
                                            <div class="h6 mb-0">Rocket - SaaS Template</div>
                                            <div class="small fw-bold text-gray-500">
                                                <span>75 %</span>
                                            </div>
                                        </div>
                                        <div class="progress mb-0">
                                            <div class="progress-bar bg-success" role="progressbar" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100" style="width: 75%;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row align-items-center mb-4">
                                <div class="col-auto">
                                    <svg class="icon icon-sm text-gray-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <div class="col">
                                    <div class="progress-wrapper">
                                        <div class="progress-info">
                                            <div class="h6 mb-0">Themesberg - Design System</div>
                                            <div class="small fw-bold text-gray-500">
                                                <span>60 %</span>
                                            </div>
                                        </div>
                                        <div class="progress mb-0">
                                            <div class="progress-bar bg-success" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: 60%;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row align-items-center mb-4">
                                <div class="col-auto">
                                    <svg class="icon icon-sm text-gray-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <div class="col">
                                    <div class="progress-wrapper">
                                        <div class="progress-info">
                                            <div class="h6 mb-0">Homepage Design in Figma</div>
                                            <div class="small fw-bold text-gray-500">
                                                <span>45 %</span>
                                            </div>
                                        </div>
                                        <div class="progress mb-0">
                                            <div class="progress-bar bg-warning" role="progressbar" aria-valuenow="45" aria-valuemin="0" aria-valuemax="100" style="width: 45%;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row align-items-center mb-3">
                                <div class="col-auto">
                                    <svg class="icon icon-sm text-gray-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <div class="col">
                                    <div class="progress-wrapper">
                                        <div class="progress-info">
                                            <div class="h6 mb-0">Backend for Themesberg v2</div>
                                            <div class="small fw-bold text-gray-500">
                                                <span>34 %</span>
                                            </div>
                                        </div>
                                        <div class="progress mb-0">
                                            <div class="progress-bar bg-danger" role="progressbar" aria-valuenow="34" aria-valuemin="0" aria-valuemax="100" style="width: 34%;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>--}}
            </div>
        </div>
        <div class="col-12 col-xl-4">
            <div class="col-12 px-0 mb-4">
                <div class="card border-0 shadow">
                    <div class="card-header border-bottom d-flex align-items-center justify-content-between">
                        <h2 class="fs-5 fw-bold mb-0">Total Trips</h2>
                        <small class="d-flex align-items-center">
                            {{ $totalTrips['start_date'] }} - {{ $totalTrips['end_date'] }}
                        </small>
                    </div>
                    <div class="card border-0 shadow">
                        <div class="card-body" style="background-color: #63b1bd ">
                            <div class="row d-xl-flex align-items-center">
                                <div class="text-xl-center mb-3 mb-xl-0 d-flex align-items-center justify-content-xl-center">
                                    <div>
                                        <h2 class="h5">Trips Planned</h2>
                                        <h3 class="fw-extrabold mb-1">{{ $totalTrips['trip_planned'] }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card border-0 shadow">
                        <div class="card-body" style="background-color: #3aaf85 ">
                            <div class="row d-block d-xl-flex align-items-center">
                                <div class="text-xl-center mb-3 mb-xl-0 d-flex align-items-center justify-content-xl-center">
                                    <div class="d-none d-sm-block">
                                        <h2 class="h5">Trips Made</h2>
                                        <h3 class="fw-extrabold mb-1">{{ $totalTrips['trip_made'] }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 px-0 mb-4">
                <div class="card border-0 shadow">
                    <div class="card-header border-bottom d-flex align-items-center justify-content-between">
                        <div class="d-none d-sm-block">
                            <h2 class="fs-5 fw-bold mb-0">Total Passenger by Type</h2>
                            <small class="d-flex align-items-center">
                                {{ $earlyLateTrips['start_date'] }} - {{ $earlyLateTrips['end_date'] }}
                            </small>
                        </div>
                        <div class="d-block ms-auto">
                            <div class="d-flex align-items-center text-end mb-2">
                                <span class="dot rounded-circle me-2" style="background-color: #d70206;"></span>
                                <span class="fw-normal small">Adult</span>
                            </div>
                            <div class="d-flex align-items-center text-end">
                                <span class="dot rounded-circle me-2" style="background-color: #f05b4f;"></span>
                                <span class="fw-normal small">Concession</span>
                            </div>
                        </div>
                    </div>
                    <div class="pie-chart"></div>
                </div>
            </div>
            {{--<div class="col-12 px-0 mb-4">
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between border-bottom pb-3">
                            <div>
                                <div class="h6 mb-0 d-flex align-items-center">
                                    <svg class="icon icon-xs text-gray-500 me-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM4.332 8.027a6.012 6.012 0 011.912-2.706C6.512 5.73 6.974 6 7.5 6A1.5 1.5 0 019 7.5V8a2 2 0 004 0 2 2 0 011.523-1.943A5.977 5.977 0 0116 10c0 .34-.028.675-.083 1H15a2 2 0 00-2 2v2.197A5.973 5.973 0 0110 16v-2a2 2 0 00-2-2 2 2 0 01-2-2 2 2 0 00-1.668-1.973z" clip-rule="evenodd"></path>
                                    </svg> Global Rank
                                </div>
                            </div>
                            <div>
                                <a href="#" class="d-flex align-items-center fw-bold">#755
                                    <svg class="icon icon-xs text-gray-500 ms-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                </a>
                            </div>
                        </div>
                        <div class="d-flex align-items-center justify-content-between border-bottom py-3">
                            <div>
                                <div class="h6 mb-0 d-flex align-items-center">
                                    <svg class="icon icon-xs text-gray-500 me-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M3 6a3 3 0 013-3h10a1 1 0 01.8 1.6L14.25 8l2.55 3.4A1 1 0 0116 13H6a1 1 0 00-1 1v3a1 1 0 11-2 0V6z" clip-rule="evenodd"></path>
                                    </svg> Country Rank
                                </div>
                                <div class="small card-stats">United States
                                    <svg class="icon icon-xs text-success" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <a href="#" class="d-flex align-items-center fw-bold">#32
                                    <svg class="icon icon-xs text-gray-500 ms-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                </a>
                            </div>
                        </div>
                        <div class="d-flex align-items-center justify-content-between pt-3">
                            <div>
                                <div class="h6 mb-0 d-flex align-items-center">
                                    <svg class="icon icon-xs text-gray-500 me-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1H8a3 3 0 00-3 3v1.5a1.5 1.5 0 01-3 0V6z" clip-rule="evenodd"></path>
                                        <path d="M6 12a2 2 0 012-2h8a2 2 0 012 2v2a2 2 0 01-2 2H2h2a2 2 0 002-2v-2z"></path>
                                    </svg> Category Rank
                                </div>
                                <div class="small card-stats">Computers Electronics &gt; Technology
                                    <svg class="icon icon-xs text-success" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <a href="#" class="d-flex align-items-center fw-bold">#11
                                    <svg class="icon icon-xs text-gray-500 ms-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 px-0">
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <h2 class="fs-5 fw-bold mb-1">Acquisition</h2>
                        <p>Tells you where your visitors originated from, such as search engines, social networks or website referrals.</p>
                        <div class="d-block">
                            <div class="d-flex align-items-center me-5">
                                <div class="icon-shape icon-sm icon-shape-danger rounded me-3">
                                    <svg fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11 4a1 1 0 10-2 0v4a1 1 0 102 0V7zm-3 1a1 1 0 10-2 0v3a1 1 0 102 0V8zM8 9a1 1 0 00-2 0v2a1 1 0 102 0V9z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <div class="d-block">
                                    <label class="mb-0">Bounce Rate</label>
                                    <h4 class="mb-0">33.50%</h4>
                                </div>
                            </div>
                            <div class="d-flex align-items-center pt-3">
                                <div class="icon-shape icon-sm icon-shape-purple rounded me-3">
                                    <svg fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"></path>
                                    </svg>
                                </div>
                                <div class="d-block">
                                    <label class="mb-0">Sessions</label>
                                    <h4 class="mb-0">9,567</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>--}}
        </div>
    </div>
@endsection
