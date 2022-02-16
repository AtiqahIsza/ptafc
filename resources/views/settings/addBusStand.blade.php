@extends('layouts.app')

@section('content')
    <script
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCGDHu1sOYoepvEmSLmatyJVGNvCCONh48&callback=initMap&libraries=&v=weekly&channel=2"
        async></script>
    <script>
        // Initialize and add the map
        function initMap() {
            // Center the map on Malaysia.
            const uluru = { lat: 3.140853, lng: 101.693207 }
            // The map, centered at Uluru
            const map = new google.maps.Map(document.getElementById("map"), {
                zoom: 4,
                center: uluru,
            });
            // The marker, positioned at Uluru
            const marker = new google.maps.Marker({
                position: uluru,
                map: map,
            });
        }
    </script>

    <div class="row">
        @if (session()->has('message'))
            <div class="alert alert-success">
                {{ session('message') }}
            </div>
        @endif
        <div class="main py-4">
            <div class="d-block mb-md-0" style="position: relative">
                <h2>Add Bus Stand</h2>
            </div>
            <div class="card card-body border-0 shadow table-wrapper table-responsive">
                <!-- Form -->
                <form wire:submit.prevent="createBusStand">
                    @csrf
                    <div class="form-group mb-4">
                        <label for="company">Company</label>
                        <div class="input-group">
                                <span class="input-group-text" id="basic-addon1">
                                    <i class="fas fa-building fa-fw"></i>
                                </span>
                            <select id="company" class="form-select border-gray-300" autofocus required>
                                <option value="">Choose Company</option>
                                @foreach($companies as $company)
                                    <option value="{{$company->id}}">{{$company->company_name}}</option>
                                @endforeach
                            </select>
                            @if ($errors->has('company'))
                                <span class="text-danger">{{ $errors->first('company') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="form-group mb-4">
                        <label for="route">Route</label>
                        <div class="input-group">
                                <span class="input-group-text" id="basic-addon1">
                                     <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pin-map-fill" viewBox="0 0 16 16">
                                        <path fill-rule="evenodd" d="M3.1 11.2a.5.5 0 0 1 .4-.2H6a.5.5 0 0 1 0 1H3.75L1.5 15h13l-2.25-3H10a.5.5 0 0 1 0-1h2.5a.5.5 0 0 1 .4.2l3 4a.5.5 0 0 1-.4.8H.5a.5.5 0 0 1-.4-.8l3-4z"/>
                                        <path fill-rule="evenodd" d="M4 4a4 4 0 1 1 4.5 3.969V13.5a.5.5 0 0 1-1 0V7.97A4 4 0 0 1 4 3.999z"/>
                                    </svg>
                                </span>
                            <select id="route" class="form-select border-gray-300" autofocus required>
                                <option value="">Choose Route</option>
                                @foreach($routes as $route)
                                    <option value="{{$route->id}}">{{$route->route_name}}</option>
                                @endforeach
                            </select>
                            @if ($errors->has('route'))
                                <span class="text-danger">{{ $errors->first('route') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="form-group mb-4">
                        <label for="route">Stage</label>
                        <div class="input-group">
                                <span class="input-group-text" id="basic-addon1">
                                     <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pin-map-fill" viewBox="0 0 16 16">
                                        <path fill-rule="evenodd" d="M3.1 11.2a.5.5 0 0 1 .4-.2H6a.5.5 0 0 1 0 1H3.75L1.5 15h13l-2.25-3H10a.5.5 0 0 1 0-1h2.5a.5.5 0 0 1 .4.2l3 4a.5.5 0 0 1-.4.8H.5a.5.5 0 0 1-.4-.8l3-4z"/>
                                        <path fill-rule="evenodd" d="M4 4a4 4 0 1 1 4.5 3.969V13.5a.5.5 0 0 1-1 0V7.97A4 4 0 0 1 4 3.999z"/>
                                    </svg>
                                </span>
                            <select id="stage" class="form-select border-gray-300" autofocus required>
                                <option value="">Choose Stage</option>
                                @foreach($stages as $stage)
                                    <option value="{{$stage->id}}">{{$stage->stage_name}}</option>
                                @endforeach
                            </select>
                            @if ($errors->has('stage'))
                                <span class="text-danger">{{ $errors->first('stage') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="form-group mb-4">
                        <label for="route">Plot Bus Stand</label>
                        <div id="map"></div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <span>Save</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

