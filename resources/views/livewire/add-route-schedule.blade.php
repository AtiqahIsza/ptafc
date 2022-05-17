<div class="card card-body border-3 shadow table-wrapper table-responsive">
    <h2 class="mb-4 h5">{{ __('Add New Route Schedule') }}</h2>

    <!-- Form -->
    <form wire:submit.prevent="{{ 'addRouteSchedule' }}">
    @csrf
    <table class="table table-hover">
        <tbody>
        <tr>
            <th class="border-gray-200">{{ __('Start Time') }}</th>
            <td>
                <input wire:model.defer="state.schedule_start_time" class="form-control border-gray-300" type="time" autofocus required>
                @if ($errors->has('schedule_start_time'))
                    <span class="text-danger">{{ $errors->first('schedule_start_time') }}</span>
                @endif
            </td>
        </tr>
        <tr>
            <th class="border-gray-200">{{ __('End Time') }}</th>
            <td>
                <input wire:model.defer="state.schedule_end_time" class="form-control border-gray-300" type="time" autofocus required>
                @if ($errors->has('schedule_end_time'))
                    <span class="text-danger">{{ $errors->first('schedule_end_time') }}</span>
                @endif
            </td>
        </tr>
        <tr>
            <th class="border-gray-200">{{ __('Company Name') }}</th>
            <td>
                <select wire:model="selectedCompany" class="form-select border-gray-300" style="width:100%" autofocus required>
                    <option value="">Choose Company</option>
                    @foreach($companies as $company)
                        <option value="{{$company->id}}">{{$company->company_name}}</option>
                    @endforeach
                </select>
                @if ($errors->has('route_id'))
                    <span class="text-danger">{{ $errors->first('route_id') }}</span>
                @endif
            </td>
        </tr>
        <tr>
            <th class="border-gray-200">{{ __('Route Name') }}</th>
            <td>
                <select wire:model="state.route_id" class="form-select border-gray-300" style="width:100%" autofocus required>
                    <option value="">Choose Route</option>
                    @foreach($routes as $route)
                        <option value="{{$route->id}}">{{$route->route_name}}</option>
                    @endforeach
                </select>
                @if ($errors->has('route_id'))
                    <span class="text-danger">{{ $errors->first('route_id') }}</span>
                @endif
            </td>
        </tr>
        <tr>
            <th class="border-gray-200">{{ __('Inbound Distance in KM') }}</th>
            <td>
                <input wire:model.defer="state.inbound_distance" id="inbound_distance" class="form-control border-gray-300" placeholder="{{ __('Inbound Distance in KM') }}" autofocus required>
                @if ($errors->has('inbound_distance'))
                    <span class="text-danger">{{ $errors->first('inbound_distance') }}</span>
                @endif
            </td>
        </tr>
        <tr>
            <th class="border-gray-200">{{ __('Outbound Distance in KM') }}</th>
            <td>
                <input wire:model.defer="state.outbound_distance" id="outbound_distance" class="form-control border-gray-300" placeholder="{{ __('Outbound Distance in KM') }}" autofocus required>
                @if ($errors->has('outbound_distance'))
                    <span class="text-danger">{{ $errors->first('outbound_distance') }}</span>
                @endif
            </td>
        </tr>
        <tr>
            <th class="border-gray-200">{{ __('Inbound Bus') }}</th>
            <td>
                <select style="width:100%" wire:model.defer="state.inbound_bus_id" class="form-select border-gray-300" autofocus required>
                    <option value="">Choose Inbound Bus</option>
                    @foreach($buses as $bus)
                        <option value="{{$bus->id}}">{{$bus->bus_registration_number}}</option>
                    @endforeach
                </select>
                @if ($errors->has('inbound_bus_id'))
                    <span class="text-danger">{{ $errors->first('inbound_bus_id') }}</span>
                @endif
            </td>
        </tr>
        <tr>
            <th class="border-gray-200">{{ __('Outbound Bus') }}</th>
            <td>
                <select wire:model.defer="state.outbound_bus_id" class="form-select border-gray-300" autofocus required>
                    <option value="">Choose Outbound Bus</option>
                    @foreach($buses as $bus)
                        <option value="{{$bus->id}}">{{$bus->bus_registration_number}}</option>
                    @endforeach
                </select>
                @if ($errors->has('outbound_bus_id'))
                    <span class="text-danger">{{ $errors->first('outbound_bus_id') }}</span>
                @endif
            </td>
        </tr>
        <tr>
            <th class="border-gray-200">{{ __('Trip Type') }}</th>
            <td>
                <select wire:model.defer="state.trip_type" class="form-select border-gray-300" autofocus required>
                    <option value="">Choose Trip Type</option>
                    <option value="1">WEEKDAY</option>
                    <option value="2">WEEKEND</option>
                </select>
                @if ($errors->has('trip_type'))
                    <span class="text-danger">{{ $errors->first('trip_type') }}</span>
                @endif
            </td>
        </tr>
        <tr>
            <th class="border-gray-200">{{ __('Status') }}</th>
            <td>
                <select wire:model.defer="state.status" class="form-select border-gray-300" autofocus required>
                    <option value="">Choose Trip Type</option>
                    <option value="1">ENABLE</option>
                    <option value="2">DISABLE</option>
                </select>
                @if ($errors->has('status'))
                    <span class="text-danger">{{ $errors->first('statuse') }}</span>
                @endif
            </td>
        </tr>
        <tr>
            <td>
                <button type="submit" class="btn btn-primary" id="btnSave">
                    <span>Save</span>
                </button>
            </td>
        </tr>
        </tbody>
    </table>
    </form>
</div>
@push('script')
    <script>
        window.addEventListener('show-form', event => {
            $('#modalEdit').modal('show');
        });
        window.addEventListener('hide-form-edit', event => {
            $('#modalEdit').modal('hide');
            toastr.success(event.detail.message, 'Route updated successfully!');
        });
        window.addEventListener('hide-form-add', event => {
            $('#modalEdit').modal('hide');
            toastr.success(event.detail.message, 'New route added successfully!');
        });
        window.addEventListener('hide-form-failed', event => {
            $('#modalEdit').modal('hide');
            toastr.error(event.detail.message, 'Operation Failed!');
        });
        window.addEventListener('failed-add-route-no', event => {
            $('#modalEdit').modal('hide');
            toastr.error(event.detail.message, 'Failed! Route number already exist!');
        });
        window.addEventListener('failed-add-route-name', event => {
            $('#modalEdit').modal('hide');
            toastr.error(event.detail.message, 'Failed! Route name already exist!');
        });

        window.addEventListener('show-delete-modal', event => {
            $('#confirmationModal').modal('show');
        });
        window.addEventListener('hide-delete-modal', event => {
            $('#confirmationModal').modal('hide');
            toastr.success(event.detail.message, 'Route removed successfully!');
        })

        window.addEventListener('show-delete-map-modal', event => {
            $('#mapConfirmationModal').modal('show');
        });
        window.addEventListener('hide-delete-map-modal', event => {
            $('#mapConfirmationModal').modal('hide');
            toastr.success(event.detail.message, 'Route map removed successfully!');
        })
    </script>
@endpush
