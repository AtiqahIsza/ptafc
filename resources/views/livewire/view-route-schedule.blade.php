<div class="card card-body border-0 shadow table-wrapper table-responsive">
    <h2 class="mb-4 h5">{{ __('All Schedule by Route') }}</h2>

    <table class="table table-hover">
        <thead>
        <tr>
            <th class="border-gray-200">{{ __('Time') }}</th>
            <th class="border-gray-200">{{ __('Route Name') }}</th>
            <th class="border-gray-200">{{ __('Inbound Distance') }}</th>
            <th class="border-gray-200">{{ __('Outbound Distance') }}</th>
            <th class="border-gray-200">{{ __('Inbound Bus') }}</th>
            <th class="border-gray-200">{{ __('Outbound Bus') }}</th>
            <th class="border-gray-200">{{ __('Trip Type') }}</th>
            <th class="border-gray-200">{{ __('Status') }}</th>
            <th class="border-gray-200">Action</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($schedules as $schedule)
            <tr>
                <td><span class="fw-normal">{{ $schedule->schedule_time }}</span></td>
                <td><span class="fw-normal">{{ $schedule->route->route_name }}</span></td>
                <td><span class="fw-normal">{{ $schedule->inbound_distance }}</span></td>
                <td><span class="fw-normal">{{ $schedule->outbound_distance }}</span></td>
                <td><span class="fw-normal">{{ $schedule->inbus->bus_registration_number}}</span></td>
                <td><span class="fw-normal">{{ $schedule->outbus->bus_registration_number}}</span></td>
                @if($schedule->trip_type==1)
                    <td><span class="fw-normal">WEEKDAY</span></td>
                @else
                    <td><span class="fw-normal">WEEKEND</span></td>
                @endif

                @if($schedule->status==1)
                    <td><span class="fw-normal">ENABLED</span></td>
                @else
                    <td><span class="fw-normal">DISABLED</span></td>
                @endif
                <td>
                    <!-- Button Modal -->
                    <button wire:click.prevent="edit({{ $schedule }})" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modalEdit">Edit</button>
                    <button wire:click.prevent="confirmRemoval({{ $schedule->id }})" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirmationModal">Remove</button>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <!-- Remove Route Schedule Modal -->
    <div wire:ignore.self class="modal fade" id="confirmationModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5>Remove Route Scheduler</h5>
                </div>

                <div class="modal-body">
                    <h4>Are you sure you want to remove this route schedule?</h4>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa fa-times mr-1"></i> Cancel</button>
                    <button type="button" wire:click.prevent="removeSchedule" class="btn btn-danger"><i class="fa fa-trash mr-1"></i>Remove Route Schedule</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End of Remove Route Schedule Modal Content -->

    <!-- Edit/Create Modal Content -->
    <div wire:ignore.self class="modal fade" id="modalEdit" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-md-5">
                    <h2 class="h4 text-center">
                        <span>Edit Route Schedule</span>
                    </h2>

                    <!-- Form -->
                    <form wire:submit.prevent="{{ 'updateRouteSchedule' }}">
                        @csrf
                        <div class="form-group mb-4">
                            <label for="schedule_time">Time</label>
                            <div class="input-group">
                            <span class="input-group-text border-gray-300" id="basic-addon3">
                                <i class="fas fa-clock fa-fw"></i>
                            </span>
                                <input wire:model.defer="state.schedule_time" class="form-control border-gray-300" type="time" autofocus required>
                                @if ($errors->has('schedule_time'))
                                    <span class="text-danger">{{ $errors->first('schedule_time') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="selectedRoute">Route Name</label>
                            <div class="input-group">
                            <span class="input-group-text border-gray-300" id="basic-addon3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pin-map-fill" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd" d="M3.1 11.2a.5.5 0 0 1 .4-.2H6a.5.5 0 0 1 0 1H3.75L1.5 15h13l-2.25-3H10a.5.5 0 0 1 0-1h2.5a.5.5 0 0 1 .4.2l3 4a.5.5 0 0 1-.4.8H.5a.5.5 0 0 1-.4-.8l3-4z"/>
                                    <path fill-rule="evenodd" d="M4 4a4 4 0 1 1 4.5 3.969V13.5a.5.5 0 0 1-1 0V7.97A4 4 0 0 1 4 3.999z"/>
                                </svg>
                            </span>
                                <select wire:model.defer="state.route_id" class="form-select border-gray-300" autofocus required>
                                    <option value="">Choose Route</option>
                                    @foreach($routes as $route)
                                        <option value="{{$route->id}}">{{$route->route_name}}</option>
                                    @endforeach
                                </select>
                                @if ($errors->has('route_id'))
                                    <span class="text-danger">{{ $errors->first('route_id') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="inbound_distance">Inbound Distance in KM</label>
                            <div class="input-group">
                            <span class="input-group-text border-gray-300" id="basic-addon3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bezier2" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd" d="M1 2.5A1.5 1.5 0 0 1 2.5 1h1A1.5 1.5 0 0 1 5 2.5h4.134a1 1 0 1 1 0 1h-2.01c.18.18.34.381.484.605.638.992.892 2.354.892 3.895 0 1.993.257 3.092.713 3.7.356.476.895.721 1.787.784A1.5 1.5 0 0 1 12.5 11h1a1.5 1.5 0 0 1 1.5 1.5v1a1.5 1.5 0 0 1-1.5 1.5h-1a1.5 1.5 0 0 1-1.5-1.5H6.866a1 1 0 1 1 0-1h1.711a2.839 2.839 0 0 1-.165-.2C7.743 11.407 7.5 10.007 7.5 8c0-1.46-.246-2.597-.733-3.355-.39-.605-.952-1-1.767-1.112A1.5 1.5 0 0 1 3.5 5h-1A1.5 1.5 0 0 1 1 3.5v-1zM2.5 2a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1zm10 10a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1z"/>
                                </svg>
                            </span>
                                <input wire:model.defer="state.inbound_distance" id="inbound_distance" class="form-control border-gray-300" placeholder="{{ __('Inbound Distance in KM') }}" autofocus required>
                                @if ($errors->has('inbound_distance'))
                                    <span class="text-danger">{{ $errors->first('inbound_distance') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="outbound_distance">Outbound Distance in KM</label>
                            <div class="input-group">
                            <span class="input-group-text border-gray-300" id="basic-addon3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bezier2" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd" d="M1 2.5A1.5 1.5 0 0 1 2.5 1h1A1.5 1.5 0 0 1 5 2.5h4.134a1 1 0 1 1 0 1h-2.01c.18.18.34.381.484.605.638.992.892 2.354.892 3.895 0 1.993.257 3.092.713 3.7.356.476.895.721 1.787.784A1.5 1.5 0 0 1 12.5 11h1a1.5 1.5 0 0 1 1.5 1.5v1a1.5 1.5 0 0 1-1.5 1.5h-1a1.5 1.5 0 0 1-1.5-1.5H6.866a1 1 0 1 1 0-1h1.711a2.839 2.839 0 0 1-.165-.2C7.743 11.407 7.5 10.007 7.5 8c0-1.46-.246-2.597-.733-3.355-.39-.605-.952-1-1.767-1.112A1.5 1.5 0 0 1 3.5 5h-1A1.5 1.5 0 0 1 1 3.5v-1zM2.5 2a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1zm10 10a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1z"/>
                                </svg>
                            </span>
                                <input wire:model.defer="state.outbound_distance" id="outbound_distance" class="form-control border-gray-300" placeholder="{{ __('Outbound Distance in KM') }}" autofocus required>
                                @if ($errors->has('outbound_distance'))
                                    <span class="text-danger">{{ $errors->first('outbound_distance') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="inbound_bus_id">Inbound Bus</label>
                            <div class="input-group">
                            <span class="input-group-text" id="basic-addon1">
                                <i class="fas fa-bus fa-fw"></i>
                            </span>
                                <select wire:model.defer="state.inbound_bus_id" class="form-select border-gray-300" autofocus required>
                                    <option value="">Choose Inbound Bus</option>
                                    @foreach($buses as $bus)
                                        <option value="{{$bus->id}}">{{$bus->bus_registration_number}}</option>
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
                                    @foreach($buses as $bus)
                                        <option value="{{$bus->id}}">{{$bus->bus_registration_number}}</option>
                                    @endforeach
                                </select>
                                @if ($errors->has('outbound_bus_id'))
                                    <span class="text-danger">{{ $errors->first('outbound_bus_id') }}</span>
                                @endif
                            </div>
                        </div>
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
                                    <option value="">Choose Trip Type</option>
                                    <option value="1">ENABLE</option>
                                    <option value="2">DISABLE</option>
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
</div>




