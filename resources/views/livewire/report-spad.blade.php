<div class="main py-4">
    <div class="card card-body border-0 shadow table-wrapper table-responsive">
        <h2>{{ __('Report For APAD') }}</h2>
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
                        <select wire:model="selectedCompany" id="company_id" class="form-select border-gray-300">
                            <option value="">Choose Company</option>
                            <option value="All">All Companies</option>
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
                    <th class="border-gray-200">{{ __('Route') }}</th>
                    <td>
                        <select wire:model="state.route_id" id="route_id" class="form-select border-gray-300">
                            <option value="">Choose Route</option>
                            <option value="All">All Routes</option>
                            @foreach($routes as $route)
                                <option value="{{$route->id}}">{{$route->route_number . ' ' . $route->route_name}}</option>
                            @endforeach
                        </select>
                        @if ($errors->has('route_id'))
                            <span class="text-danger">{{ $errors->first('route_id') }}</span>
                        @endif
                    </td>
                </tr>
                <tr style="text-align: center;">
                    <td colspan="2">
                        <button wire:click.prevent="printSummary()" class="btn btn-gray-800 align-items-center me-2" id="btnSave" style="margin:5px; width: 220px">
                            <span>Print Summary</span>
                        </button>
                        <button wire:click.prevent="printServiceGroup()" class="btn btn-gray-800 align-items-center me-2" id="btnSave" style="margin:5px; width: 220px">
                            <span>Print Service Group</span>
                        </button>
                    </td>
                </tr>
                <tr style="text-align: center;">
                    <td colspan="2">
                        <button wire:click.prevent="printRoute()" class="btn btn-gray-800 align-items-center me-2" id="btnSave" style="margin:5px; width: 220px">
                            <span>Print Route</span>
                        </button>
                        <button wire:click.prevent="printTrip()" class="btn btn-gray-800 align-items-center me-2" id="btnSave" style="margin:5px; width: 220px">
                            <span>Print Trip</span>
                        </button>
                    </td>
                </tr>
                <tr style="text-align: center;">
                    <td colspan="2">
                        <button wire:click.prevent="printTopBoardings()" class="btn btn-gray-800 align-items-center me-2" id="btnSave" style="margin:5px; width: 220px">
                            <span>Print Top Boardings</span>
                        </button>
                        <button wire:click.prevent="printTopAlighting()" class="btn btn-gray-800 align-items-center me-2" id="btnSave" style="margin:5px; width: 220px">
                            <span>Print Top Alightings</span>
                        </button>
                        <button wire:click.prevent="printBusTransfer()" class="btn btn-gray-800 align-items-center me-2" id="btnSave" style="margin:5px; width: 220px">
                            <span>Print Bus Transfer</span>
                        </button>
                    </td>
                </tr>
                {{--<tr style="text-align: center;">
                    <td colspan="2">
                        <button wire:click.prevent="printBusTransfer()" class="btn btn-gray-800 align-items-center me-2" id="btnSave" style="margin:5px; width: 220px">
                            <span>Print Bus Transfer</span>
                        </button>
                        <button wire:click.prevent="printTranferPoint()" class="btn btn-gray-800 align-items-center me-2" id="btnSave" style="margin:5px; width: 220px">
                            <span>Print Transfer Point</span>
                        </button>
                    </td>
                </tr>--}}
                <tr style="text-align: center;">
                    <td colspan="2">
                        <button wire:click.prevent="printClaimDetails()" class="btn btn-gray-800 align-items-center me-2" id="btnSave" style="margin:5px; width: 220px">
                            <span>Print Claim Details</span>
                        </button>
                        {{-- <button onclick="printClaimDetailGPS($this->state['dateFrom'], $this->state['dateFrom'], $this->state['route_id'], $this->selectedCompany)" class="btn btn-gray-800 align-items-center me-2" id="btnSave" style="margin:5px; width: 220px">
                            <span>Print Claim Details GPS</span>
                        </button> --}}
                        <button wire:click.prevent="printClaimDetailGPS()" class="btn btn-gray-800 align-items-center me-2" id="btnSave" style="margin:5px; width: 220px">
                            <span>Print Claim Details GPS</span>
                        </button>
                        <button wire:click.prevent="printClaimSummary()" class="btn btn-gray-800 align-items-center me-2" id="btnSave" style="margin:5px; width: 220px">
                            <span>Print Claim Summary</span>
                        </button>
                    </td>
                </tr>
                <tr style="text-align: center;">
                    <td colspan="2">
                        <button wire:click.prevent="printSummaryRoute()" class="btn btn-gray-800 align-items-center me-2" id="btnSave" style="margin:5px; width: 220px">
                            <span>Print Summary by Route</span>
                        </button>
                        <button wire:click.prevent="printSummaryNetwork()" class="btn btn-gray-800 align-items-center me-2" id="btnSave" style="margin:5px; width: 220px">
                            <span>Print Summary by Network</span>
                        </button>
                        <button wire:click.prevent="printISBSF()" class="btn btn-gray-800 align-items-center me-2" id="btnSave" style="margin:5px; width: 220px">
                            <span>Print ISBSF Report</span>
                        </button>
                    </td>
                </tr>
                <tr style="text-align: center;">
                    <td colspan="2">
                        <button wire:click.prevent="printTripPlanned()" class="btn btn-gray-800 align-items-center me-2" id="btnSave" style="margin:5px; width: 220px">
                            <span>Print Trip Planned</span>
                        </button>
                        <button wire:click.prevent="printTripMissed()" class="btn btn-gray-800 align-items-center me-2" id="btnSave" style="margin:5px; width: 220px">
                            <span>Print Trip Missed</span>
                        </button>
                        <button wire:click.prevent="printSalesDetails()" class="btn btn-gray-800 align-items-center me-2" id="btnSave" style="margin:5px; width: 220px">
                            <span>Print Sales Details</span>
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
@push('script')
    <script>
        window.addEventListener('route-required', event => {
            toastr.warning(event.detail.message, 'Please select route!');
        });
        window.addEventListener('company-required', event => {
            toastr.warning(event.detail.message, 'Please select company!');
        });
        window.addEventListener('new-tab-gps', event => {
            //toastr.warning(event.detail.message, 'Here in new tab!');
            // open the page as popup //
            var page = 'http://mara.joompe.com/report/claimDetails/' + event.detail.dateFrom + '/' + event.detail.dateTo + '/' + event.detail.routeID + '/' + event.detail.companyID;
            var myWindow = window.open(page, "_blank");
            //var myWindow = window.open(page, "_blank", "scrollbars=yes,width=400,height=500,top=300");
            
            // focus on the popup //
            myWindow.focus();
        });
        
    </script>
@endpush
