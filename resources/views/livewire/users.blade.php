<div class="main py-4">
    <div class="d-block mb-md-0" style="position: relative">
        <h2>Manage Users</h2>
        <button class="buttonAdd btn btn-secondary d-inline-flex align-items-center me-2">+ Add User</button>
    </div>
    <div class="col-9 col-lg-8 d-md-flex">
        <select wire:model="selectedCompany" class="form-select fmxw-200 d-none d-md-inline">
            <option value="" disabled selected>Choose Company</option>
            @foreach($companies as $company)
                <option value="{{$company->company_id}}">{{$company->company_name}}</option>
            @endforeach
        </select>
    </div>
    <br>

    @if (!is_null($selectedCompany))
    <div wire:model="users" class="card card-body border-0 shadow table-wrapper table-responsive">
        <h2 class="mb-4 h5">{{ __('Users') }}</h2>

        <table class="table table-hover">
            <thead>
            <tr>
                <th class="border-gray-200">{{ __('Name') }}</th>
                <th class="border-gray-200">{{ __('Email') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($users as $user)
                <tr>
                    <td><span class="fw-normal">{{ $user->full_name }}</span></td>
                    <td><span class="fw-normal">{{ $user->email }}</span></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div
            class="card-footer px-3 border-0 d-flex flex-column flex-lg-row align-items-center justify-content-between">
            {{ $users->links() }}
        </div>
    </div>
    @endif
</div>
