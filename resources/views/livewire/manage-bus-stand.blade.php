<div class="main py-4">
    <div class="d-block mb-md-0" style="position: relative">
        <h2>Manage Bus Stand</h2>
        {{--<button wire:click.prevent="addNew" class="buttonAdd btn btn-gray-800 d-inline-flex align-items-center me-2" data-bs-toggle="modal" data-bs-target="#modalAdd">
            <i class="fa fa-plus-circle mr-1 fa-fw"></i>
            Add Bus Stand
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
        @endif
        {{--@if (!is_null($selectedRoute))
            <select wire:model="selectedStage" class="form-select fmxw-200 d-none d-md-inline"  >
                <option value="">Choose Stage</option>
                @foreach($stages as $stage)
                    <option value="{{$stage->id}}">{{$stage->stage_name}}</option>
                @endforeach
            </select>
        @endif
        @endif--}}
    </div>

    @if (!is_null($selectedRoute))
        <br>
        <div class="card card-body border-0 shadow table-wrapper table-responsive">
            <div class="d-block mb-md-0" style="position: relative">
                <h2 class="mb-4 h5">{{ __('All Bus Stands By Company, Route and Stage') }}</h2>
                @if($routeMap)
                <button onclick="window.location='{{ route('addBusStand', $selectedRoute) }}'" class="buttonAdd btn btn-gray-800 d-inline-flex align-items-center me-2">
                    <i class="fa fa-plus-circle mr-1 fa-fw"></i>
                    Add Bus Stand
                </button>
                @else
                    <span style="float:right; color: red">**Add Route Map first**</span>
                @endif
                @if($removeBtn)
                    <button onclick="window.location='{{ route('viewBusStand', $selectedRoute) }}'" class="buttonAdd-map btn btn-success d-inline-flex align-items-center me-2">
                        <i class="fa fa-bus mr-1 fa-fw"></i>
                        View Bus Stand
                    </button>
                    <button wire:click.prevent="confirmRemoval({{ $selectedRoute }})" class="buttonAdd btn btn-red-200 d-inline-flex align-items-center me-2" data-bs-toggle="modal" data-bs-target="#confirmationModal">
                        <i class="fa fa-minus-circle mr-1 fa-fw"></i>
                        Remove Bus Stand
                    </button>
                @endif
            </div>
            <table class="table table-hover">
                <thead>
                <tr>
                    <th class="border-gray-200">{{ __('Sequence') }}</th>
                    <th class="border-gray-200">{{ __('Latitude') }}</th>
                    <th class="border-gray-200">{{ __('Longitude') }}</th>
                    <th class="border-gray-200">{{ __('Radius') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($busStands as $busStand)
                    <tr>
                        <td><span class="fw-normal">{{ $busStand->sequence }}</span></td>
                        <td><span class="fw-normal">{{ $busStand->latitude }}</span></td>
                        <td><span class="fw-normal">{{ $busStand->longitude }}</span></td>
                        <td><span class="fw-normal">{{ $busStand->radius }}</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <div class="card-footer px-3 border-0 d-flex flex-column flex-lg-row align-items-center justify-content-between">
                {{--{{ $users->links() }}--}}
            </div>
        </div>
    @endif

    <!-- Remove Bus Stand Modal -->
    <div wire:ignore.self class="modal fade" id="confirmationModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5>Remove Bus Stand</h5>
                </div>

                <div class="modal-body">
                    <h4>Are you sure you want to remove these bus stand?</h4>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa fa-times mr-1"></i> Cancel</button>
                    <button type="button" wire:click.prevent="removeSector" class="btn btn-danger" data-bs-dismiss="modal"><i class="fa fa-trash mr-1"></i>Remove Bus Stand</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End of Remove User Modal Content -->

</div>
