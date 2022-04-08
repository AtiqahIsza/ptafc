<div class="main py-4">
    <div class="d-block mb-md-0" style="position: relative">
        <h2>All Wallet Transaction Records of {{ $drivers->driver_name }}</h2>
    </div>
    <br>
    <div class="card card-body border-3 shadow table-wrapper table-responsive">
        <table class="table table-hover">
            <thead>
            <tr>
                <th class="border-gray-200">{{ __('No') }}</th>
                <th class="border-gray-200">{{ __('Driver Name') }}</th>
                <th class="border-gray-200">{{ __('Value (RM)') }}</th>
                <th class="border-gray-200">{{ __('Top-up Promo (%)') }}</th>
                <th class="border-gray-200">{{ __('Created At') }}</th>
                <th class="border-gray-200">{{ __('Created By') }}</th>
            </tr>
            </thead>
            <tbody>
            @php $i = 1 @endphp
            @foreach ($records as $record)
                @php //dd($record) @endphp
                <tr>
                    <td><span class="fw-normal">{{ $i++ }}</span></td>
                    <td><span class="fw-normal">{{ $record->busdriver->driver_name}}</span></td>
                    <td><span class="fw-normal">{{ $record->value }}</span></td>
                    <td><span class="fw-normal">{{ $record->topuppromo->promo_value }}</span></td>
                    <td><span class="fw-normal">{{ $record->created_at }}</span></td>
                    <td><span class="fw-normal">{{ $record->createdby->full_name }}</span></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="card-footer px-3 border-0 d-flex flex-column flex-lg-row align-items-center justify-content-between">
            {{--{{ $users->links() }}--}}
        </div>
    </div>
    <br>
    <div class="d-block mb-md-0" style="position: relative">

        <input style="float: right; width:100px;" type="button" onclick="window.history.back()" class="btn btn-gray-800" value="Back">
    </div>
</div>
