<div class="main py-4">
    <div class="d-block mb-md-0" style="position: relative">
        <h2>Manage Stages</h2>
        <button wire:click.prevent="addNew" class="buttonAdd btn btn-gray-800 d-inline-flex align-items-center me-2">
            <i class="fa fa-plus-circle mr-1 fa-fw"></i>
            Add Stage
        </button>
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
        <div class="card card-body border-0 shadow table-wrapper table-responsive">
            <h2 class="mb-4 h5">{{ __('All Stages by Company and Route') }}</h2>
            <table class="table table-hover">
                <thead>
                <tr>
                    <th class="border-gray-200">{{ __('Sequence') }}</th>
                    <th class="border-gray-200">{{ __('Stage Name') }}</th>
                    <th class="border-gray-200">{{ __('Stage Number') }}</th>
                    <th class="border-gray-200">{{ __('Distance (KM)') }}</th>
                    <th class="border-gray-200">{{ __('Stage Map') }}</th>
                    <th class="border-gray-200">Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($stages as $stage)
                    <tr>
                        <td><span class="fw-normal">{{ $stage->stage_order }}</span></td>
                        <td><span class="fw-normal">{{ $stage->stage_name }}</span></td>
                        <td><span class="fw-normal">{{ $stage->stage_number }}</span></td>
                        <td><span class="fw-normal">{{ $stage->no_of_km }}</span></td>
                        @php
                            $result = false;
                        @endphp
                        @foreach($stageMaps as $stageMap)
                            @if($stageMap->stage_id == $stage->id)
                                @php
                                    $result = true;
                                @endphp
                            @endif
                        @endforeach

                        @if($result)
                            <td>
                                <!-- Button for preview stage map-->
                                <button onclick="window.location='{{ route('viewStageMap', $stage->id) }}'" class="btn btn-success">View</button>
                                <button wire:click.prevent="confirmRemovalMap({{ $stage->id }})" class="btn btn-danger">Remove</button>
                            </td>
                        @else
                            <td>
                                <!-- Button for creating map-->
                                <button onclick="window.location='{{ route('addStageMap', $stage->id) }}'" class="btn btn-primary">Create</button>
                            </td>
                        @endif
                        <td>
                            <!-- Button Modal -->
                            <button wire:click.prevent="edit({{ $stage }})" class="btn btn-warning">Edit</button>
                            <button wire:click.prevent="confirmRemoval({{ $stage->id }})" class="btn btn-danger">Remove</button>
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
                            <span>Edit Stage Details</span>
                        @else
                            <span>Add New Stage</span>
                        @endif
                    </h2>

                    <!-- Form -->
                    <form wire:submit.prevent="{{ $showEditModal ? 'updateStage' : 'createStage' }}">
                        @csrf
                        <div class="form-group mb-4">
                            <label for="stageName">Stage Name</label>
                            <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pin-map-fill" viewBox="0 0 16 16">
                                        <path fill-rule="evenodd" d="M3.1 11.2a.5.5 0 0 1 .4-.2H6a.5.5 0 0 1 0 1H3.75L1.5 15h13l-2.25-3H10a.5.5 0 0 1 0-1h2.5a.5.5 0 0 1 .4.2l3 4a.5.5 0 0 1-.4.8H.5a.5.5 0 0 1-.4-.8l3-4z"/>
                                        <path fill-rule="evenodd" d="M4 4a4 4 0 1 1 4.5 3.969V13.5a.5.5 0 0 1-1 0V7.97A4 4 0 0 1 4 3.999z"/>
                                    </svg>
                                </span>
                                <input wire:model.defer="state.stage_name" class="form-control border-gray-300" id="stageName" placeholder="{{ __('Stage Name') }}" autofocus required>
                                @if ($errors->has('stageName'))
                                    <span class="text-danger">{{ $errors->first('stageName') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="stageNum">Stage Number</label>
                            <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-signpost-fill" viewBox="0 0 16 16">
                                        <path d="M7.293.707A1 1 0 0 0 7 1.414V4H2a1 1 0 0 0-1 1v4a1 1 0 0 0 1 1h5v6h2v-6h3.532a1 1 0 0 0 .768-.36l1.933-2.32a.5.5 0 0 0 0-.64L13.3 4.36a1 1 0 0 0-.768-.36H9V1.414A1 1 0 0 0 7.293.707z"/>
                                    </svg>
                                </span>
                                <input wire:model.defer="state.stage_number" class="form-control border-gray-300" id="stageNum" placeholder="{{ __('Stage Number') }}">
                                @if ($errors->has('stageNum'))
                                    <span class="text-danger">{{ $errors->first('stageNum') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="seq">Sequence Number</label>
                            <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                   <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-sort-numeric-down" viewBox="0 0 16 16">
                                      <path d="M12.438 1.668V7H11.39V2.684h-.051l-1.211.859v-.969l1.262-.906h1.046z"/>
                                      <path fill-rule="evenodd" d="M11.36 14.098c-1.137 0-1.708-.657-1.762-1.278h1.004c.058.223.343.45.773.45.824 0 1.164-.829 1.133-1.856h-.059c-.148.39-.57.742-1.261.742-.91 0-1.72-.613-1.72-1.758 0-1.148.848-1.835 1.973-1.835 1.09 0 2.063.636 2.063 2.687 0 1.867-.723 2.848-2.145 2.848zm.062-2.735c.504 0 .933-.336.933-.972 0-.633-.398-1.008-.94-1.008-.52 0-.927.375-.927 1 0 .64.418.98.934.98z"/>
                                      <path d="M4.5 2.5a.5.5 0 0 0-1 0v9.793l-1.146-1.147a.5.5 0 0 0-.708.708l2 1.999.007.007a.497.497 0 0 0 .7-.006l2-2a.5.5 0 0 0-.707-.708L4.5 12.293V2.5z"/>
                                   </svg>
                                </span>
                                <input wire:model.defer="state.stage_order" class="form-control border-gray-300" id="seq" placeholder="{{ __('Sequence Number') }}" autofocus required>
                            </div>
                            @if ($errors->has('seq'))
                                <span class="text-danger">{{ $errors->first('seq') }}</span>
                            @endif
                            <div class="text-danger">
                                {{ session('message') }}
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="nokm">Number of Kilometers</label>
                            <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-123" viewBox="0 0 16 16">
                                      <path d="M2.873 11.297V4.142H1.699L0 5.379v1.137l1.64-1.18h.06v5.961h1.174Zm3.213-5.09v-.063c0-.618.44-1.169 1.196-1.169.676 0 1.174.44 1.174 1.106 0 .624-.42 1.101-.807 1.526L4.99 10.553v.744h4.78v-.99H6.643v-.069L8.41 8.252c.65-.724 1.237-1.332 1.237-2.27C9.646 4.849 8.723 4 7.308 4c-1.573 0-2.36 1.064-2.36 2.15v.057h1.138Zm6.559 1.883h.786c.823 0 1.374.481 1.379 1.179.01.707-.55 1.216-1.421 1.21-.77-.005-1.326-.419-1.379-.953h-1.095c.042 1.053.938 1.918 2.464 1.918 1.478 0 2.642-.839 2.62-2.144-.02-1.143-.922-1.651-1.551-1.714v-.063c.535-.09 1.347-.66 1.326-1.678-.026-1.053-.933-1.855-2.359-1.845-1.5.005-2.317.88-2.348 1.898h1.116c.032-.498.498-.944 1.206-.944.703 0 1.206.435 1.206 1.07.005.64-.504 1.106-1.2 1.106h-.75v.96Z"/>
                                    </svg>
                                </span>
                                <input wire:model.defer="state.no_of_km" class="form-control border-gray-300" id="nokm"  placeholder="{{ __('Number of Kilometers') }}" autofocus required>
                                @if ($errors->has('nokm'))
                                    <span class="text-danger">{{ $errors->first('nokm') }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group mb-4">
                            <label for="company">Company</label>
                            <div class="input-group">
                                <span class="input-group-text" id="basic-addon1">
                                    <i class="fas fa-building fa-fw"></i>
                                </span>
                                <select wire:model="selectedEditCompany" class="form-select border-gray-300" autofocus required>
                                    <option value="">Choose Company</option>
                                    @foreach($editedCompanies as $editedCompany)
                                        <option value="{{$editedCompany->id}}">{{$editedCompany->company_name}}</option>
                                    @endforeach
                                </select>
                                @if ($errors->has('company'))
                                    <span class="text-danger">{{ $errors->first('company') }}</span>
                                @endif
                            </div>
                        </div>
                        {{--@if($showEditModal)
                            <input wire:model.defer="state.route_id" type="hidden" class="form-control border-gray-300" id="route_id">
                        @endif--}}

                        <div class="form-group mb-4">
                            <label for=route">Route</label>
                            <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                     <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pin-map-fill" viewBox="0 0 16 16">
                                        <path fill-rule="evenodd" d="M3.1 11.2a.5.5 0 0 1 .4-.2H6a.5.5 0 0 1 0 1H3.75L1.5 15h13l-2.25-3H10a.5.5 0 0 1 0-1h2.5a.5.5 0 0 1 .4.2l3 4a.5.5 0 0 1-.4.8H.5a.5.5 0 0 1-.4-.8l3-4z"/>
                                        <path fill-rule="evenodd" d="M4 4a4 4 0 1 1 4.5 3.969V13.5a.5.5 0 0 1-1 0V7.97A4 4 0 0 1 4 3.999z"/>
                                    </svg>
                                </span>
                                <select wire:model.defer="state.route_id" id="route" class="form-select border-gray-300" autofocus required>
                                    <option value="">Choose Route</option>
                                    @foreach($editedRoutes as $editedRoute)
                                        <option value="{{$editedRoute->id}}">{{$editedRoute->route_name}}</option>
                                    @endforeach
                                </select>
                                @if ($errors->has('route'))
                                    <span class="text-danger">{{ $errors->first('route') }}</span>
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
                    <h5>Remove Stage</h5>
                </div>

                <div class="modal-body">
                    <h4>Are you sure you want to remove {{$removedStage}}?</h4>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa fa-times mr-1"></i> Cancel</button>
                    <button type="button" wire:click.prevent="removeStage" class="btn btn-danger" data-bs-dismiss="modal"><i class="fa fa-trash mr-1"></i>Remove Stage</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End of Remove User Modal Content -->

    <!-- Remove Stage Map Modal -->
    <div wire:ignore.self class="modal fade" id="mapConfirmationModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5>Remove Stage Map</h5>
                </div>

                <div class="modal-body">
                    <h4>Are you sure you want to remove stage map of {{$removedStage}}?</h4>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa fa-times mr-1"></i> Cancel</button>
                    <button type="button" wire:click.prevent="removeStageMap" class="btn btn-danger" data-bs-dismiss="modal"><i class="fa fa-trash mr-1"></i>Remove Stage Map</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End of Remove Stage Map Modal Content -->
</div>
@push('script')
    <script>
        window.addEventListener('show-form', event => {
            $('#modalEdit').modal('show');
        });
        window.addEventListener('hide-form-edit', event => {
            $('#modalEdit').modal('hide');
            toastr.success(event.detail.message, 'Stage updated successfully!');
        });
        window.addEventListener('hide-form-add', event => {
            $('#modalEdit').modal('hide');
            toastr.success(event.detail.message, 'New stage added successfully!');
        });
        window.addEventListener('hide-form-failed', event => {
            $('#modalEdit').modal('hide');
            toastr.error(event.detail.message, 'Operation Failed!');
        });
        window.addEventListener('show-error-existed', event => {
            $('#modalEdit').modal('hide');
            toastr.error(event.detail.message, 'Failed! Sequence of order already exist for this stage!');
        });

        window.addEventListener('show-delete-modal', event => {
            $('#confirmationModal').modal('show');
        });
        window.addEventListener('hide-delete-modal', event => {
            $('#confirmationModal').modal('hide');
            toastr.success(event.detail.message, 'Stage removed successfully!');
        })

        window.addEventListener('show-delete-map-modal', event => {
            $('#mapConfirmationModal').modal('show');
        });
        window.addEventListener('hide-delete-map-modal', event => {
            $('#mapConfirmationModal').modal('hide');
            toastr.success(event.detail.message, 'Stage map removed successfully!');
        })
    </script>
@endpush
