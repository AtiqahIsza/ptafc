<div class="main py-4">
    <div class="d-block mb-md-0" style="position: relative">
        <h2>GPS Last Received</h2>
    </div>
    <div class="d-block mb-md-0" style="position: relative">
        <select wire:model="selectedCompany" class="form-select fmxw-200 d-none d-md-inline"  >
            <option value="">Choose Company</option>
            <option value="ALL">All Companies</option>
            @foreach($companies as $company)
                <option value="{{$company->id}}">{{$company->company_name}}</option>
            @endforeach
        </select>

        <select wire:model="selectedBus" class="form-select fmxw-200 d-none d-md-inline"  >
            <option value="">Choose Bus</option>
            <option value="ALL">All Buses</option>
            @foreach($buses as $bus)
                <option value="{{$bus->id}}">{{$bus->bus_registration_number}}</option>
            @endforeach
        </select>
        {{-- <input wire:model="selectedDate" class="form-control border-gray-300 fmxw-400 d-none d-md-inline" type="date" placeholder="Choose Date"> --}}
    </div>
    <br>

    <div class="card card-body border-0 shadow table-wrapper table-responsive">
        <table class="table table-hover">
            <thead>
            <tr>
                <th class="border-gray-200">{{ __('No') }}</th>
                <th class="border-gray-200">{{ __('Creation Date') }}</th>
                <th class="border-gray-200">{{ __('Bus No') }}</th>
                <th class="border-gray-200">{{ __('Speed') }}</th>
                <th class="border-gray-200">{{ __('Latitude') }}</th>
                <th class="border-gray-200">{{ __('Longitude') }}</th>
                <th class="border-gray-200">{{ __('View') }}</th>
            </tr>
            </thead>
            <tbody>
                @php $i=1; @endphp
                @foreach ($gpses as $gps)
                    <tr>
                        <td><span class="fw-normal">{{ $i++ }}</span></td>
                        <td><span class="fw-normal">{{ $gps->date_time }}</span></td>
                        <td><span class="fw-normal">{{ $gps->bus_registration_number }}</span></td>
                        <td><span class="fw-normal">{{ $gps->speed }}</span></td>
                        <td><span class="fw-normal">{{ $gps->latitude }}</span></td>
                        <td><span class="fw-normal">{{ $gps->longitude }}</span></td>
                        <td>
                            <button onclick="window.location='{{ route('viewRealtime', $gps->id) }}'" class="btn btn-success">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-geo-alt-fill" viewBox="0 0 16 16">
                                    <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/>
                                </svg>
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="card-footer px-3 border-0 d-flex flex-column flex-lg-row align-items-center justify-content-between">
            {{--{{ $users->links() }}--}}
        </div>
    </div>
</div>