<div class="main py-4">
    <div class="d-block mb-md-0" style="position: relative">
        <h2>Manage Stage Fares</h2>
    </div>
    <div class="d-block mb-md-0" style="position: relative">
        <select wire:model="selectedCompany" class="form-select fmxw-200 d-none d-md-inline"  >
            <option value="">Choose Company</option>
            @foreach($companies as $company)
                <option value="{{$company->id}}">{{$company->company_name}}</option>
            @endforeach
        </select>

        <select wire:model="selectedRoute" class="form-select fmxw-200 d-none d-md-inline"  >
            <option value="">Choose Route</option>
            @foreach($routes as $route)
                <option value="{{$route->id}}">{{$route->route_name}}</option>
            @endforeach
        </select>
    </div>
    <br>

    @if (!is_null($selectedRoute))
        <div class="row">
            <div class="col-12">

                <!-- Tab -->
                <nav>
                    <div class="nav nav-tabs mb-4" id="nav-tab" role="tablist">
                        <a wire:click="fareType({{ $selectedRoute }},'Adult')" class="nav-item nav-link {{ $fareTypes == 'Adult' ? 'active' : '' }}" id="nav-adult-tab" data-bs-toggle="tab" href="#nav-adult" role="tab" aria-controls="nav-adult" >Adult Fare</a>
                        <a wire:click="fareType({{ $selectedRoute }},'Concession')" class="nav-item nav-link {{ $fareTypes == 'Concession' ? 'active' : '' }}" id="nav-concession-tab" data-bs-toggle="tab" href="#nav-concession" role="tab" aria-controls="nav-concession" >Concession Fare</a>
                    </div>
                </nav>

                <div class="tab-content" id="nav-tabContent">

                    <!-- Adult Fare Tab -->
                    <div class="tab-pane fade show {{ $fareTypes == 'Adult' ? 'active' : '' }}" id="nav-adult" role="tabpanel" aria-labelledby="nav-adult-tab">
                        <div class="card card-body border-0 shadow table-wrapper table-responsive">
                            <h2 class="mb-4 h5">{{ __('Add & View Stage Fares for Routes') }}</h2>

                            <form action={{ route('updateStageFare') }} method="post">
                                @csrf
                                <table id="adultFare" class="table table-hover">
                                    <thead>
                                    <tr>
                                        <th class="border-gray-200">{{ __('Order') }}</th>
                                        <th class="border-gray-200">{{ __('Stage Name') }}</th>
                                        @foreach($stages as $stage)
                                            <th class="border-gray-200">{{ $stage->stage_name }}</th>
                                        @endforeach
                                    </tr>
                                    </thead>
                                    <tbody id="each-fare">

                                    <input name="routeId" type="hidden" value="{{ $selectedRoute }}">
                                    <input name="fareType" type="hidden" value="{{ $fareTypes }}">

                                    @foreach ($stageTo as $toStage)
                                        <tr>
                                            <td style="display: none"><input name="routeId" type="hidden" value="{{ $selectedRoute }}">></td>
                                            <td><span class="fw-normal">{{ $toStage->stage_order}}</span></td>
                                            <td><span class="fw-normal">{{ $toStage->stage_name }}</span></td>

                                            @for($i=0; $i<$toStage->stage_order; $i++)
                                                @if( isset($stageFrom[$i]['id']))
                                                    @php
                                                    $result = 0;
                                                    @endphp
                                                    @foreach ($stageFares as $stageFare)
                                                        @if(($stageFare->tostage_stage_id == $toStage->id) && ($stageFare->fromstage_stage_id == $stageFrom[$i]['id']))
                                                            @php
                                                                $result = $stageFare->fare
                                                            @endphp
                                                        @endif
                                                    @endforeach

                                                    @if($result)
                                                        <td>
                                                            <label>
                                                                <input style="width: 80px;height: 30px" name="fare[]" class="update form-control border-gray-300" type="number" step=".01" value="{{ $result }}" required>
                                                                @if ($errors->has('fare'))
                                                                    <span class="text-danger">{{ $errors->first('fare') }}</span>
                                                                @endif
                                                            </label>
                                                            <input name="toStage[]" type="hidden" value="{{ $toStage->id }}" required>
                                                            <input name="fromStage[]" type="hidden" value="{{ $stageFrom[$i]['id']}}" required>
                                                        </td>
                                                    @else
                                                        <td>
                                                            <label>
                                                                <input name="fare[]" class="update form-control border-gray-300" type="number" step=".01" placeholder="Enter fare">
                                                            </label>
                                                            <input name="toStage[]" type="hidden" value="{{ $toStage->id }}">
                                                            <input name="fromStage[]" type="hidden" value="{{ $stageFrom[$i]['id']}}">
                                                        </td>
                                                    @endif
                                                @endif
                                            @endfor
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            <br>
                            <button class="btn btn-primary" type="submit">Save Changes</button>
                            </form>
                        </div>
                    </div>
                    <!-- End of Adult Fare Tab -->

                    <!-- Concession Fare Tab -->
                    <div class="tab-pane fade show {{ $fareTypes == 'Concession' ? 'active' : '' }}" id="nav-concession" role="tabpanel" aria-labelledby="nav-concession-tab">
                        <div class="card card-body border-0 shadow table-wrapper table-responsive">
                            <h2 class="mb-4 h5">{{ __('Add & View Stage Fares for Routes') }}</h2>

                            <table id="adultFare" class="table table-hover">
                                <thead>
                                <tr>
                                    <th class="border-gray-200">{{ __('Order') }}</th>
                                    <th class="border-gray-200">{{ __('Stage Name') }}</th>
                                    @foreach($stages as $stage)
                                        <th class="border-gray-200">{{ $stage->stage_name }}</th>
                                    @endforeach
                                </tr>
                                </thead>
                                <tbody id="each-fare">

                                    @foreach ($stageTo as $toStage)
                                    <tr>
                                        <td><span class="fw-normal">{{ $toStage->stage_order}}</span></td>
                                        <td><span class="fw-normal">{{ $toStage->stage_name }}</span></td>
                                        @for($i=0; $i<$toStage->stage_order; $i++)
                                            @if( isset($stageFrom[$i]['id']))
                                                @php
                                                    $result = 0;
                                                @endphp
                                                @foreach ($stageFares as $stageFare)
                                                    @if(($stageFare->tostage_stage_id == $toStage->id) && ($stageFare->fromstage_stage_id == $stageFrom[$i]['id']))
                                                        @php
                                                            $result = $stageFare->consession_fare
                                                        @endphp
                                                    @endif
                                                @endforeach
                                                @if($result)
                                                    <td>
                                                        <span>{{$result}}</span>
                                                    </td>
                                                @else
                                                    <td>
                                                        <span>No Adult Fare</span>
                                                    </td>
                                                @endif
                                            @endif
                                        @endfor
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <!-- Button Modal -->
                            <div class="d-block mb-md-0" style="position: relative">
                                <button wire:click.prevent="modalDisc({{ $selectedRoute }})" class="btn btn-warning">Apply Discount</button>
                            </div>
                        </div>
                    </div>
                    <!-- End of Concession Fare Tab -->
                </div>
                <!-- End of Tab -->

            </div>
            <div class="card-footer px-3 border-0 d-flex flex-column flex-lg-row align-items-center justify-content-between">
                {{--{{ $users->links() }}--}}
            </div>
        </div>
    @endif

    <!-- Apply Discount Modal Content -->
    <div wire:ignore.self class="modal fade" id="modalDiscount" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-md-5">
                    <h2 class="h4 text-center">
                        <span>Copy Adult Fare & Apply Discount</span>
                    </h2>
                    <br>

                    <!-- Form -->
                    <form wire:submit.prevent="{{ 'applyDiscount' }}">
                    @csrf
                        <div class="form-group mb-4">
                            <label for="discount">Enter Concession Fare Discount (%)</label>
                            <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-percent" viewBox="0 0 16 16">
                                        <path d="M13.442 2.558a.625.625 0 0 1 0 .884l-10 10a.625.625 0 1 1-.884-.884l10-10a.625.625 0 0 1 .884 0zM4.5 6a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm0 1a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5zm7 6a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm0 1a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"/>
                                    </svg>
                                </span>
                                <input wire:model.defer="state.discount" class="form-control border-gray-300" id="discount" placeholder="{{ __('Discount') }}" autofocus required>
                                @if ($errors->has('discount'))
                                    <span class="text-danger">{{ $errors->first('discount') }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary" id="btnSave">
                                <span>Save Changes</span>
                            </button>
                        </div>
                    </form>
                </div>
                <div class="modal-header"></div>
            </div>
        </div>
    </div>
    <!-- End of Apply Discount Modal Content -->
</div>
@push('script')
    <script>
        window.addEventListener('show-disc-form', event => {
            $('#modalDiscount').modal('show');
        });
        window.addEventListener('hide-disc-edit', event => {
            $('#modalDiscount').modal('hide');
            toastr.success(event.detail.message, 'Concession fare updated successfully!');
        });
        window.addEventListener('hide-disc-failed', event => {
            $('#modalDiscount').modal('hide');
            toastr.error(event.detail.message, 'Operation Failed!');
        });
    </script>
@endpush
