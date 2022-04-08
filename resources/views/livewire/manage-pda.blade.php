<div class="main py-4">
    <div class="d-block mb-md-0" style="position: relative">
        <h2>Manage PDA</h2>
        <button wire:click.prevent="addNew" class="buttonAdd btn btn-gray-800 d-inline-flex align-items-center me-2" data-bs-toggle="modal" data-bs-target="#modalEdit">
            <i class="fa fa-plus-circle mr-1 fa-fw"></i>
            Add PDA
        </button>
    </div>
    <div class="d-block mb-md-0" style="position: relative">
        <select wire:model="selectedRegion" class="form-select fmxw-200 d-none d-md-inline">
            <option value="">Choose Region</option>
            @foreach($regions as $region)
                <option value="{{$region->id}}">{{$region->description}}</option>
            @endforeach
        </select>

        @if (!is_null($selectedRegion))
            <select wire:model="selectedCompany"  class="form-select fmxw-200 d-none d-md-inline">
                <option value="">Choose Company</option>
                @foreach($companies as $company)
                    <option value="{{$company->id}}">{{$company->company_name}}</option>
                @endforeach
            </select>
        @endif
    </div>

    @if (!is_null($selectedCompany))
        <div class="card card-body border-0 shadow table-wrapper table-responsive">
            <h2 class="mb-4 h5">{{ __('All PDAs') }}</h2>
            <table class="table table-hover">
                <thead>
                <tr>
                    <th class="border-gray-200">{{ __('PDA Tag') }}</th>
                    <th class="border-gray-200">{{ __('IMEI') }}</th>
                    <th class="border-gray-200">{{ __('Status') }}</th>
                    <th class="border-gray-200">{{ __('Date Created') }}</th>
                    <th class="border-gray-200">{{ __('Date Registered') }}</th>
                    <th class="border-gray-200">Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($pdas as $pda)
                    <tr>
                        <td><span class="fw-normal">{{ $pda->pda_tag }}</span></td>
                        <td><span class="fw-normal">{{ $pda->imei }}</span></td>
                        @if($pda->status==1)
                            <td><span class="fw-normal">ACTIVE</span></td>
                        @else
                            <td><span class="fw-normal">INACTIVE</span></td>
                        @endif

                        <td><span class="fw-normal">{{ $pda->date_created }}</span></td>
                        <td><span class="fw-normal">{{ $pda->date_registered }}</span></td>
                        <td>
                            <!-- Button Modal -->
                            <button wire:click.prevent="edit({{ $pda }})" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modalEdit">Edit</button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <!-- Edit/Create PDA Modal Content -->
    <div wire:ignore.self class="modal fade" id="modalEdit" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-md-5">
                    <h2 class="h4 text-center">
                        @if($showEditModal)
                            <span>Edit PDA Details</span>
                        @else
                            <span>Add New PDA</span>
                        @endif
                    </h2>

                    <!-- Form -->
                    <form wire:submit.prevent="{{ $showEditModal ? 'updatePDA' : 'createPDA' }}">
                        @csrf
                        <div class="form-group mb-4">
                            <label for="pda_tag">PDA Tag</label>
                            <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pin-map-fill" viewBox="0 0 16 16">
                                        <path fill-rule="evenodd" d="M3.1 11.2a.5.5 0 0 1 .4-.2H6a.5.5 0 0 1 0 1H3.75L1.5 15h13l-2.25-3H10a.5.5 0 0 1 0-1h2.5a.5.5 0 0 1 .4.2l3 4a.5.5 0 0 1-.4.8H.5a.5.5 0 0 1-.4-.8l3-4z"/>
                                        <path fill-rule="evenodd" d="M4 4a4 4 0 1 1 4.5 3.969V13.5a.5.5 0 0 1-1 0V7.97A4 4 0 0 1 4 3.999z"/>
                                    </svg>
                                </span>
                                <input wire:model.defer="state.pda_tag" class="form-control border-gray-300" id="pda_tag" placeholder="{{ __('PDA Tag') }}" autofocus required>
                                @if ($errors->has('pda_tag'))
                                    <span class="text-danger">{{ $errors->first('pda_tag') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="imei">IMEI</label>
                            <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-signpost-fill" viewBox="0 0 16 16">
                                        <path d="M7.293.707A1 1 0 0 0 7 1.414V4H2a1 1 0 0 0-1 1v4a1 1 0 0 0 1 1h5v6h2v-6h3.532a1 1 0 0 0 .768-.36l1.933-2.32a.5.5 0 0 0 0-.64L13.3 4.36a1 1 0 0 0-.768-.36H9V1.414A1 1 0 0 0 7.293.707z"/>
                                    </svg>
                                </span>
                                <input wire:model.defer="state.imei" class="form-control border-gray-300" id="imei" placeholder="{{ __('IMEI') }}" autofocus required>
                                @if ($errors->has('imei'))
                                    <span class="text-danger">{{ $errors->first('imei') }}</span>
                                @endif
                            </div>
                        </div>
                        @if($showEditModal)
                            <div class="form-group mb-4">
                                <label for="region">Region</label>
                                <div class="input-group">
                                <span class="input-group-text" id="basic-addon1">
                                     <i class="fa fa-project-diagram"></i>
                                </span>
                                    <select wire:model="state.region_id" id="region" class="form-select border-gray-300"  autofocus required>
                                        <option value="">Choose Region</option>
                                        @foreach($regionModals as $regionModal)
                                            <option value="{{$regionModal->id}}">{{$regionModal->description}}</option>
                                        @endforeach
                                    </select>
                                    @if ($errors->has('region'))
                                        <span class="text-danger">{{ $errors->first('region') }}</span>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="form-group mb-4">
                                <label for="region">Region</label>
                                <div class="input-group">
                                <span class="input-group-text" id="basic-addon1">
                                     <i class="fa fa-project-diagram"></i>
                                </span>
                                    <select wire:model="selectedRegionModal" id="region" class="form-select border-gray-300" autofocus required>
                                        <option value="">Choose Region</option>
                                        @foreach($regionModals as $regionModal)
                                            <option value="{{$regionModal->id}}">{{$regionModal->description}}</option>
                                        @endforeach
                                    </select>
                                    @if ($errors->has('region'))
                                        <span class="text-danger">{{ $errors->first('region') }}</span>
                                    @endif
                                </div>
                            </div>
                        @endif
                        @if($selectedRegionModal)
                            <div class="form-group mb-4">
                                <label for="company">Company</label>
                                <div class="input-group">
                                    <span class="input-group-text" id="basic-addon1">
                                        <i class="fas fa-building fa-fw"></i>
                                    </span>
                                    <select wire:model.defer="state.company_id" class="form-select border-gray-300"  autofocus required>
                                        <option value="">Choose Company</option>
                                        @foreach($companyModals as $companyModal)
                                            <option value="{{$companyModal->id}}">{{$companyModal->company_name}}</option>
                                        @endforeach
                                    </select>
                                    @if ($errors->has('company'))
                                        <span class="text-danger">{{ $errors->first('company') }}</span>
                                    @endif
                                </div>
                            </div>
                        @endif
                        <div class="form-group mb-4">
                            <label for="status">Status</label>
                            <div class="input-group">
                                <span class="input-group-text" id="basic-addon1">
                                    <i class="fas fa-user-check fa-fw"></i>
                                </span>
                                <select wire:model.defer="state.status" id="status" class="form-select border-gray-300"  autofocus required>
                                    <option value="">Choose Status</option>
                                    <option value="1">Active</option>
                                    <option value="2">Inactive</option>
                                </select>
                                @if ($errors->has('status'))
                                    <span class="text-danger">{{ $errors->first('status') }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                @if($showEditModal)
                                    <span>Save Changes</span>
                                @else
                                    <span>Save</span>
                                @endif
                            </button>
                        </div>
                    </form>
                </div>
                <div class="modal-header"></div>
            </div>
        </div>
    </div>
    <!-- End of Edit PDA Modal Content -->

    <div
        class="card-footer px-3 border-0 d-flex flex-column flex-lg-row align-items-center justify-content-between">
        {{--{{ $users->links() }}--}}
    </div>
</div>

