<div class="card card-body border-3 shadow table-wrapper table-responsive">
    <!-- Form -->
    <form wire:submit.prevent="{{ 'printReceipt' }}">
        @csrf
        <table class="table table-hover">
            <tbody>
            <tr>
                <th class="border-gray-200">{{ __('Bus Driver Name') }}</th>
                <td>
                    <select wire:model="state.driver_id" class="form-select border-gray-300" style="width:100%" autofocus required>
                        <option value="">Choose Bus Driver</option>
                        @foreach($busDrivers as $busDriver)
                            <option value="{{$busDriver->id}}">{{$busDriver->driver_name}}</option>
                        @endforeach
                    </select>
                    @if ($errors->has('busDriver_id'))
                        <span class="text-danger">{{ $errors->first('busDriver_id') }}</span>
                    @endif
                </td>
            </tr>
            <tr>
                <th class="border-gray-200">{{ __('Amount (RM)') }}</th>
                <td>
                    <input wire:model.defer="state.value" id="value" class="form-control border-gray-300" placeholder="{{ __('Amount in RM') }}" autofocus required>
                    @if ($errors->has('value'))
                        <span class="text-danger">{{ $errors->first('value') }}</span>
                    @endif
                </td>
            </tr>
            <tr style="text-align: right">
                <td colspan="2">
                    <button type="submit" class="btn btn-primary" id="btnPrint">
                        <span>Print Receipt</span>
                    </button>
                </td>
            </tr>
            </tbody>
        </table>
    </form>
</div>
