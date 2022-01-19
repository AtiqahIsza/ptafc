<div class="main py-4">
    <div class="d-block mb-md-0" style="position: relative">
        <h2>Manage Sectors</h2>
        <button wire:click.prevent="addNew" class="buttonAdd-map btn btn-primary d-inline-flex align-items-center me-2" data-bs-toggle="modal" data-bs-target="#modalEdit">
            <i class="fa fa-plus-circle mr-1 fa-fw"></i>
            Add Sector
        </button>
    </div>
    <div class="col-9 col-lg-8 d-md-flex">
        <select wire:model="selectedCompany" class="form-select fmxw-200 d-none d-md-inline"  >
            <option value="">Choose Company</option>
            @foreach($companies as $company)
                <option value="{{$company->id}}">{{$company->company_name}}</option>
            @endforeach
        </select>
    </div>
    <br>

    @if (!is_null($selectedCompany))
        <div class="card card-body border-0 shadow table-wrapper table-responsive">
            <h2 class="mb-4 h5">{{ __('All Sectors by Company') }}</h2>
            <table class="table table-hover">
                <thead>
                <tr>
                    <th class="border-gray-200">{{ __('Sector Name') }}</th>
                    <th class="border-gray-200">{{ __('Company Name') }}</th>
                    <th class="border-gray-200">Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($sectors as $sector)
                    <tr>
                        <td><span class="fw-normal">{{ $sector->sector_name }}</span></td>
                        <td><span class="fw-normal">{{ $sector->company->company_name }}</span></td>
                        <td>
                            <!-- Button Modal -->
                            <button wire:click.prevent="edit({{ $sector }})" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modalEdit">Edit</button>
                            <button wire:click.prevent="confirmRemoval({{ $sector->id }})" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirmationModal">Remove</button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <div class="card-footer px-3 border-0 d-flex flex-column flex-lg-row align-items-center justify-content-between">
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
                            <span>Edit Sector Details</span>
                        @else
                            <span>Add New Sector</span>
                        @endif
                    </h2>

                    <!-- Form -->
                    <form wire:submit.prevent="{{ $showEditModal ? 'updateSector' : 'createSector' }}">
                        @csrf
                        <div class="form-group mb-4">
                            <label for="sectorName">Sector Name</label>
                            <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <i class="fa fa-project-diagram"></i>
                                </span>
                                <input wire:model.defer="state.sector_name" class="form-control border-gray-300" id="sectorName" placeholder="{{ __('Sector Name') }}" autofocus required>
                                @if ($errors->has('sectorName'))
                                    <span class="text-danger">{{ $errors->first('sectorName') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="company">Company</label>
                            <div class="input-group">
                                <span class="input-group-text" id="basic-addon1">
                                    <i class="fas fa-building fa-fw"></i>
                                </span>
                                <select wire:model.defer="state.company_id" class="form-control border-gray-300" autofocus required>
                                    <option value="">Choose Company</option>
                                    @foreach($companies as $company)
                                        <option value="{{$company->id}}">{{$company->company_name}}</option>
                                    @endforeach
                                </select>
                                @if ($errors->has('company'))
                                    <span class="text-danger">{{ $errors->first('company') }}</span>
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
    <!-- End of Edit User Modal Content -->

    <!-- Remove User Modal -->
    <div wire:ignore.self class="modal fade" id="confirmationModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5>Remove Sector</h5>
                </div>

                <div class="modal-body">
                    <h4>Are you sure you want to remove this sector?</h4>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa fa-times mr-1"></i> Cancel</button>
                    <button type="button" wire:click.prevent="removeSector" class="btn btn-danger" data-bs-dismiss="modal"><i class="fa fa-trash mr-1"></i>Remove Sector</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End of Remove User Modal Content -->
</div>

@section('script')
    <script>
        window.addEventListener('show-form', event => {
            $('#modalEdit').modal('show');
        });
        window.addEventListener('hide-form', event => {
            $('#modalEdit').modal('hide');
            toastr.success(event.detail.message, 'Success!');
        });
        window.addEventListener('show-delete-form', event => {
            $('#confirmationModal').modal('show');
        });
        window.addEventListener('hide-delete-modal', event => {
            $('#confirmationModal').modal('hide');
            toastr.success(event.detail.message, 'Success!');
        })
    </script>
@endsection
