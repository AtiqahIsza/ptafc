<div class="main py-4">
    <div class="d-block mb-md-0" style="position: relative">
        <h2>Manage Buses</h2>
        <button wire:click.prevent="addNew" class="buttonAdd btn btn-gray-800 d-inline-flex align-items-center me-2" >
            <i class="fa fa-plus-circle mr-1 fa-fw"></i>
            Add Bus
        </button>
        <button wire:click.prevent="extractExcel" class="buttonDownloadBus btn btn-gray-800 d-inline-flex align-items-center me-2">
            <i class="fa fa-file-download mr-1 fa-fw"></i>
            Extract to Excel
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
                    <th class="border-gray-200">{{ __('Registration Date') }}</th>
                    <th class="border-gray-200">{{ __('Age') }}</th>
                    <th class="border-gray-200">{{ __('Terminal ID') }}</th>
                    <th class="border-gray-200">{{ __('Updated At') }}</th>
                    <th class="border-gray-200">{{ __('Updated By') }}</th>
                    <th class="border-gray-200">{{ __('Status') }}</th>
                    <th class="border-gray-200">Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($buses as $bus)
                    <tr>
                        <td><span class="fw-normal">{{ $bus->bus_registration_number }}</span></td>
                        <td><span class="fw-normal">{{ $bus->bus_series_number }}</span></td>
                        <td><span class="fw-normal">{{ $bus->bus_manufacturing_date }}</span></td>
                        <td><span class="fw-normal">{{ $bus->bus_age }}</span></td>
                        @if ($bus->terminal_id != NULL)
                            <td><span class="fw-normal">{{ $bus->terminal_id }}</span></td>
                        @else
                            <td style="text-align:center"><span class="fw-normal"> - </span></td>
                        @endif
                        @if ($bus->updated_at != NULL && $bus->updated_by != NULL)
                            <td><span class="fw-normal">{{ $bus->updated_at}}</span></td>
                            <td><span class="fw-normal">{{ $bus->UpdatedBy->username}}</span></td>
                        @else
                            <td style="text-align:center"><span class="fw-normal"> - </span></td>
                            <td style="text-align:center"><span class="fw-normal"> - </span></td>
                        @endif
                        @if ($bus->status==1)
                            <td><span class="badge bg-success">ACTIVE</span></td>
                        @else
                            <td><span class="badge bg-danger">INACTIVE</span></td>
                        @endif
                        <td>
                            @if ($bus->status==1)
                                <!-- Button Modal -->
                                <button wire:click.prevent="edit({{ $bus }})" class="btn btn-warning">Edit</button>
                            @else
                                <button wire:click.prevent="confirmChanges({{ $bus->id }})" class="btn btn-primary">Activate</button>
                                {{-- <button wire:click.prevent="confirmRemoval({{ $bus->id }})" class="btn btn-danger">Remove</button> --}}
                            @endif
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
                                <input wire:model.defer="state.bus_series_number" class="form-control border-gray-300" id="seriesNum" placeholder="{{ __('Bus Series Number') }}" autofocus required>
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
                                    @foreach($editedCompanies as $editedCompany)
                                        <option value="{{$editedCompany->id}}">{{$editedCompany->company_name}}</option>
                                    @endforeach
                                </select>
                                @if ($errors->has('company'))
                                    <span class="text-danger">{{ $errors->first('company') }}</span>
                                @endif
                            </div>
                        </div>
                        @if($showEditModal==false)
                        <div class="form-group mb-4">
                            <label for="bus_manufacturing_date">Bus Manufacturing Date</label>
                            <div class="input-group">
                                <span class="input-group-text" id="basic-addon1">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-123" viewBox="0 0 16 16">
                                        <path d="M2.873 11.297V4.142H1.699L0 5.379v1.137l1.64-1.18h.06v5.961h1.174Zm3.213-5.09v-.063c0-.618.44-1.169 1.196-1.169.676 0 1.174.44 1.174 1.106 0 .624-.42 1.101-.807 1.526L4.99 10.553v.744h4.78v-.99H6.643v-.069L8.41 8.252c.65-.724 1.237-1.332 1.237-2.27C9.646 4.849 8.723 4 7.308 4c-1.573 0-2.36 1.064-2.36 2.15v.057h1.138Zm6.559 1.883h.786c.823 0 1.374.481 1.379 1.179.01.707-.55 1.216-1.421 1.21-.77-.005-1.326-.419-1.379-.953h-1.095c.042 1.053.938 1.918 2.464 1.918 1.478 0 2.642-.839 2.62-2.144-.02-1.143-.922-1.651-1.551-1.714v-.063c.535-.09 1.347-.66 1.326-1.678-.026-1.053-.933-1.855-2.359-1.845-1.5.005-2.317.88-2.348 1.898h1.116c.032-.498.498-.944 1.206-.944.703 0 1.206.435 1.206 1.07.005.64-.504 1.106-1.2 1.106h-.75v.96Z"/>
                                    </svg>
                                </span>
                                <input wire:model.defer="state.bus_manufacturing_date" type="date" class="form-control border-gray-300" id="age" autofocus>
                                @if ($errors->has('bus_manufacturing_date'))
                                    <span class="text-danger">{{ $errors->first('bus_manufacturing_date') }}</span>
                                @endif
                            </div>
                        </div>
                        @endif
                        <div class="form-group mb-4">
                            <label for="type">Type of Bus</label>
                            <div class="input-group">
                                <span class="input-group-text" id="basic-addon1">
                                    <i class="fas fa-bus-alt fa-fw"></i>
                                </span>
                                <select wire:model.defer="state.bus_type_id" id="type" class="form-control border-gray-300" autofocus>
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
                            <label for="tid">Cashless Terminal ID</label>
                            <div class="input-group">
                                 <span class="input-group-text" id="basic-addon1">
                                    <i class="fas fa-bus fa-fw"></i>
                                </span>
                                <input wire:model.defer="state.terminal_id" class="form-control border-gray-300" id="tid" placeholder="{{ __('Cashless Terminal ID') }}" autofocus required>
                                @if ($errors->has('tid'))
                                    <span class="text-danger">{{ $errors->first('tid') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="status">Status</label>
                            <div class="input-group">
                                <span class="input-group-text" id="basic-addon1">
                                    <i class="fas fa-check-circle fa-fw"></i>
                                </span>
                                <select wire:model.defer="state.status" id="status" class="form-control border-gray-300" autofocus>
                                    <option value="">Choose Status</option>
                                    <option value="1">Active</option>
                                    <option value="2">Inactive</option>
                                </select>
                                @if ($errors->has('type'))
                                    <span class="text-danger">{{ $errors->first('status') }}</span>
                                @endif
                            </div>
                        </div>
                        {{-- <div class="form-group mb-4">
                            <label for="mac">MAC Address</label>
                            <div class="input-group">
                                <span class="input-group-text" id="basic-addon1">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-laptop-fill" viewBox="0 0 16 16">
                                      <path d="M2.5 2A1.5 1.5 0 0 0 1 3.5V12h14V3.5A1.5 1.5 0 0 0 13.5 2h-11zM0 12.5h16a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 12.5z"/>
                                    </svg>
                                </span>
                                <input wire:model.defer="state.mac_address" class="form-control border-gray-300" id="mac" placeholder="{{ __('MAC Address') }}" autofocus>
                                @if ($errors->has('mac'))
                                    <span class="text-danger">{{ $errors->first('mac') }}</span>
                                @endif
                            </div>
                        </div> --}}

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

    <!-- Change Status Route Modal -->
    <div wire:ignore.self class="modal fade" id="modalActivated" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5>Confirmation For Changing Status</h5>
                </div>

                <div class="modal-body">
                    <h4>Are you sure you want to activate {{ $activatedBus }}?</h4>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa fa-times mr-1"></i>Cancel</button>
                    <button type="button" wire:click.prevent="activateBus" class="btn btn-danger"><i class="fa fa-pen mr-1"></i>Activate</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End of Change Status Modal Content -->

    <!-- Remove User Modal -->
    <div wire:ignore.self class="modal fade" id="confirmationModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5>Remove Bus</h5>
                </div>

                <div class="modal-body">
                    <h4>Are you sure you want to remove {{ $removedBus }}?</h4>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa fa-times mr-1"></i> Cancel</button>
                    <button type="button" wire:click.prevent="removeBus" class="btn btn-danger"><i class="fa fa-trash mr-1"></i>Remove Bus</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End of Remove User Modal Content -->
</div>
@push('script')
    <script>
        window.addEventListener('show-form', event => {
            $('#modalEdit').modal('show');
        });
        window.addEventListener('hide-form-edit', event => {
            $('#modalEdit').modal('hide');
            toastr.success(event.detail.message, 'Bus updated successfully!');
        });
        window.addEventListener('hide-form-add', event => {
            $('#modalEdit').modal('hide');
            toastr.success(event.detail.message, 'New bus added successfully!');
        });
        window.addEventListener('hide-form-failed', event => {
            $('#modalEdit').modal('hide');
            toastr.error(event.detail.message, 'Operation Failed!');
        });
        window.addEventListener('hide-form-existed-bus', event => {
            $('#modalEdit').modal('hide');
            toastr.error(event.detail.message, 'Bus Registration Number already exist!');
        });

        window.addEventListener('show-activated-modal', event => {
            $('#modalActivated').modal('show');
        });
        window.addEventListener('hide-activated-modal', event => {
            $('#modalActivated').modal('hide');
            toastr.success(event.detail.message, 'Bus activated successfully!');
        })

        window.addEventListener('show-delete-modal', event => {
            $('#confirmationModal').modal('show');
        });
        window.addEventListener('hide-delete-modal', event => {
            $('#confirmationModal').modal('hide');
            toastr.success(event.detail.message, 'Bus removed successfully!');
        })
    </script>
@endpush
