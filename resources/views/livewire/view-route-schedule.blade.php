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
                <td><span class="fw-normal">123{{--{{ $schedule->bus->bus_registration_number}}--}}</span></td>
                <td><span class="fw-normal">123{{--{{ $schedule->bus->bus_registration_number}}--}}</span></td>
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
</div>
