<div class="main py-4">
    <div class="card card-body border-0 shadow table-wrapper table-responsive">
        <h2>{{ __('Collection Report By Company') }}</h2>
        <br>
        <!-- Form -->
        <form wire:submit.prevent="{{ 'print' }}">
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
                    <th class="border-gray-200">{{ __('Company') }}</th>
                    <td>
                        <select wire:model.defer="state.company_id" id="company_id" class="form-select border-gray-300">
                            <option value="">Choose Company</option>
                            {{-- <option value="All">All Companies</option> --}}
                            @foreach($companies as $company)
                                <option value="{{$company->id}}">{{$company->company_name}}</option>
                            @endforeach
                        </select>
                        @if ($errors->has('company_id'))
                            <span class="text-danger">{{ $errors->first('company_id') }}</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td>
                        <button type="submit" class="btn btn-primary" id="btnSave" style="float: right">
                            <span>Print Details</span>
                        </button>
                    </td>
                </tr>
                </tbody>
            </table>
        </form>
    </div>
    <div wire:loading>
        <div style="display:flex; justify-content:center; align-items:center; background-color:black;
        position:fixed; top:0px; left:0px; z-index:9999; width:100%; height:100%; opacity:.75;">
            <div style="color: #f4696b" class="la-ball-clip-rotate-pulse la-3x">
                <div></div>
                <div></div>
            </div>
        </div>
    </div>
    <div
        class="card-footer px-3 border-0 d-flex flex-column flex-lg-row align-items-center justify-content-between">
        {{--{{ $users->links() }}--}}
    </div>
</div>