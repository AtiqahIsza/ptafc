<div class="main py-4">
    <div class="d-block mb-md-0" style="position: relative">
        <h2>Manage Buses</h2>
        <button wire:click.prevent="addNew" class="buttonAdd btn btn-gray-800 d-inline-flex align-items-center me-2" data-bs-toggle="modal" data-bs-target="#modalEdit">
            <i class="fa fa-plus-circle mr-1 fa-fw"></i>
            Add Bus
        </button>
    </div>
    <div class="col-9 col-lg-8 d-md-flex">
        <select wire:model="selectedCompany" class="form-select fmxw-200 d-none d-md-inline"  >
            <option value="">Choose Company</option>
            @foreach($companies as $company)
                <option value="{{$company->id}}">{{$company->company_name}}</option>
            @endforeach
        </select>
    </div>
    <br>

    @if (!is_null($selectedCompany))
        <div class="card card-body border-0 shadow table-wrapper table-responsive">
            <h2 class="mb-4 h5">{{ __('All Buses By Company') }}</h2>
            <table class="table table-hover">
                <thead>
                <tr>
                    <th class="border-gray-200">{{ __('Registration Number') }}</th>
                    <th class="border-gray-200">{{ __('Series Number') }}</th>
                    <th class="border-gray-200">{{ __('Route') }}</th>
                    <th class="border-gray-200">{{ __('Sector') }}</th>
                    <th class="border-gray-200">Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($buses as $bus)
                    <tr>
                        <td><span class="fw-normal">{{ $bus->bus_registration_number }}</span></td>
                        <td><span class="fw-normal">{{ $bus->bus_series_number }}</span></td>
                        <td><span class="fw-normal">{{ $bus->route->route_name }}</span></td>
                        <td><span class="fw-normal">{{ $bus->sector->sector_name}}</span></td>
                        <td>
                            <!-- Button Modal -->
                            <button wire:click.prevent="edit({{ $bus }})" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modalEdit">Edit</button>
                            <button wire:click.prevent="confirmRemoval({{ $bus->id }})" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirmationModal">Remove</button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <div class="card-footer px-3 border-0 d-flex flex-column flex-lg-row align-items-center justify-content-between">
                {{--{{ $users->links() }}--}}
            </div>
        </div>
    @endif

    <!-- Edit/Create Modal Content -->
    <div wire:ignore.self class="modal fade" id="modalEdit" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-md-5">
                    <h2 class="h4 text-center">
                        @if($showEditModal)
                            <span>Edit Bus Details</span>
                        @else
                            <span>Add New Bus</span>
                        @endif
                    </h2>

                    <!-- Form -->
                    <form wire:submit.prevent="{{ $showEditModal ? 'updateBus' : 'createBus' }}">
                        @csrf
                        <div class="form-group mb-4">
                            <label for="regNum">Registration Number</label>
                            <div class="input-group">
                                 <span class="input-group-text" id="basic-addon1">
                                    <i class="fas fa-bus fa-fw"></i>
                                </span>
                                <input wire:model.defer="state.bus_registration_number" class="form-control border-gray-300" id="regNum" placeholder="{{ __('Bus Registration Name') }}" autofocus required>
                                @if ($errors->has('regNum'))
                                    <span class="text-danger">{{ $errors->first('regNum') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="seriesNum">Series Number</label>
                            <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-123" viewBox="0 0 16 16">
                                        <path d="M2.873 11.297V4.142H1.699L0 5.379v1.137l1.64-1.18h.06v5.961h1.174Zm3.213-5.09v-.063c0-.618.44-1.169 1.196-1.169.676 0 1.174.44 1.174 1.106 0 .624-.42 1.101-.807 1.526L4.99 10.553v.744h4.78v-.99H6.643v-.069L8.41 8.252c.65-.724 1.237-1.332 1.237-2.27C9.646 4.849 8.723 4 7.308 4c-1.573 0-2.36 1.064-2.36 2.15v.057h1.138Zm6.559 1.883h.786c.823 0 1.374.481 1.379 1.179.01.707-.55 1.216-1.421 1.21-.77-.005-1.326-.419-1.379-.953h-1.095c.042 1.053.938 1.918 2.464 1.918 1.478 0 2.642-.839 2.62-2.144-.02-1.143-.922-1.651-1.551-1.714v-.063c.535-.09 1.347-.66 1.326-1.678-.026-1.053-.933-1.855-2.359-1.845-1.5.005-2.317.88-2.348 1.898h1.116c.032-.498.498-.944 1.206-.944.703 0 1.206.435 1.206 1.07.005.64-.504 1.106-1.2 1.106h-.75v.96Z"/>
                                    </svg>
                                </span>
                                <input wire:model.defer="state.bus_series_number" class="form-control border-gray-300" id="seriesNum" placeholder="{{ __('Bus Registration Number') }}" autofocus required>
                                @if ($errors->has('seriesNum'))
                                    <span class="text-danger">{{ $errors->first('seriesNum') }}</span>
                                @endif
                               </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="company">Company</label>
                            <div class="input-group">
                                <span class="input-group-text" id="basic-addon1">
                                    <i class="fas fa-building fa-fw"></i>
                                </span>
                                <select wire:model.defer="state.company_id" id="company" class="form-control border-gray-300" autofocus required>
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
                            <label for="sector">Sector</label>
                            <div class="input-group">
                                <span class="input-group-text" id="basic-addon1">
                                   <i class="fa fa-project-diagram"></i>
                                </span>
                                <select wire:model.defer="state.sector_id" id="sector" class="form-control border-gray-300" autofocus required>
                                    <option value="">Choose Sector</option>
                                    @foreach($sectors as $sector)
                                        <option value="{{$sector->id}}">{{$sector->sector_name}}</option>
                                    @endforeach
                                </select>
                                @if ($errors->has('sector'))
                                    <span class="text-danger">{{ $errors->first('sector') }}</span>
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
                                <select wire:model.defer="state.route_id" id="route" class="form-control border-gray-300" autofocus required>
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
                            <label for="age">Age of Bus</label>
                            <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-123" viewBox="0 0 16 16">
                                        <path d="M2.873 11.297V4.142H1.699L0 5.379v1.137l1.64-1.18h.06v5.961h1.174Zm3.213-5.09v-.063c0-.618.44-1.169 1.196-1.169.676 0 1.174.44 1.174 1.106 0 .624-.42 1.101-.807 1.526L4.99 10.553v.744h4.78v-.99H6.643v-.069L8.41 8.252c.65-.724 1.237-1.332 1.237-2.27C9.646 4.849 8.723 4 7.308 4c-1.573 0-2.36 1.064-2.36 2.15v.057h1.138Zm6.559 1.883h.786c.823 0 1.374.481 1.379 1.179.01.707-.55 1.216-1.421 1.21-.77-.005-1.326-.419-1.379-.953h-1.095c.042 1.053.938 1.918 2.464 1.918 1.478 0 2.642-.839 2.62-2.144-.02-1.143-.922-1.651-1.551-1.714v-.063c.535-.09 1.347-.66 1.326-1.678-.026-1.053-.933-1.855-2.359-1.845-1.5.005-2.317.88-2.348 1.898h1.116c.032-.498.498-.944 1.206-.944.703 0 1.206.435 1.206 1.07.005.64-.504 1.106-1.2 1.106h-.75v.96Z"/>
                                    </svg>
                                </span>
                                </span>
                                <input wire:model.defer="state.bus_age" class="form-control border-gray-300" id="age" placeholder="{{ __('Age of Bus') }}" autofocus required>
                                @if ($errors->has('age'))
                                    <span class="text-danger">{{ $errors->first('age') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="type">Type of Bus</label>
                            <div class="input-group">
                                <span class="input-group-text" id="basic-addon1">
                                    <i class="fas fa-bus-alt fa-fw"></i>
                                </span>
                                <select wire:model.defer="state.bus_type_id" id="type" class="form-control border-gray-300" autofocus required>
                                    <option value="">Choose Bus Type</option>
                                    @foreach($busTypes as $busType)
                                        <option value="{{$busType->id}}">{{$busType->type}}</option>
                                    @endforeach
                                </select>
                                @if ($errors->has('type'))
                                    <span class="text-danger">{{ $errors->first('type') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="mac">MAC Address</label>
                            <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-laptop-fill" viewBox="0 0 16 16">
                                      <path d="M2.5 2A1.5 1.5 0 0 0 1 3.5V12h14V3.5A1.5 1.5 0 0 0 13.5 2h-11zM0 12.5h16a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 12.5z"/>
                                    </svg>
                                </span>
                                <input wire:model.defer="state.mac_address" class="form-control border-gray-300" id="mac" placeholder="{{ __('MAC Address') }}" autofocus required>
                                @if ($errors->has('mac'))
                                    <span class="text-danger">{{ $errors->first('mac') }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                @if($showEditModal)
                                    <span>Save Changes</span>
                                @else
                                    <span>Save</span>
                                @endif
                            </button>
                        </div>
                    </form>
                </div>
                <div class="modal-header"></div>
            </div>
        </div>
    </div>
    <!-- End of Edit User Modal Content -->

    <!-- Remove User Modal -->
    <div wire:ignore.self class="modal fade" id="confirmationModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5>Remove Sector</h5>
                </div>

                <div class="modal-body">
                    <h4>Are you sure you want to remove this sector?</h4>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa fa-times mr-1"></i> Cancel</button>
                    <button type="button" wire:click.prevent="removeBus" class="btn btn-danger"><i class="fa fa-trash mr-1"></i>Remove Sector</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End of Remove User Modal Content -->
</div>
@section('script')
    <script>
        window.addEventListener('show-form', event => {
            $('#modalEdit').modal('show');
        });
        window.addEventListener('hide-form', event => {
            $('#modalEdit').modal('hide');
            toastr.success(event.detail.message, 'Success!');
        });
        window.addEventListener('show-delete-form', event => {
            $('#confirmationModal').modal('show');
        });
        window.addEventListener('hide-delete-modal', event => {
            $('#confirmationModal').modal('hide');
            toastr.success(event.detail.message, 'Success!');
        })
    </script>
@endsection
