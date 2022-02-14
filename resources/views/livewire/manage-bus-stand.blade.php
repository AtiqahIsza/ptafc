<div class="main py-4">
    <div class="d-block mb-md-0" style="position: relative">
        <h2>Manage Bus Stand</h2>
        <button wire:click.prevent="addNew" class="buttonAdd btn btn-gray-800 d-inline-flex align-items-center me-2" data-bs-toggle="modal" data-bs-target="#modalAdd">
            <i class="fa fa-plus-circle mr-1 fa-fw"></i>
            Add Bus Stand
        </button>
        {{--<button onclick="window.location='{{ route('addBusStandMap') }}'" class="btn btn-primary">Create</button>
            <i class="fa fa-plus-circle mr-1 fa-fw"></i>
            Add New Bus Stand
        </button>--}}
    </div>
    <div class="col-9 col-lg-8 d-md-flex">
        <select wire:model="selectedCompany" class="form-select fmxw-200 d-none d-md-inline"  >
            <option value="">Choose Company</option>
            @foreach($companies as $company)
                <option value="{{$company->id}}">{{$company->company_name}}</option>
            @endforeach
        </select>

        @if (!is_null($selectedCompany))
        <select wire:model="selectedRoute" class="form-select fmxw-200 d-none d-md-inline"  >
            <option value="">Choose Route</option>
            @foreach($routes as $route)
                <option value="{{$route->id}}">{{$route->route_name}}</option>
            @endforeach
        </select>

        @if (!is_null($selectedRoute))
            <select wire:model="selectedStage" class="form-select fmxw-200 d-none d-md-inline"  >
                <option value="">Choose Stage</option>
                @foreach($stages as $stage)
                    <option value="{{$stage->id}}">{{$stage->stage_name}}</option>
                @endforeach
            </select>
        @endif
        @endif
    </div>

    @if (!is_null($selectedStage))
        <br>
        <div class="card card-body border-0 shadow table-wrapper table-responsive">
            <h2 class="mb-4 h5">{{ __('All Bus Stands By Company, Route and Stage') }}</h2>
            <table class="table table-hover">
                <thead>
                <tr>
                    <th class="border-gray-200">{{ __('ID') }}</th>
                    <th class="border-gray-200">{{ __('Sequence Number') }}</th>
                    <th class="border-gray-200">{{ __('Description') }}</th>
                    <th class="border-gray-200">{{ __('Latitute') }}</th>
                    <th class="border-gray-200">{{ __('Longitude') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($busStands as $busStand)
                    <tr>
                        <td><span class="fw-normal">{{ $busStand->id }}</span></td>
                        <td><span class="fw-normal">{{ $busStand->sequence }}</span></td>
                        <td><span class="fw-normal">{{ $busStand->description }}</span></td>
                        <td><span class="fw-normal">{{ $busStand->latitude }}</span></td>
                        <td><span class="fw-normal">{{ $busStand->longitude }}</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <div class="card-footer px-3 border-0 d-flex flex-column flex-lg-row align-items-center justify-content-between">
                {{--{{ $users->links() }}--}}
            </div>
        </div>
    @endif

    <!-- Create Modal Content -->
    <div wire:ignore.self class="modal fade" id="modalAdd" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-md-5">
                    <h2 class="h4 text-center">
                        <span>Add New Bus Stand</span>
                    </h2>

                    <!-- Form -->
                    <form wire:submit.prevent="createBusStand">
                        @csrf
                        <div class="form-group mb-4">
                            <label for="company">Company</label>
                            <div class="input-group">
                                <span class="input-group-text" id="basic-addon1">
                                    <i class="fas fa-building fa-fw"></i>
                                </span>
                                <select wire:model.defer="state.company_id" id="company" class="form-select border-gray-300" autofocus required>
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
                                <select wire:model.defer="state.route_id" id="route" class="form-select border-gray-300" autofocus required>
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
                <div class="modal-header"></div>
            </div>
        </div>
    </div>
    <!-- End of Edit User Modal Content -->

</div>
@section('script')
    <script
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyB41DRUbKWJHPxaFjMAwdrzWzbVKartNGg&callback=initMap&v=weekly&channel=2"
        async></script>
    <script>
        // This example creates a 2-pixel-wide red polyline showing the path of
        // the first trans-Pacific flight between Oakland, CA, and Brisbane,
        // Australia which was made by Charles Kingsford Smith.
        function initMap() {
            const map = new google.maps.Map(document.getElementById("map"), {
                zoom: 3,
                center: { lat: 0, lng: -180 },
                mapTypeId: "terrain",
            });
            const flightPlanCoordinates = [
                { lat: 37.772, lng: -122.214 },
                { lat: 21.291, lng: -157.821 },
                { lat: -18.142, lng: 178.431 },
                { lat: -27.467, lng: 153.027 },
            ];
            const flightPath = new google.maps.Polyline({
                path: flightPlanCoordinates,
                geodesic: true,
                strokeColor: "#FF0000",
                strokeOpacity: 1.0,
                strokeWeight: 2,
            });

            flightPath.setMap(map);
        }
    </script>
@endsection
