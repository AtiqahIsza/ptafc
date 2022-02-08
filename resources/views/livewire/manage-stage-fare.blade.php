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

        @if (!is_null($selectedCompany))
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
                                <tbody>

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
                                                            $result = $stageFare->fare
                                                        @endphp
                                                    @endif
                                                @endforeach
                                                
                                                @if($result)
                                                    <td>
                                                        <label>
                                                            <input name="fare" class="update form-control border-gray-300" data-type="text"
                                                                data-pk="{{ $toStage->id }}" data-title="Enter fare" value="{{ $result }}">
                                                            @if ($errors->has('companyName'))
                                                                <span class="text-danger">{{ $errors->first('companyName') }}</span>
                                                            @endif
                                                        </label>
                                                    </td>
                                                @else
                                                    <td>
                                                        <label>
                                                            <input name="fare[{{$toStage->id}}][{{$stageFrom[$i]['id']}}]" class="update form-control border-gray-300" data-type="text"
                                                                data-pk="{{ $toStage->id }}" data-title="Enter fare" placeholder="Enter fare">
                                                        </label>
                                                    </td>
                                                @endif
                                            @endif
                                        @endfor

                                {{--@foreach ($stageFrom as $fromStage)
                                    --}}{{--@php
                                            $stageTo = array();
                                    @endphp--}}{{--
                                    <tr>
                                        <td><span class="fw-normal">{{ $fromStage->stage_order}}</span></td>
                                        <td><span class="fw-normal">{{ $fromStage->stage_name }}</span></td>
                                        @for($i=0; $i<$maxColumns; $i++)
                                            @if( isset($stageTo[$i]['id']))
                                                @if(($stageFares->contains('fromstage_stage_id', $fromStage->id)) && ($stageFares->contains('tostage_stage_id', $stageTo[$i]['id'])))
                                                --}}{{--@if(($stageFares->fromstage_stage_id == $stageFrom->id) && ($stageFares->tostage_stage_id == $stageTo[$i]['id']))--}}{{--
                                                    <td>
                                                        <label>
                                                            <input name="fare[{{$fromStage->id}}][{{$stageTo[$i]['id']}}]" class="update form-control border-gray-300" data-type="text"
                                                                   data-pk="{{ $fromStage->id }}" data-title="Enter fare"
                                                                   value="{{ $stageTo[$i]['id'] }}">
                                                        </label>
                                                    </td>
                                                @else
                                                    <td></td>
                                                @endif
                                            @endif
                                        @endfor
                                    </tr>
                                    @endforeach--}}
                                        {{--@foreach ($stages as $stageTo)
                                            @php $i=0 @endphp
                                            @if($i<$stageTo->stage_order)
                                                @if( isset($stageFrom->stageFare->fromstage_stage_id))
                                                    @if(($stageFare->fromstage_stage_id == $stageFrom->id) && ($stageFare->tostage_stage_id == $stageTo->id))
                                                        @php $i++ @endphp
                                                        <td>
                                                            <label>
                                                                <input name="fare[{{$stageFrom->id}}][{{$stageTo->id}}]" class="update form-control border-gray-300" data-type="text"
                                                                       data-pk="{{ $stageFrom->id }}" data-title="Enter fare"
                                                                       value="{{ $stageFare->fare }}">
                                                            </label>
                                                        </td>
                                                    @else
                                                        @php $i++ @endphp
                                                        <td>
                                                            <label>
                                                                <input name="fare[{{$stageFrom->id}}][{{$stageTo->id}}}]" class="update form-control border-gray-300" data-type="text"
                                                                       data-pk="{{ $stageFrom->id }}" data-title="Enter fare">
                                                            </label>
                                                        </td>
                                                    @endif
                                                @else
                                                    @php $i++ @endphp
                                                    <td>
                                                        <label>
                                                            <input name="fare[{{$stageFrom->id}}][{{$stageTo->id}}}]" class="update form-control border-gray-300" data-type="text"
                                                                   data-pk="{{ $stageFrom->id }}" data-title="Enter fare">
                                                        </label>
                                                    </td>
                                                @endif
                                            @endif
                                        @endforeach--}}
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                            {{-- <button class="btn btn-primary">Save Changes</button> --}}
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
                                        <th class="border-gray-200">{{ $loop->index+1 }}</th>
                                    @endforeach
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($stages as $stage)
                                    <tr>
                                        <td><span class="fw-normal">{{ $stage->stage_order}}</span></td>
                                        <td><span class="fw-normal">{{ $stage->stage_name }}</span></td>
                                        @foreach($stageFares as $stageFare)
                                            @if($fareTypes=='Concession')
                                                <td>
                                                    <label>
                                                        <input class="update form-control border-gray-300" data-type="text" data-pk="{{ $stage->id }}" data-title="Enter fare" value="{{ $stageFare->consession_fare }}">
                                                    </label>
                                                </td>
                                            @endif
                                        @endforeach
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                            <button class="btn btn-primary">Save Concession Fare</button>
                        </div>
                    </div>
                    <!-- End of Concession Fare Tab -->
                </div>
                <!-- End of Tab -->
            </div>
            {{--<div class="col-12">
                <!-- Tab -->
                <nav>
                    <div class="nav nav-tabs mb-4" id="nav-tab" role="tablist">
                        <a class="nav-item nav-link {{ $fareTypes == 'Adult' ? 'active' : '' }}" id="nav-adult-tab" data-bs-toggle="tab" href="#nav-adult" role="tab"
                           aria-controls="nav-adult" aria-selected="true" wire:click="fareType({{ $selectedRoute }},'Adult')">Adult Fare</a>
                        <a class="nav-item nav-link {{ $fareTypes == 'Consession' ? 'active' : '' }}" id="nav-concession-tab" data-bs-toggle="tab" href="#nav-concession" role="tab"
                           aria-controls="nav-concession" aria-selected="false" wire:click="fareType({{ $selectedRoute }},'Consession')">Concession Fare</a>
                    </div>
                </nav>

                <div class="tab-content" id="nav-tabContent" wire:model="fareTypes">
                    <div class="card card-body border-0 shadow table-wrapper table-responsive">
                        <h2 class="mb-4 h5">{{ __('Add & View Stage Fares for Routes') }}</h2>
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                <th class="border-gray-200">{{ __('Order') }}</th>
                                <th class="border-gray-200">{{ __('Stage Name') }}</th>
                                @foreach($stages as $stage)
                                    <th class="border-gray-200">{{ $loop->index+1 }}</th>
                                @endforeach
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($stages as $stage)
                                <tr>
                                    <td><span class="fw-normal">{{ $stage->stage_order}}</span></td>
                                    <td><span class="fw-normal">{{ $stage->stage_name }}</span></td>
                                    @foreach($stageFares as $stageFare)
                                        @if($fareTypes=='Adult')
                                            <td>
                                                <input class="update form-control border-gray-300" data-type="text" data-pk="{{ $stage->id }}" data-title="Enter fare" value="{{ $stageFare->adult_fare }}">
                                                --}}{{--<input wire:model.defer="state.adult_fare" class="form-control border-gray-300" id="concession_fare" placeholder="{{ __('0.00') }}" autofocus required>
                                                <input wire:model.defer="state.stage_id" type="hidden" class="form-control border-gray-300" id="concession_fare">
                                                <input wire:model.defer="state.stage_order" type="hidden" class="form-control border-gray-300" id="concession_fare" placeholder="{{ __('0.00') }}" autofocus required>--}}{{--

                                            </td>
                                        @else
                                            <td>
                                                <a href="" class="update" class="form-control border-gray-300" data-type="text" data-pk="{{ $stage->id }}" data-title="Enter fare">{{ $stageFare->adult_fare }}</a>
                                                --}}{{--<input wire:model.defer="state.concession_fare" class="form-control border-gray-300" id="concession_fare" placeholder="{{ __('0.00') }}" autofocus required>--}}{{--
                                            </td>



                                        --}}{{--@if($stageFare->fromstage_stage_id == $stage->id)

                                       --}}{{----}}{{-- @if($stageFare->fromstage_stage_id == $stageFare->tostage_stage_id)--}}{{----}}{{--
                                            @if($fareTypes=='Adult')
                                                <td><span class="border-gray-200">{{ $stageFare->fare }}</span>
                                                    --}}{{----}}{{--<input class="form-control border-gray-300" placeholder="{{ __('Fare') }}">--}}{{----}}{{--
                                                </td>
                                            @else
                                                <td><span class="border-gray-200">{{ $stageFare->consession_fare }}</span>
                                                    --}}{{----}}{{--<input class="form-control border-gray-300" id="fare" placeholder="{{ __('Fare') }}">--}}{{----}}{{--
                                                </td>
                                            @endif
                                        @else
                                            <td><span class="border-gray-200">N/A</span>
                                                --}}{{----}}{{--<input class="form-control border-gray-300" id="fare" placeholder="{{ __('Fare') }}">--}}{{----}}{{--
                                            </td>--}}{{--
                                        @endif
                                    @endforeach
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <!-- End of tab -->
                </div>
            </div>--}}
            <div class="card-footer px-3 border-0 d-flex flex-column flex-lg-row align-items-center justify-content-between">
                {{--{{ $users->links() }}--}}
            </div>
        </div>
        @endif
    @endif
</div>

@section('script')
    <script>
        $.fn.editable.defaults.mode = 'inline';

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': '{{csrf_token()}}'
            }
        });

        $('.update').editable({
            url: "{{ route('updateStageFare') }}",
            type: 'text',
            pk: 1,
            name: 'adult_fare',
            title: 'Enter stage fare'
        });
    </script>
@endsection
