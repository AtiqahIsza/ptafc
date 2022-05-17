<div class="main py-4">
    <div class="card card-body border-0 shadow table-wrapper table-responsive">
        <h2>{{ __('Vehicle Position History') }}</h2>
        <br>
        <!-- Form -->
        <form action={{ route('viewGPS') }} method="post">
            @csrf
            <table class="table table-hover">
                <tbody>
                <tr>
                    <th class="border-gray-200">{{ __('Date') }}</th>
                    <td>
                        <input name="dateChoose" id="dateChoose" class="form-control border-gray-300" type="date" autofocus required>
                        @if ($errors->has('dateChoose'))
                            <span class="text-danger">{{ $errors->first('dateChoose') }}</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th class="border-gray-200">{{ __('Company') }}</th>
                    <td>
                        <select style="width:100%" wire:model="selectedCompany" id="company" class="form-select border-gray-300" autofocus required>
                            <option value="">Choose Company</option>
                            @foreach($companies as $company)
                                <option value="{{$company->id}}">{{$company->company_name}}</option>
                            @endforeach
                        </select>
                        @if ($errors->has('company'))
                            <span class="text-danger">{{ $errors->first('company') }}</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th class="border-gray-200">{{ __('Bus Registration Number') }}</th>
                    <td>
                        <select style="width:100%" name="bus_id" id="bus_id" class="form-select border-gray-300" autofocus required>
                            <option value="">Choose Bus</option>
                            @foreach($buses as $bus)
                                <option value="{{$bus->id}}">{{$bus->bus_registration_number}}</option>
                            @endforeach
                        </select>
                        @if ($errors->has('bus_id'))
                            <span class="text-danger">{{ $errors->first('bus_id') }}</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td>
                        <button type="submit" class="btn btn-primary" id="btnSave" style="float: right">
                            <span>View</span>
                        </button>
                    </td>
                </tr>
                </tbody>
            </table>
        </form>
    </div>
</div>
