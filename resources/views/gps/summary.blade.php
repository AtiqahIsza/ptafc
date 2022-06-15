@extends('layouts.app')

@section('content')
    <div class="row">
        @if (session()->has('message'))
            <div class="alert alert-success">
                {{ session('message') }}
            </div>
        @endif
        <div class="main py-4">
            <div class="d-block mb-md-0" style="position: relative">
                <h2>Vehicle Location Summary</h2>
            </div>
            <div class="container-summary">
                <div class="one">
                    <div class="card border-bottom border-start shadow">
                        <div class="card-body" style="background-color: #ffffff ">
                            <div class="container-content">
                                <div class="one-content icon-shape icon-shape-purple rounded me-4 ">
                                    <i class="fas fa-bus fa-fw" style="100%"></i>
                                </div>
                                <div class="two-content">
                                    <h2 class="h5" style="color:rgb(137, 39, 140)">All</h2>
                                    <h3 class="fw-extrabold mb-1">{{ $allBus }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="two">
                    <div class="card border-bottom border-start shadow">
                        <div class="card-body" style="background-color: #ffffff  ">
                            <div class="container-content">
                                <div class="one-content icon-shape icon-shape-secondary rounded me-4 me-sm-0">
                                    <i class="fas fa-bus fa-fw" style="width:22px;height:22px"></i>
                                </div>
                                <div class="two-content d-none d-sm-block">
                                    <h2 class="h5" style="color:rgb(221, 36, 36)"> Stationary</h2>
                                    <h3 class="fw-extrabold mb-1">{{ $stationaryBus }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="container-summary">
                <div class="one">
                    <div class="card border-bottom border-start shadow">
                        <div class="card-body" style="background-color: #ffffff  ">
                            <div class="container-content">
                                <div class="one-content icon-shape icon-shape-success rounded me-4">
                                    <i class="fas fa-bus fa-fw" style="width:22px;height:22px"></i>
                                </div>
                                <div class="two-content d-none d-sm-block">
                                    <h2 class="h5" style="color:rgb(26, 191, 76)">Online</h2>
                                    <h3 class="fw-extrabold mb-1">{{ $onlineBus }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="two">
                    <div class="card border-bottom border-start shadow">
                        <div class="card-body" style="background-color: #ffffff  ">
                            <div class="container-content">
                                <div class="one-content icon-shape icon-shape-primary rounded me-4 me-sm-0">
                                    <i class="fas fa-bus fa-fw" style="width:22px;height:22px"></i>
                                </div>
                                <div class="two-content d-none d-sm-block">
                                    <h2 class="h5" style="black">Offline</h2>
                                    <h3 class="fw-extrabold mb-1">{{ $offlineBus }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div
        class="card-footer px-3 border-0 d-flex flex-column flex-lg-row align-items-center justify-content-between">
    </div>
@endsection
