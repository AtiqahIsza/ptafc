<div class="main py-4">
    <div class="d-block mb-md-0" style="position: relative">
        <h2>Manage Routes</h2>
        <button wire:click.prevent="addNew" class="buttonAdd btn btn-gray-800 d-inline-flex align-items-center me-2">
            <i class="fa fa-plus-circle mr-1 fa-fw"></i>
            Add Route
        </button>
        <button class="buttonUpload btn btn-gray-800 d-inline-flex align-items-center me-2" data-bs-toggle="modal" data-bs-target="#modalUpload">
            <i class="fa fa-file-upload mr-1 fa-fw"></i>
            Upload KML File
        </button>
        <button wire:click.prevent="extractExcel" class="buttonDownload btn btn-gray-800 d-inline-flex align-items-center me-2">
            <i class="fa fa-file-download mr-1 fa-fw"></i>
            Extract to Excel
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
            <h2 class="mb-4 h5">{{ __('All Routes by Company') }}</h2>
            <table class="table table-hover">
                <thead>
                <tr>
                    <th class="border-gray-200">{{ __('Route Number') }}</th>
                    <th class="border-gray-200">{{ __('Route Name') }}</th>
                    <th class="border-gray-200">{{ __('Inbound Distance (KM)') }}</th>
                    <th class="border-gray-200">{{ __('Outbound Distance (KM)') }}</th>
                    <th class="border-gray-200">{{ __('Route Map') }}</th>
                    <th class="border-gray-200">Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($routes as $route)
                    <tr>
                        <td><span class="fw-normal">{{ $route->route_number }}</span></td>
                        <td><span class="fw-normal">{{ $route->route_name }}</span></td>
                        <td><span class="fw-normal">{{ $route->inbound_distance }}</span></td>
                        <td><span class="fw-normal">{{ $route->outbound_distance }}</span></td>
                        @php
                            $result = false;
                        @endphp
                        @foreach($routeMaps as $routeMap)
                            @if($routeMap->route_id == $route->id)
                                @php
                                    $result = true;
                                @endphp
                            @endif
                        @endforeach

                        @if($result)
                            <td>
                                <!-- Button for preview stage map-->
                                <button onclick="window.location='{{ route('viewRouteMap', $route->id) }}'" class="btn btn-success">View</button>
                                <button wire:click.prevent="confirmRemovalMap({{ $route->id }})"  class="btn btn-danger">Remove</button>
                            </td>
                        @else
                            <td>
                                <!-- Button for creating map-->
                                <button onclick="window.location='{{ route('addRouteMap', $route->id) }}'" class="btn btn-primary">Create</button>
                            </td>
                        @endif
                        <td>
                            <!-- Button Modal -->
                            <button wire:click.prevent="edit({{ $route }})" class="btn btn-warning">Edit</button>
                            <button wire:click.prevent="confirmRemoval({{ $route->id }})" class="btn btn-danger">Remove</button>
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
                            <span>Edit Route Details</span>
                        @else
                            <span>Add New Route</span>
                        @endif
                    </h2>

                    <!-- Form -->
                    <form wire:submit.prevent="{{ $showEditModal ? 'updateRoute' : 'createRoute' }}">
                        @csrf
                        <div class="form-group mb-4">
                            <label for="routeName">Route Name</label>
                            <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pin-map-fill" viewBox="0 0 16 16">
                                        <path fill-rule="evenodd" d="M3.1 11.2a.5.5 0 0 1 .4-.2H6a.5.5 0 0 1 0 1H3.75L1.5 15h13l-2.25-3H10a.5.5 0 0 1 0-1h2.5a.5.5 0 0 1 .4.2l3 4a.5.5 0 0 1-.4.8H.5a.5.5 0 0 1-.4-.8l3-4z"/>
                                        <path fill-rule="evenodd" d="M4 4a4 4 0 1 1 4.5 3.969V13.5a.5.5 0 0 1-1 0V7.97A4 4 0 0 1 4 3.999z"/>
                                    </svg>
                                </span>
                                <input wire:model.defer="state.route_name" class="form-control border-gray-300" id="routeName" placeholder="{{ __('Route Name') }}" autofocus required>
                                @if ($errors->has('routeName'))
                                    <span class="text-danger">{{ $errors->first('routeName') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="routeNum">Route Number</label>
                            <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-signpost-fill" viewBox="0 0 16 16">
                                        <path d="M7.293.707A1 1 0 0 0 7 1.414V4H2a1 1 0 0 0-1 1v4a1 1 0 0 0 1 1h5v6h2v-6h3.532a1 1 0 0 0 .768-.36l1.933-2.32a.5.5 0 0 0 0-.64L13.3 4.36a1 1 0 0 0-.768-.36H9V1.414A1 1 0 0 0 7.293.707z"/>
                                    </svg>
                                </span>
                                <input wire:model.defer="state.route_number" class="form-control border-gray-300" id="routeNum" placeholder="{{ __('Route Number') }}" autofocus>
                                @if ($errors->has('routeNum'))
                                    <span class="text-danger">{{ $errors->first('routeNum') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="routeTarget">Route Target (RM)</label>
                            <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <i class="fas fa-directions fa-fw"></i>
                                </span>
                                <input wire:model.defer="state.route_target" class="form-control border-gray-300" id="routeTarget" placeholder="{{ __('Route Target') }}" autofocus required>
                                @if ($errors->has('routeTarget'))
                                    <span class="text-danger">{{ $errors->first('routeTarget') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="distance">Distance (KM)</label>
                            <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bezier2" viewBox="0 0 16 16">
                                        <path fill-rule="evenodd" d="M1 2.5A1.5 1.5 0 0 1 2.5 1h1A1.5 1.5 0 0 1 5 2.5h4.134a1 1 0 1 1 0 1h-2.01c.18.18.34.381.484.605.638.992.892 2.354.892 3.895 0 1.993.257 3.092.713 3.7.356.476.895.721 1.787.784A1.5 1.5 0 0 1 12.5 11h1a1.5 1.5 0 0 1 1.5 1.5v1a1.5 1.5 0 0 1-1.5 1.5h-1a1.5 1.5 0 0 1-1.5-1.5H6.866a1 1 0 1 1 0-1h1.711a2.839 2.839 0 0 1-.165-.2C7.743 11.407 7.5 10.007 7.5 8c0-1.46-.246-2.597-.733-3.355-.39-.605-.952-1-1.767-1.112A1.5 1.5 0 0 1 3.5 5h-1A1.5 1.5 0 0 1 1 3.5v-1zM2.5 2a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1zm10 10a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1z"/>
                                    </svg>
                                </span>
                                <input wire:model.defer="state.distance" class="form-control border-gray-300" id="distance"  placeholder="{{ __('Distance in KM') }}" autofocus>
                                @if ($errors->has('distance'))
                                    <span class="text-danger">{{ $errors->first('distance') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="indistance">Inbound Distance (KM)</label>
                            <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bezier2" viewBox="0 0 16 16">
                                        <path fill-rule="evenodd" d="M1 2.5A1.5 1.5 0 0 1 2.5 1h1A1.5 1.5 0 0 1 5 2.5h4.134a1 1 0 1 1 0 1h-2.01c.18.18.34.381.484.605.638.992.892 2.354.892 3.895 0 1.993.257 3.092.713 3.7.356.476.895.721 1.787.784A1.5 1.5 0 0 1 12.5 11h1a1.5 1.5 0 0 1 1.5 1.5v1a1.5 1.5 0 0 1-1.5 1.5h-1a1.5 1.5 0 0 1-1.5-1.5H6.866a1 1 0 1 1 0-1h1.711a2.839 2.839 0 0 1-.165-.2C7.743 11.407 7.5 10.007 7.5 8c0-1.46-.246-2.597-.733-3.355-.39-.605-.952-1-1.767-1.112A1.5 1.5 0 0 1 3.5 5h-1A1.5 1.5 0 0 1 1 3.5v-1zM2.5 2a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1zm10 10a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1z"/>
                                    </svg>
                                </span>
                                <input wire:model.defer="state.inbound_distance" class="form-control border-gray-300" id="indistance" placeholder="{{ __('Inbound Distance in KM') }}" autofocus>
                                @if ($errors->has('indistance'))
                                    <span class="text-danger">{{ $errors->first('indistance') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="outdistance">Outbound Distance (KM)</label>
                            <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bezier2" viewBox="0 0 16 16">
                                        <path fill-rule="evenodd" d="M1 2.5A1.5 1.5 0 0 1 2.5 1h1A1.5 1.5 0 0 1 5 2.5h4.134a1 1 0 1 1 0 1h-2.01c.18.18.34.381.484.605.638.992.892 2.354.892 3.895 0 1.993.257 3.092.713 3.7.356.476.895.721 1.787.784A1.5 1.5 0 0 1 12.5 11h1a1.5 1.5 0 0 1 1.5 1.5v1a1.5 1.5 0 0 1-1.5 1.5h-1a1.5 1.5 0 0 1-1.5-1.5H6.866a1 1 0 1 1 0-1h1.711a2.839 2.839 0 0 1-.165-.2C7.743 11.407 7.5 10.007 7.5 8c0-1.46-.246-2.597-.733-3.355-.39-.605-.952-1-1.767-1.112A1.5 1.5 0 0 1 3.5 5h-1A1.5 1.5 0 0 1 1 3.5v-1zM2.5 2a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1zm10 10a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1z"/>
                                    </svg>
                                </span>
                                <input wire:model.defer="state.outbound_distance" class="form-control border-gray-300" id="outdistance" placeholder="{{ __('Outbound Distance in KM') }}"  autofocus>
                                @if ($errors->has('outdistance'))
                                    <span class="text-danger">{{ $errors->first('outdistance') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="company">Company</label>
                            <div class="input-group">
                                <span class="input-group-text" id="basic-addon1">
                                    <i class="fas fa-building fa-fw"></i>
                                </span>
                                <select wire:model.defer="state.company_id" class="form-select border-gray-300" autofocus required>
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
                        <div class="form-group mb-4">
                            <label for="status">Status</label>
                            <div class="input-group">
                                <span class="input-group-text" id="basic-addon1">
                                    <i class="fas fa-user-check fa-fw"></i>
                                </span>
                                <select wire:model.defer="state.status" id="status" class="form-select border-gray-300" autofocus required>
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
    <!-- End of Edit User Modal Content -->

    <!-- Upload KML File Modal Content -->
    <div wire:ignore.self class="modal fade" id="modalUpload" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-md-5">
                    <h2 class="h4 text-center">
                        <span>Upload KML File</span>
                    </h2>
                    <br>

                    <!-- Form -->
                    <form action="{{ route('uploadFile')}}" method="post" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group mb-4">
                            <div class="input-group">
                                <input class="form-control border-gray-300" type="file" id="file" name="file" autofocus required>
                                @if ($errors->has('file'))
                                    <span class="text-danger">{{ $errors->first('file') }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary" id="btnSave">
                                <span>Upload</span>
                            </button>
                        </div>
                    </form>
                </div>
                <div class="modal-header"></div>
            </div>
        </div>
    </div>
    <!-- End of Upload KML File Modal Content -->

    <!-- Remove Route Modal -->
    <div wire:ignore.self class="modal fade" id="confirmationModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5>Remove Route</h5>
                </div>

                <div class="modal-body">
                    <h4>Are you sure you want to remove {{ $removedRoute }}?</h4>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa fa-times mr-1"></i> Cancel</button>
                    <button type="button" wire:click.prevent="removeRoute" class="btn btn-danger" data-bs-dismiss="modal"><i class="fa fa-trash mr-1"></i>Remove Route</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End of Remove Route Modal Content -->

    <!-- Remove Route Map Modal -->
    <div wire:ignore.self class="modal fade" id="mapConfirmationModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5>Remove Route Map</h5>
                </div>

                <div class="modal-body">
                    <h4>Are you sure you want to remove map of {{ $removedRoute }}?</h4>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa fa-times mr-1"></i> Cancel</button>
                    <button type="button" wire:click.prevent="removeRouteMap" class="btn btn-danger" data-bs-dismiss="modal"><i class="fa fa-trash mr-1"></i>Remove Route Map</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End of Remove Route Map Modal Content -->
</div>
@push('script')
    <script>
        window.addEventListener('show-form', event => {
            $('#modalEdit').modal('show');
        });
        window.addEventListener('hide-form-edit', event => {
            $('#modalEdit').modal('hide');
            toastr.success(event.detail.message, 'Route updated successfully!');
        });
        window.addEventListener('hide-form-add', event => {
            $('#modalEdit').modal('hide');
            toastr.success(event.detail.message, 'New route added successfully!');
        });
        window.addEventListener('hide-form-failed', event => {
            $('#modalEdit').modal('hide');
            toastr.error(event.detail.message, 'Operation Failed!');
        });
        window.addEventListener('failed-add-route-no', event => {
            $('#modalEdit').modal('hide');
            toastr.error(event.detail.message, 'Failed! Route number already exist!');
        });
        window.addEventListener('failed-add-route-name', event => {
            $('#modalEdit').modal('hide');
            toastr.error(event.detail.message, 'Failed! Route name already exist!');
        });
        
        window.addEventListener('show-delete-modal', event => {
            $('#confirmationModal').modal('show');
        });
        window.addEventListener('hide-delete-modal', event => {
            $('#confirmationModal').modal('hide');
            toastr.success(event.detail.message, 'Route removed successfully!');
        })

        window.addEventListener('show-delete-map-modal', event => {
            $('#mapConfirmationModal').modal('show');
        });
        window.addEventListener('hide-delete-map-modal', event => {
            $('#mapConfirmationModal').modal('hide');
            toastr.success(event.detail.message, 'Route map removed successfully!');
        })
    </script>
@endpush
