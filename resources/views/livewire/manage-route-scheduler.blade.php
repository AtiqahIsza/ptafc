<div class="main py-4">
    <div class="d-block mb-md-0" style="position: relative">
        <h2>Manage Route Schedule</h2>
        <button wire:click.prevent="addNew" class="buttonAdd btn btn-gray-800 d-inline-flex align-items-center me-2">
            <i class="fa fa-plus-circle mr-1 fa-fw"></i>
            Add Route Schedule
        </button>
    </div>
    <div class="d-block mb-md-0" style="position: relative">
        <select wire:model="selectedCompany"  class="form-select fmxw-200 d-none d-md-inline">
            <option value="">Choose Company</option>
            @foreach($companies as $company)
                <option value="{{$company->id}}">{{$company->company_name}}</option>
            @endforeach
        </select>

        <select wire:model="selectedRoute"  class="form-select fmxw-200 d-none d-md-inline">
            <option value="">Choose Route</option>
            @foreach($routes as $route)
                <option value="{{$route->id}}">{{$route->route_name}}</option>
            @endforeach
        </select>
    </div>
    <br>

    <div class="card card-body border-0 shadow table-wrapper table-responsive">
        <h2 class="mb-4 h5">{{ __('All Schedule by Route') }}</h2>
    
        <table class="table table-hover">
            <thead>
            <tr>
                <th class="border-gray-200">{{ __('No') }}</th>
                <th class="border-gray-200">{{ __('Start Time') }}</th>
                <th class="border-gray-200">{{ __('End Time') }}</th>
                <th class="border-gray-200">{{ __('Route Name') }}</th>
                <th class="border-gray-200">{{ __('Bus Reg No.') }}</th>
                <th class="border-gray-200">{{ __('Trip Type') }}</th>
                <th class="border-gray-200">{{ __('Trip Code') }}</th>
                <th class="border-gray-200">{{ __('Status') }}</th>
                <th class="border-gray-200">Action</th>
            </tr>
            </thead>

            @if (!is_null($selectedRoute))
                <tbody>
                @php $i=1; @endphp
                @foreach ($schedules as $schedule)
                    <tr>
                        <td><span class="fw-normal">{{ $i++ }}</span></td>
                        <td><span class="fw-normal">{{ $schedule->schedule_start_time }}</span></td>
                        <td><span class="fw-normal">{{ $schedule->schedule_end_time }}</span></td>
                        <td><span class="fw-normal">{{ $schedule->route->route_name }}</span></td>

                        @if($schedule->bus_id != NULL)
                            <td><span class="fw-normal">{{ $schedule->Bus->bus_registration_number}}</span></td>
                        @else
                            <td><span class="fw-normal">No Assigned Bus</span></td>
                        @endif

                        @if($schedule->trip_type==1)
                            <td><span class="fw-normal">WEEKDAY</span></td>
                        @elseif($schedule->trip_type==2)
                            <td><span class="fw-normal">WEEKEND</span></td>
                        @elseif($schedule->trip_type==3)
                            <td><span class="fw-normal">ALL DAY</span></td>
                        @elseif($schedule->trip_type==4)
                            <td><span class="fw-normal">ALL DAY (Except Friday)</span></td>
                        @elseif($schedule->trip_type==5)
                            <td><span class="fw-normal">ALL DAY (Except Sunday)</span></td>
                        @elseif($schedule->trip_type==6)
                            <td><span class="fw-normal">MON - THUR</span></td>
                        @elseif($schedule->trip_type==7)
                            <td><span class="fw-normal">FRIDAY Only</span></td>
                        @elseif($schedule->trip_type==8)
                            <td><span class="fw-normal">SATURDAY Only</span></td>
                        @elseif($schedule->trip_type==9)
                            <td><span class="fw-normal">ALL DAY (Except Friday & Sunday)</span></td>
                        @elseif($schedule->trip_type==10)
                            <td><span class="fw-normal">ALL DAY (Except Friday & Saturday)</span></td>
                        @elseif($schedule->trip_type==11)
                            <td><span class="fw-normal">SUNDAY Only</span></td>
                        @else
                            <td><span class="fw-normal">FRI & SAT</span></td>
                        @endif

                        @if($schedule->trip_code==1)
                            <td><span class="fw-normal">INBOUND</span></td>
                        @else
                            <td><span class="fw-normal">OUTBOUND</span></td>
                        @endif

                        @if($schedule->status==1)
                            <td><span class="fw-normal">ENABLED</span></td>
                        @else
                            <td><span class="fw-normal">DISABLED</span></td>
                        @endif
                        <td>
                            <!-- Button Modal -->
                            <button wire:click.prevent="edit({{ $schedule }})" class="btn btn-warning">Edit</button>
                            {{-- <button wire:click.prevent="confirmChanges({{ $schedule->id }}, {{ $schedule->status }})" class="btn btn-primary">Change Status</button> --}}
                            {{-- <button wire:click.prevent="confirmRemoval({{ $schedule->id }})" class="btn btn-danger"><i class="fas fa-trash fa-fw"></i></button> --}}
                        </td>
                    </tr>
                @endforeach
                </tbody>
            @endif
        </table>
    </div>

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
                            <span>Edit Route Schedule</span>
                        @else
                            <span>Add New Route Schedule</span>
                        @endif
                    </h2>

                    <!-- Form -->
                    <form wire:submit.prevent="{{ $showEditModal ? 'updateRouteSchedule' : 'addRouteSchedule' }}">
                        @csrf
                        <div class="form-group mb-4">
                            <label for="schedule_time">Start Time</label>
                            <div class="input-group">
                            <span class="input-group-text border-gray-300" id="basic-addon3">
                                <i class="fas fa-clock fa-fw"></i>
                            </span>
                                <input wire:model.defer="state.schedule_start_time" class="form-control border-gray-300" type="time" autofocus required>
                                @if ($errors->has('schedule_start_time'))
                                    <span class="text-danger">{{ $errors->first('schedule_start_time') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="schedule_time">End Time</label>
                            <div class="input-group">
                            <span class="input-group-text border-gray-300" id="basic-addon3">
                                <i class="fas fa-clock fa-fw"></i>
                            </span>
                                <input wire:model.defer="state.schedule_end_time" class="form-control border-gray-300" type="time" autofocus required>
                                @if ($errors->has('schedule_end_time'))
                                    <span class="text-danger">{{ $errors->first('schedule_end_time') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="company_id">Company Name</label>
                            <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <i class="fas fa-building fa-fw"></i>
                                </span>
                                <select wire:model="selectedEditCompany" class="form-select border-gray-300" autofocus required>
                                    <option value="">Choose Company</option>
                                    @foreach($editedCompanies as $editedCompany)
                                        <option value="{{$editedCompany->id}}">{{$editedCompany->company_name}}</option>
                                    @endforeach
                                </select>                                
                                @if ($errors->has('company_id'))
                                    <span class="text-danger">{{ $errors->first('company_id') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="route_id">Route Name</label>
                            <div class="input-group">
                            <span class="input-group-text border-gray-300" id="basic-addon3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pin-map-fill" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd" d="M3.1 11.2a.5.5 0 0 1 .4-.2H6a.5.5 0 0 1 0 1H3.75L1.5 15h13l-2.25-3H10a.5.5 0 0 1 0-1h2.5a.5.5 0 0 1 .4.2l3 4a.5.5 0 0 1-.4.8H.5a.5.5 0 0 1-.4-.8l3-4z"/>
                                    <path fill-rule="evenodd" d="M4 4a4 4 0 1 1 4.5 3.969V13.5a.5.5 0 0 1-1 0V7.97A4 4 0 0 1 4 3.999z"/>
                                </svg>
                            </span>
                                <select wire:model.defer="state.route_id" class="form-select border-gray-300" autofocus required>
                                    <option value="">Choose Route</option>
                                    @foreach($editedRoutes as $editedRoute)
                                        <option value="{{$editedRoute->id}}">{{$editedRoute->route_name}}</option>
                                    @endforeach
                                </select>
                                @if ($errors->has('route_id'))
                                    <span class="text-danger">{{ $errors->first('route_id') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="inbound_bus_id">Trip Code</label>
                            <div class="input-group">
                                <span class="input-group-text" id="basic-addon1">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-signpost-2-fill" viewBox="0 0 16 16">
                                        <path d="M7.293.707A1 1 0 0 0 7 1.414V2H2a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h5v1H2.5a1 1 0 0 0-.8.4L.725 8.7a.5.5 0 0 0 0 .6l.975 1.3a1 1 0 0 0 .8.4H7v5h2v-5h5a1 1 0 0 0 1-1V8a1 1 0 0 0-1-1H9V6h4.5a1 1 0 0 0 .8-.4l.975-1.3a.5.5 0 0 0 0-.6L14.3 2.4a1 1 0 0 0-.8-.4H9v-.586A1 1 0 0 0 7.293.707z"/>
                                    </svg>
                                </span>
                                <select wire:model.defer="state.trip_code" class="form-select border-gray-300" autofocus required>
                                    <option value="">Choose Trip Code</option>
                                    <option value="0">OUTBOUND</option>
                                    <option value="1">INBOUND</option>
                                </select>
                                @if ($errors->has('inbound_bus_id'))
                                    <span class="text-danger">{{ $errors->first('inbound_bus_id') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="bus_id">Bus</label>
                            <div class="input-group">
                            <span class="input-group-text" id="basic-addon1">
                                <i class="fas fa-bus fa-fw"></i>
                            </span>
                                <select wire:model.defer="state.bus_id" class="form-select border-gray-300" autofocus required>
                                    <option value="">Choose Bus</option>
                                    @foreach($editedBuses as $editedBus)
                                        <option value="{{$editedBus->id}}">{{$editedBus->bus_registration_number}}</option>
                                    @endforeach
                                </select>
                                @if ($errors->has('bus_id'))
                                    <span class="text-danger">{{ $errors->first('bus_id') }}</span>
                                @endif
                            </div>
                        </div>
                        {{-- <div class="form-group mb-4">
                            <label for="inbound_bus_id">Inbound Bus</label>
                            <div class="input-group">
                            <span class="input-group-text" id="basic-addon1">
                                <i class="fas fa-bus fa-fw"></i>
                            </span>
                                <select wire:model.defer="state.inbound_bus_id" class="form-select border-gray-300" autofocus required>
                                    <option value="">Choose Inbound Bus</option>
                                    @foreach($editedBuses as $editedBus)
                                        <option value="{{$editedBus->id}}">{{$editedBus->bus_registration_number}}</option>
                                    @endforeach
                                </select>
                                @if ($errors->has('inbound_bus_id'))
                                    <span class="text-danger">{{ $errors->first('inbound_bus_id') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="outbound_bus_id">Outbound Bus</label>
                            <div class="input-group">
                            <span class="input-group-text" id="basic-addon1">
                                <i class="fas fa-bus fa-fw"></i>
                            </span>
                                <select wire:model.defer="state.outbound_bus_id" class="form-select border-gray-300" autofocus required>
                                    <option value="">Choose Outbound Bus</option>
                                    @foreach($editedBuses as $editedBus)
                                        <option value="{{$editedBus->id}}">{{$editedBus->bus_registration_number}}</option>
                                    @endforeach
                                </select>
                                @if ($errors->has('outbound_bus_id'))
                                    <span class="text-danger">{{ $errors->first('outbound_bus_id') }}</span>
                                @endif
                            </div>
                        </div> --}}
                        <div class="form-group mb-4">
                            <label for="state">Trip Type</label>
                            <div class="input-group">
                            <span class="input-group-text" id="basic-addon1">
                                <i class="fas fa-calendar-check fa-fw"></i>
                            </span>
                                <select wire:model.defer="state.trip_type" class="form-select border-gray-300" autofocus required>
                                    <option value="">Choose Trip Type</option>
                                    <option value="1">WEEKDAY</option>
                                    <option value="2">WEEKEND</option>
                                    <option value="3">ALLDAY</option>
                                    <option value="4">ALLDAY (Except Friday)</option>
                                    <option value="5">ALLDAY (Except Sunday)</option>
                                    <option value="9">ALLDAY (Except Friday & Sunday)</option>
                                    <option value="10">ALLDAY (Except Friday & Saturday)</option>
                                    <option value="6">Monday - Thursday</option>
                                    <option value="7">Friday Only</option>
                                    <option value="8">Saturday Only</option>
                                    <option value="11">Sunday Only</option>
                                    <option value="12">Friday & Saturday</option>
                                </select>
                                @if ($errors->has('trip_type'))
                                    <span class="text-danger">{{ $errors->first('trip_type') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="status">Status</label>
                            <div class="input-group">
                            <span class="input-group-text" id="basic-addon1">
                                <i class="fas fa-check-circle fa-fw"></i>
                            </span>
                                <select wire:model.defer="state.status" class="form-select border-gray-300" autofocus required>
                                    <option value="">Choose Status</option>
                                    <option value="1">ENABLED</option>
                                    <option value="2">DISABLED</option>
                                </select>
                                @if ($errors->has('status'))
                                    <span class="text-danger">{{ $errors->first('status') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary" id="btnSave">
                                <span>Save Changes</span>
                            </button>
                        </div>
                    </form>
                </div>
                <div class="modal-header"></div>
            </div>
        </div>
    </div>
    <!-- End of Edit Route Schedule Modal Content -->

    <!-- Confirm Change Status Route Schedule Modal -->
    <div wire:ignore.self class="modal fade" id="confirmChangeModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5>Change Status of Route Schedule</h5>
                </div>
                <div class="modal-body">
                    @if($currentStatus==1)
                        <h4>Are you confirm to disable this route schedule at {{ $changedSchedule }}?</h4>
                    @else
                        <h4>Are you confirm to enable this route schedule at {{ $changedSchedule }}?</h4>
                    @endif
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fa fa-times mr-1"></i>
                        <span>Cancel</span>
                    </button>
                    <button type="button" wire:click.prevent="changeStatus" class="btn btn-danger">
                        <i class="fa fa-check mr-1"></i>
                        <span>Confirm</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- End of Confirm Change Status Route Schedule Modal Content -->

    <!-- Remove Route Schedule Modal -->
    <div wire:ignore.self class="modal fade" id="confirmRemoveModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5>Remove Route Scheduler</h5>
                </div>

                <div class="modal-body">
                    <h4>Are you sure you want to remove route schedule at {{ $removedSchedule }}?</h4>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa fa-times mr-1"></i> Cancel</button>
                    <button type="button" wire:click.prevent="removeSchedule" class="btn btn-danger"><i class="fa fa-trash mr-1"></i>Remove Route Schedule</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End of Remove Route Schedule Modal Content -->
    
    <div
        class="card-footer px-3 border-0 d-flex flex-column flex-lg-row align-items-center justify-content-between">
        {{--{{ $users->links() }}--}}
    </div>
</div>
@push('script')
    <script>
        window.addEventListener('show-form', event => {
            $('#modalEdit').modal('show');
        });
        window.addEventListener('hide-form-edit', event => {
            $('#modalEdit').modal('hide');
            toastr.success(event.detail.message, 'Route schedule updated successfully!');
        });
        window.addEventListener('hide-form-add', event => {
            $('#modalEdit').modal('hide');
            toastr.success(event.detail.message, 'New route schedule added successfully!');
        });
        window.addEventListener('hide-form-failed', event => {
            $('#modalEdit').modal('hide');
            toastr.error(event.detail.message, 'Operation failed!');
        });
        window.addEventListener('show-status-modal', event => {
            $('#confirmChangeModal').modal('show');
        });
        window.addEventListener('hide-status-modal', event => {
            $('#confirmChangeModal').modal('hide');
            toastr.success(event.detail.message, 'Status updated successfully!');
        })
        window.addEventListener('show-delete-modal', event => {
            $('#confirmRemoveModal').modal('show');
        });
        window.addEventListener('hide-delete-modal', event => {
            $('#confirmRemoveModal').modal('hide');
            toastr.success(event.detail.message, 'Route schedule removed successfully!');
        })
        window.addEventListener('hide-delete-failed', event => {
            $('#confirmRemoveModal').modal('hide');
            toastr.error(event.detail.message, 'Route schedule failed to remove!');
        });
    </script>
@endpush
