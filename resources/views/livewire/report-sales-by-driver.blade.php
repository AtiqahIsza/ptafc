<div class="main py-4">
    <div class="card card-body border-0 shadow table-wrapper table-responsive">
        <h2>{{ __('Sales Report By Driver') }}</h2>
        <br>
        <!-- Form -->
        <form wire:submit.prevent="{{ 'printSalesByDriver' }}">
            @csrf
            <table class="table table-hover">
                <tbody>
                <tr>
                    <th class="border-gray-200">{{ __('Date From') }}</th>
                    <td>
                        <input wire:model.defer="state.dateFrom" class="form-control border-gray-300" type="date" autofocus required>
                        @if ($errors->has('dateFrom'))
                            <span class="text-danger">{{ $errors->first('dateFrom') }}</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th class="border-gray-200">{{ __('Date To') }}</th>
                    <td>
                        <input wire:model.defer="state.dateTo" class="form-control border-gray-300" type="date" autofocus required>
                        @if ($errors->has('dateTo'))
                            <span class="text-danger">{{ $errors->first('dateTo') }}</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th class="border-gray-200">{{ __('Driver') }}</th>
                    <td>
                        <select style="width:100%" wire:model.defer="state.driver_id" id="driver_id" class="form-select border-gray-300" autofocus required>
                            <option value="">Choose Driver</option>
                            @foreach($drivers as $driver)
                                <option value="{{$driver->id}}">{{$driver->driver_name}}</option>
                            @endforeach
                        </select>
                        @if ($errors->has('driver_id'))
                            <span class="text-danger">{{ $errors->first('driver_id') }}</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>
                        <button type="submit" class="btn btn-primary" id="btnSave">
                            <span>Print Details</span>
                        </button>
                    </td>
                </tr>
                </tbody>
            </table>
        </form>
    </div>
    <div
        class="card-footer px-3 border-0 d-flex flex-column flex-lg-row align-items-center justify-content-between">
        {{--{{ $users->links() }}--}}
    </div>
</div>
