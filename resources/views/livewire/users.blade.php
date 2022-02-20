<div class="main py-4">
    <div class="d-block mb-md-0" style="position: relative">
        <h2>Manage Users</h2>
        <button wire:click.prevent="addNew" class="buttonAdd btn btn-gray-800 d-inline-flex align-items-center me-2" data-bs-toggle="modal" data-bs-target="#modalEditUser">
            <i class="fa fa-plus-circle mr-1 fa-fw"></i>
            Add User
        </button>
    </div>
    <div class="card card-body border-0 shadow table-wrapper table-responsive">
        <livewire:user-table/>
    </div>

    {{--<div class="col-9 col-lg-8 d-md-flex">
        <select wire:model="selectedCompany" class="form-select fmxw-200 d-none d-md-inline">
            <option value="">Choose Company</option>
            @foreach($companies as $company)
                <option value="{{$company->id}}">{{$company->company_name}}</option>
            @endforeach
        </select>
    </div>
    <br>

    @if (!is_null($selectedCompany))
    <div class="card card-body border-0 shadow table-wrapper table-responsive">
        <h2 class="mb-4 h5">{{ __('All Users by Company') }}</h2>

        <table class="table table-hover">
            <thead>
            <tr>
                <th class="border-gray-200">{{ __('Name') }}</th>
                <th class="border-gray-200">{{ __('Email') }}</th>
                <th class="border-gray-200">{{ __('Phone Number') }}</th>
                <th class="border-gray-200">{{ __('User Role') }}</th>
                <th class="border-gray-200">Action</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($users as $user)
                <tr>
                    <td><span class="fw-normal">{{ $user->full_name }}</span></td>
                    <td><span class="fw-normal">{{ $user->email }}</span></td>
                    <td><span class="fw-normal">{{ $user->phone_number }}</span></td>
                    @if($user->user_role==1)
                        <td><span class="fw-normal">Administrator</span></td>
                    @elseif($user->user_role==2)
                        <td><span class="fw-normal">Report User</span></td>
                    @else
                        <td><span class="fw-normal">Super User</span></td>
                    @endif
                    @if (auth()->user()->user_role == 1)
                    <td>
                        <!-- Button Modal -->
                        <button wire:click.prevent="editUser({{ $user }})" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modalEditUser">Edit</button>
                        <button wire:click.prevent="confirmUserRemoval({{ $user->id }})" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirmationModal">Remove</button>
                    </td>
                    @endif
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="card-footer px-3 border-0 d-flex flex-column flex-lg-row align-items-center justify-content-between">
            --}}{{--{{ $users->links() }}--}}{{--
        </div>
    </div>
    @endif--}}

    <!-- Edit User Modal Content -->
    <div wire:ignore.self class="modal fade" id="modalEditUser" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-md-5">
                    <h2 class="h4 text-center">
                        @if($showEditModal)
                            <span>Edit User Details</span>
                        @else
                            <span>Add New User</span>
                        @endif
                    </h2>

                    <form wire:submit.prevent="{{ $showEditModal ? 'updateUser' : 'createUser' }}">
                    @csrf
                    <!-- Form -->
                        <div class="form-group mb-4">
                            <label for="full_name">Full Name</label>
                            <div class="input-group">
                                <span class="input-group-text"  id="basic-addon1">
                                    <i class="fas fa-user-alt fa-fw"></i>
                                </span>
                                <input wire:model.defer="state.full_name" id="full_name" class="form-control border-gray-300" placeholder="{{ __('Full Name') }}" autofocus required>
                            </div>
                            @error('full_name')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group mb-4">
                            <label for="email">Email</label>
                            <div class="input-group">
                                <span class="input-group-text" id="basic-addon3">
                                    <svg class="icon icon-xxs" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path></svg>
                                </span>
                                <input wire:model.defer="state.email" type="email" class="form-control border-gray-300" id="email" placeholder="{{ __('Email') }}" autofocus required>
                            </div>
                            @error('email')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group mb-4">
                            <label for="ic_number">Identification Card Number (with dash '-') </label>
                            <div class="input-group">
                               <span class="input-group-text" id="basic-addon1">
                                   <i class="fas fa-id-badge fa-fw"></i>
                               </span>
                                <input wire:model.defer="state.ic_number" id="ic_number" class="form-control border-gray-300" placeholder="{{ __('IC Number') }}" autofocus required>
                            </div>
                            @error('ic_number')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group mb-4">
                            <label for="phone_number">Phone Number</label>
                            <div class="input-group">
                                <span class="input-group-text" id="basic-addon1">
                                    <i class="fas fa-phone-alt fa-fw"></i>
                                </span>
                                <input wire:model.defer="state.phone_number" id="phone_number" class="form-control border-gray-300" placeholder="{{ __('Phone Number') }}"  autofocus required>
                            </div>
                            @error('phone_number')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group mb-4">
                            <label for="company_id">Company</label>
                            <div class="input-group">
                                <span class="input-group-text" id="basic-addon1">
                                    <i class="fas fa-building fa-fw"></i>
                                </span>
                                <select wire:model.defer="state.company_id" id="company_id" class="form-select border-gray-300" autofocus required>
                                    <option value="">Select your company</option>
                                    @foreach($companies as $company)
                                        <option value="{{$company->id}} ">{{$company->company_name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            @error('company_id')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group mb-4">
                            <label for="user_role">User Role</label>
                            <div class="input-group">
                                <span class="input-group-text" id="basic-addon1">
                                    <i class="fas fa-user-lock fa-fw"></i>
                                </span>
                                <select wire:model.defer="state.user_role" id="user_role" class="form-select border-gray-300" autofocus required>
                                    <option value="">Select user role</option>
                                    <option value="1">Administrator</option>
                                    <option value="2">Report User</option>
                                </select>
                            </div>
                            @error('user_role')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group mb-4">
                            <label for="username">Username</label>
                            <div class="input-group">
                                <span class="input-group-text" id="basic-addon2">
                                    <i class="fas fa-user-alt fa-fw"></i>
                                </span>
                                <input wire:model.defer="state.username" placeholder="{{ __('Username') }}" class="form-control border-gray-300" id="username" required>
                            </div>
                            @error('username')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        @if($showEditModal==false)
                        <div class="form-group mb-4">
                            <label for="password">Password</label>
                            <div class="input-group">
                                <span class="input-group-text" id="basic-addon2">
                                    <svg class="icon icon-xs text-gray-600" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </span>
                                <input wire:model.defer="state.password" type="password" placeholder="{{ __('Password') }}" class="form-control border-gray-300" id="password" required>
                            </div>
                            @if ($errors->has('password'))
                                <span class="text-danger">{{ $errors->first('password') }}</span>
                            @endif
                        </div>
                        <div class="form-group mb-4">
                            <label for="password_confirmation">Confirm Password</label>
                            <div class="input-group">
                                <span class="input-group-text" id="basic-addon3">
                                    <svg class="icon icon-xs text-gray-600" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </span>
                                <input wire:model.defer="state.password_confirmation" type="password" placeholder="{{ __('Confirm Password') }}" class="form-control border-gray-300" id="password_confirmation" required>
                            </div>
                            @if ($errors->has('password_confirmation'))
                                <span class="text-danger">{{ $errors->first('password_confirmation') }}</span>
                            @endif
                        </div>
                        @endif

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
            </div>
        </div>
    </div>
    <!-- End of Edit User Modal Content -->

    <!-- Remove User Modal -->
    {{--<div wire:ignore.self class="modal fade" id="confirmationModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5>Remove User</h5>
                </div>

                <div class="modal-body">
                    <h4>Are you sure you want to remove this user?</h4>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa fa-times mr-1"></i> Cancel</button>
                    <button type="button" wire:click.prevent="removeUser" class="btn btn-danger"><i class="fa fa-trash mr-1"></i>Remove User</button>
                </div>
            </div>
        </div>
    </div>--}}
    <!-- End of Remove User Modal Content -->
</div>
@section('script')
    <script>
        window.addEventListener('editEvent', event => {
            $('#modalEditUser').modal('show');
        });
        window.addEventListener('hide-form', event => {
            $('#modalEditUser').modal('hide');
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
