<div class="main py-4">
    <div class="d-block mb-md-0" style="position: relative">
        <h2>View Cards</h2>
    </div>
    <div class="d-block mb-md-0" style="position: relative">
        <select wire:model="selectedRegion" class="form-select fmxw-200 d-none d-md-inline"  >
            <option value="">Choose Region</option>
            @foreach($regions as $region)
                <option value="{{$region->id}}">{{$region->description}}</option>
            @endforeach
        </select>

        @if (!is_null($selectedRegion))
            <select wire:model="selectedCardType" class="form-select fmxw-200 d-none d-md-inline"  >
                <option value="">Choose Card Type</option>
                <option value="1">Passenger</option>
                <option value="2">Agent</option>
                <option value="3">Sub-Agent</option>
                <option value="4">Mobile-Agent</option>
                <option value="5">UPM Student</option>
                <option value="6">KTB Staff</option>
                <option value="7">Inspector</option>
                <option value="8">Driver</option>
            </select>
        @endif

        @if (!is_null($selectedCardType))
            <select wire:model="selectedCardStatus" class="form-select fmxw-200 d-none d-md-inline"  >
                <option value="">Choose Card Status</option>
                <option value="1">Active</option>
                <option value="2">Inactive</option>
                <option value="3">Blacklisted</option>
            </select>
        @endif
    </div>
    <br>

    @if (!is_null($selectedCardStatus))
    <div class="card card-body border-0 shadow table-wrapper table-responsive">
        <h2 class="mb-4 h5">{{ __('All Cards by Region, Type and Status') }}</h2>
        <table class="table table-hover">
            <thead>
            <tr>
                <th class="border-gray-200">{{ __('Manufacturing No') }}</th>
                <th class="border-gray-200">{{ __('Cardholder Name') }}</th>
                <th class="border-gray-200">{{ __('ID No') }}</th>
                <th class="border-gray-200">{{ __('Card No') }}</th>
                <th class="border-gray-200">{{ __('Card Type') }}</th>
                <th class="border-gray-200">{{ __('Card Balance') }}</th>
            </tr>
            </thead>
                <tbody>

                    @foreach ($cards as $card)
                        <tr>
                            <td><span class="fw-normal">{{ $card->manufacturing_id }}</span></td>
                            <td><span class="fw-normal">{{ $card->card_holder_name }}</span></td>
                            <td><span class="fw-normal">{{ $card->id_number }}</span></td>
                            <td><span class="fw-normal">{{ $card->card_number }}</span></td>
                            <td><span class="fw-normal">{{ $card->card_type }}</span></td>
                            <td><span class="fw-normal">{{ $card->current_balance }}</span></td>
                        </tr>
                    @endforeach

                </tbody>
        </table>
        <div class="card-footer px-3 border-0 d-flex flex-column flex-lg-row align-items-center justify-content-between">
            {{--{{ $users->links() }}--}}
        </div>
    </div>
    @endif
</div>
