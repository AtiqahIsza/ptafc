<div class="main py-4">
    <div class="d-block mb-md-0" style="position: relative">
        <h2>Manage Companies</h2>
        <button wire:click.prevent="addNew" class="buttonAdd btn btn-gray-800 d-inline-flex align-items-center me-2" data-bs-toggle="modal" data-bs-target="#modalEdit">
            <i class="fa fa-plus-circle mr-1 fa-fw"></i>
            Add Company
        </button>
    </div>
    <div class="col-9 col-lg-8 d-md-flex">
        <select wire:model="selectedRegion"  class="form-select fmxw-200 d-none d-md-inline">
            <option value="">Choose Region</option>
            @foreach($regions as $region)
                <option value="{{$region->id}}">{{$region->description}}</option>
            @endforeach
        </select>
    </div>

    @if (!is_null($selectedRegion))
        <div class="card card-body border-0 shadow table-wrapper table-responsive">
            <h2 class="mb-4 h5">{{ __('All Companies by Region') }}</h2>

            <table class="table table-hover">
                <thead>
                <tr>
                    <th class="border-gray-200">{{ __('Company Name') }}</th>
                    <th class="border-gray-200">{{ __('Address') }}</th>
                    <th class="border-gray-200">{{ __('Postcode') }}</th>
                    <th class="border-gray-200">{{ __('City') }}</th>
                    <th class="border-gray-200">{{ __('State') }}</th>
                    <th class="border-gray-200">Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($companies as $company)
                    <tr>
                        <td><span class="fw-normal">{{ $company->company_name }}</span></td>
                        <td><span class="fw-normal">{{ $company->address1 }} , {{ $company->address2 }} </span></td>
                        <td><span class="fw-normal">{{ $company->postcode}}</span></td>
                        <td><span class="fw-normal">{{ $company->city}}</span></td>
                        <td><span class="fw-normal">{{ $company->state}}</span></td>
                        <td>
                            <!-- Button Modal -->
                            <button wire:click.prevent="edit({{ $company }})" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modalEdit">Edit</button>
                            <button wire:click.prevent="confirmRemoval({{ $company->id }})" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirmationModal">Remove</button>
                        </td>

                    </tr>
                @endforeach
                </tbody>
            </table>
            <div
                class="card-footer px-3 border-0 d-flex flex-column flex-lg-row align-items-center justify-content-between">
                {{--{{ $users->links() }}--}}
            </div>
        </div>
    @endif

    <!-- Edit/Create Modal Content -->
    <div wire:ignore.self class="modal fade" id="modalEdit" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-md-5">
                    <h2 class="h4 text-center">
                        @if($showEditModal)
                            <span>Edit Company Details</span>
                        @else
                            <span>Add New Company</span>
                        @endif
                    </h2>

                    <form wire:submit.prevent="{{ $showEditModal ? 'updateCompany' : 'createCompany' }}">
                    @csrf
                    <!-- Form -->
                        <div class="form-group mb-4">
                            <label for="companyName">Company Name</label>
                            <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <i class="fas fa-building fa-fw"></i>
                                </span>
                                <input wire:model.defer="state.company_name" class="form-control border-gray-300" id="companyName" placeholder="{{ __('Company Name') }}" autofocus required>
                                @if ($errors->has('companyName'))
                                    <span class="text-danger">{{ $errors->first('companyName') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="companyType">Company Type</label>
                            <div class="input-group">
                                <span class="input-group-text" id="basic-addon1">
                                    <i class="fas fa-user-friends fa-fw"></i>
                                </span>
                                <select wire:model.defer="state.company_type" id="companyType" class="form-select border-gray-300" autofocus required>
                                    <option value="">Choose Company Type</option>
                                    <option value="0">Agent</option>
                                    <option value="1">Non-Agent</option>\
                                </select>
                                @if ($errors->has('companyType'))
                                    <span class="text-danger">{{ $errors->first('companyType') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="region">Region</label>
                            <div class="input-group">
                                <span class="input-group-text" id="basic-addon1">
                                    <i class="fas fa-address-card fa-fw"></i>
                                </span>
                                <select wire:model.defer="state.region_id" class="form-select border-gray-300" autofocus required>
                                    <option value="">Choose Region</option>
                                    @foreach($regions as $region)
                                        <option value="{{$region->id}}">{{$region->description}}</option>
                                    @endforeach
                                </select>
                                @if ($errors->has('region'))
                                    <span class="text-danger">{{ $errors->first('region') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="address1">Address 1</label>
                            <div class="input-group">
                               <span class="input-group-text" id="basic-addon1">
                                   <i class="fas fa-address-card fa-fw"></i>
                               </span>
                                <input wire:model.defer="state.address1" id="address1" class="form-control border-gray-300" placeholder="{{ __('Address Line 1') }}" autofocus required>
                                @if ($errors->has('address1'))
                                    <span class="text-danger">{{ $errors->first('address1') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="address2">Address 2</label>
                            <div class="input-group">
                               <span class="input-group-text" id="basic-addon1">
                                   <i class="fas fa-address-card fa-fw"></i>
                               </span>
                                <input wire:model.defer="state.address2" id="address2" class="form-control border-gray-300" placeholder="{{ __('Address Line 2') }}" autofocus required>
                                @if ($errors->has('address2'))
                                    <span class="text-danger">{{ $errors->first('address2') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="postcode">Postcode</label>
                            <div class="input-group">
                                <span class="input-group-text" id="basic-addon1">
                                    <i class="fas fa-address-card fa-fw"></i>
                                </span>
                                <input wire:model.defer="state.postcode" id="postcode" class="form-control border-gray-300" placeholder="{{ __('Postcode') }}" autofocus required>
                                @if ($errors->has('postcode'))
                                    <span class="text-danger">{{ $errors->first('postcode') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="city">City</label>
                            <div class="input-group">
                                <span class="input-group-text" id="basic-addon1">
                                    <i class="fas fa-address-card fa-fw"></i>
                                </span>
                                <input wire:model.defer="state.city" id="city" class="form-control border-gray-300" placeholder="{{ __('City') }}" autofocus required>
                                @if ($errors->has('city'))
                                    <span class="text-danger">{{ $errors->first('city') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="state">State</label>
                            <div class="input-group">
                                <span class="input-group-text" id="basic-addon1">
                                    <i class="fas fa-address-card fa-fw"></i>
                                </span>
                                <select wire:model.defer="state.state" id="state" class="form-select border-gray-300" autofocus required>
                                    <option value="">Choose State</option>
                                    <option value="Johor">Johor</option>
                                    <option value="Kedah">Kedah</option>
                                    <option value="Kelantan">Kelantan</option>
                                    <option value="Kuala Lumpu">Kuala Lumpur</option>
                                    <option value="Melaka">Melaka</option>
                                    <option value="Negeri Sembilan">Negeri Sembilan</option>
                                    <option value="Pahang">Pahang</option>
                                    <option value="Penang">Penang</option>
                                    <option value="Perak">Perak</option>
                                    <option value="Perlis">Perlis</option>
                                    <option value="Putrajaya">Putrajaya</option>
                                    <option value="Sabah">Sabah</option>
                                    <option value="Sarawak">Sarawak</option>
                                    <option value="Selangor">Selangor</option>
                                    <option value="Terengganu">Terengganu</option>
                                </select>
                                @if ($errors->has('state'))
                                    <span class="text-danger">{{ $errors->first('state') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="min">Minimum Balance for Agents (RM):</label>
                            <div class="input-group">
                                <span class="input-group-text" id="basic-addon1">
                                    <i class="fas fa-money-bill fa-fw"></i>
                                </span>
                                <input wire:model.defer="state.minimum_balance" id="min" class="form-control border-gray-300" placeholder="{{ __('Minimum Balance for Agents in RM') }}" autofocus required>
                                @if ($errors->has('min'))
                                    <span class="text-danger">{{ $errors->first('min') }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary" id="btnSave">
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
    <!-- End of Edit Company Modal Content -->

    <!-- Remove Company Modal -->
    <div wire:ignore.self class="modal fade" id="confirmationModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5>Remove Company</h5>
                </div>

                <div class="modal-body">
                    <h4>Are you sure you want to remove this company?</h4>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa fa-times mr-1"></i> Cancel</button>
                    <button type="button" wire:click.prevent="removeCompany" class="btn btn-danger"><i class="fa fa-trash mr-1"></i>Remove Company</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End of Remove User Modal Content -->
</div>
