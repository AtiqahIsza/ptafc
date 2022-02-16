<div class="main py-4">
    <div class="d-block mb-md-0" style="position: relative">
        <h2>Manage Bus Stand</h2>
        {{--<button wire:click.prevent="addNew" class="buttonAdd btn btn-gray-800 d-inline-flex align-items-center me-2" data-bs-toggle="modal" data-bs-target="#modalAdd">
            <i class="fa fa-plus-circle mr-1 fa-fw"></i>
            Add Bus Stand
        </button>--}}
        <button onclick="window.location='{{ route('addBusStand') }}'" class="buttonAdd btn btn-gray-800 d-inline-flex align-items-center me-2">
            <i class="fa fa-plus-circle mr-1 fa-fw"></i>
            Add New Bus Stand
        </button>
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
</div>
