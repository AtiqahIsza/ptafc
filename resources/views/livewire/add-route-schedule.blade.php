<div class="card card-body border-0 shadow table-wrapper table-responsive">
    <h2 class="mb-4 h5">{{ __('Add New Route Schedule') }}</h2>

    <!-- Form -->
    <form wire:submit.prevent="{{ 'addRouteSchedule' }}">
    @csrf
    <table class="table table-hover">
        <tbody>
        <tr>
            <th class="border-gray-200">{{ __('Time') }}</th>
            <td>
                <input wire:model.defer="state.schedule_time" class="form-control border-gray-300" type="time" autofocus required>
            </td>
        </tr>
        <tr>
            <th class="border-gray-200">{{ __('Route Name') }}</th>
            <td>
                <label for="route_id">
                    <select wire:model="selectedRoute" class="form-select border-gray-300" style="width:100%" autofocus required>
                        <option value="">Choose Route</option>
                        @foreach($routes as $route)
                            <option value="{{$route->id}}">{{$route->route_name}}</option>
                        @endforeach
                    </select>
                </label>
                @if ($errors->has('route_id'))
                    <span class="text-danger">{{ $errors->first('route_id') }}</span>
                @endif
            </td>
        </tr>
        <tr>
            <th class="border-gray-200">{{ __('Inbound Distance in KM') }}</th>
            <td>
                <input wire:model.defer="state.inbound_distance" id="inDistance" class="form-control border-gray-300" placeholder="{{ __('Inbound Distance in KM') }}" autofocus required>
            </td>
        </tr>
        <tr>
            <th class="border-gray-200">{{ __('Outbound Distance in KM') }}</th>
            <td>
                <input wire:model.defer="state.outbound_distance" id="outDistance" class="form-control border-gray-300" placeholder="{{ __('Outbound Distance in KM') }}" autofocus required>
            </td>
        </tr>
        @if (!is_null($selectedRoute))
        <tr>
            <th class="border-gray-200">{{ __('Inbound Bus') }}</th>
            <td>
                <label for="inbound_bus_id">
                    <select style="width:100%" wire:model.defer="state.inbound_bus_id" class="form-select border-gray-300" autofocus required>
                        <option value="">Choose Inbound Bus</option>
                        @foreach($buses as $bus)
                            <option value="{{$bus->id}}">{{$bus->bus_registration_number}}</option>
                        @endforeach
                    </select>
                </label>
                @if ($errors->has('inbound_bus_id'))
                    <span class="text-danger">{{ $errors->first('inbound_bus_id') }}</span>
                @endif            </td>
        </tr>
        <tr>
            <th class="border-gray-200">{{ __('Outbound Bus') }}</th>
            <td>
                <label for="outbound_bus_id">
                    <select wire:model.defer="state.outbound_bus_id" class="form-select border-gray-300" autofocus required>
                        <option value="">Choose Outbound Bus</option>
                        @foreach($buses as $bus)
                            <option value="{{$bus->id}}">{{$bus->bus_registration_number}}</option>
                        @endforeach
                    </select>
                </label>
                @if ($errors->has('outbound_bus_id'))
                    <span class="text-danger">{{ $errors->first('outbound_bus_id') }}</span>
                @endif
            </td>
        </tr>
        @endif
        <tr>
            <th class="border-gray-200">{{ __('Trip Type') }}</th>
            <td>
                <label for="trip_type">
                    <select wire:model.defer="state.trip_type" class="form-select border-gray-300" autofocus required>
                        <option value="">Choose Trip Type</option>
                        <option value="1">WEEKDAY</option>
                        <option value="2">WEEKEND</option>
                    </select>
                </label>
                @if ($errors->has('trip_type'))
                    <span class="text-danger">{{ $errors->first('trip_type') }}</span>
                @endif
            </td>
        </tr>
        <tr>
            <th class="border-gray-200">{{ __('Status') }}</th>
            <td>
                <label for="status">
                    <select wire:model.defer="state.status" class="form-select border-gray-300" autofocus required>
                        <option value="">Choose Trip Type</option>
                        <option value="1">ENABLE</option>
                        <option value="2">DISABLE</option>
                    </select>
                </label>
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
</div>
