<div class="main py-4">
    <div class="d-block mb-md-0" style="position: relative">
        <h2>Manage Route Schedule</h2>
        <button wire:click.prevent="addNew" class="buttonAdd btn btn-gray-800 d-inline-flex align-items-center me-2">
            <i class="fa fa-plus-circle mr-1 fa-fw"></i>
            Add Route Schedule
        </button>
    </div>
    <div class="d-block mb-md-0" style="position: relative">
        <select wire:model="selectedRegion" class="form-select fmxw-200 d-none d-md-inline">
            <option value="">Choose Region</option>
            @foreach($regions as $region)
                <option value="{{$region->id}}">{{$region->description}}</option>
            @endforeach
        </select>

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
    @livewire('view-route-schedule')

    @if ($addNewButton)
        <br>
        @livewire('add-route-schedule')
    @endif

    <div
        class="card-footer px-3 border-0 d-flex flex-column flex-lg-row align-items-center justify-content-between">
        {{--{{ $users->links() }}--}}
    </div>
</div>
