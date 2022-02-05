<div class="main py-4">
    <div class="d-block mb-md-0" style="position: relative">
        <h2>Manage Cards</h2>
    </div>
    <div class="d-block mb-md-0" style="position: relative">
        <input wire:model="selectedID" class="form-control border-gray-300 fmxw-400 d-none d-md-inline" placeholder="Insert ID Number/Manufacturing Number">

        @if (!is_null($selectedID))
            <select wire:model="selectedCardStatus" class="form-select fmxw-200 d-none d-md-inline"  >
                <option value="">Choose Card Status</option>
                <option value="1">Active</option>
                <option value="2">Inactive</option>
                <option value="3">Blacklisted</option>
            </select>
        @endif
    </div>
    <br>

    <div class="card card-body border-0 shadow table-wrapper table-responsive">
        <h2 class="mb-4 h5">{{ __('All Cards by ID Number/Manufacturing Number and Status') }}</h2>
        <table class="table table-hover">
            <thead>
            <tr>
                <th class="border-gray-200">{{ __('Manufacturing No') }}</th>
                <th class="border-gray-200">{{ __('Cardholder Name') }}</th>
                <th class="border-gray-200">{{ __('ID No') }}</th>
                <th class="border-gray-200">{{ __('Card No') }}</th>
                <th class="border-gray-200">{{ __('Card Type') }}</th>
                <th class="border-gray-200">{{ __('Card Balance') }}</th>
                <th class="border-gray-200">{{ __('Action') }}</th>
            </tr>
            </thead>
            @if (!is_null($selectedCardStatus))
                <tbody>
                @if (!is_null($cards))
                    @foreach ($cards as $card)
                        <tr>
                            <td><span class="fw-normal">{{ $card->manufacturing_id }}</span></td>
                            <td><span class="fw-normal">{{ $card->card_holder_name }}</span></td>
                            <td><span class="fw-normal">{{ $card->id_number }}</span></td>
                            <td><span class="fw-normal">{{ $card->card_number }}</span></td>
                            @if($card->card_type == '1')
                                <td><span class="fw-normal">Passenger</span></td>
                            @elseif($card->card_type == '2')
                                <td><span class="fw-normal">Agent</span></td>
                            @elseif($card->card_type == '3')
                                <td><span class="fw-normal">Sub-Agent</span></td>
                            @elseif($card->card_type == '4')
                                <td><span class="fw-normal">Mobile-Agent</span></td>
                            @elseif($card->card_type == '5')
                                <td><span class="fw-normal">UPM Student</span></td>
                            @elseif($card->card_type == '6')
                                <td><span class="fw-normal">KTB Staff</span></td>
                            @elseif($card->card_type == '7')
                                <td><span class="fw-normal">Inspector</span></td>
                            @else
                                <td><span class="fw-normal">Driver</span></td>
                            @endif
                            <td><span class="fw-normal">{{ $card->current_balance }}</span></td>
                            <td>
                                <!-- Button Modal -->
                                <!-- NEED TO REVISE BACK THE LOGIC CONDITION-->
                                <button wire:click.prevent="changeRegion({{ $card }})" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#changeRegionModal">Change Region</button>
                                @if($card->card_type == 1)
                                    <button wire:click.prevent="edit({{ $card }})" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editModal">Edit Card</button>
                                @endif
                                @if($card->card_status  == 3)
                                    <button wire:click.prevent="generateVoucher({{ $card }})" class="btn btn-tertiary" data-bs-toggle="modal" data-bs-target="#generateVoucherModal">Generate Voucher</button>
                                @else
                                    <button wire:click.prevent="blacklist({{ $card }})" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#blacklistModal">Blacklist</button>
                                @endif
                                <button wire:click.prevent="resetCard({{ $card->id }})" class="btn btn-gray-700" data-bs-toggle="modal" data-bs-target="#resetModal">Reset</button>
                                <!-- NEED TO REVISE BACK THE LOGIC CONDITION-->
                            </td>
                        </tr>
                    @endforeach
                @endif
                </tbody>
            @endif
        </table>
        <div class="card-footer px-3 border-0 d-flex flex-column flex-lg-row align-items-center justify-content-between">
            {{--{{ $users->links() }}--}}
        </div>
    </div>

    <!-- Edit Passenger Card Content -->
    <div wire:ignore.self class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-md-5">
                    <h2 class="h4 text-center">
                        <span>Edit Details of Passenger Card</span>
                    </h2>

                @if (!is_null($ticketCards))
                    <!-- Form -->
                        <form wire:submit.prevent="{{ 'updateCard' }}">
                            @csrf
                            <div class="form-group mb-4">
                                <label for="manufacturing_id">Manufacturing No.</label>
                                <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-123" viewBox="0 0 16 16">
                                        <path d="M2.873 11.297V4.142H1.699L0 5.379v1.137l1.64-1.18h.06v5.961h1.174Zm3.213-5.09v-.063c0-.618.44-1.169 1.196-1.169.676 0 1.174.44 1.174 1.106 0 .624-.42 1.101-.807 1.526L4.99 10.553v.744h4.78v-.99H6.643v-.069L8.41 8.252c.65-.724 1.237-1.332 1.237-2.27C9.646 4.849 8.723 4 7.308 4c-1.573 0-2.36 1.064-2.36 2.15v.057h1.138Zm6.559 1.883h.786c.823 0 1.374.481 1.379 1.179.01.707-.55 1.216-1.421 1.21-.77-.005-1.326-.419-1.379-.953h-1.095c.042 1.053.938 1.918 2.464 1.918 1.478 0 2.642-.839 2.62-2.144-.02-1.143-.922-1.651-1.551-1.714v-.063c.535-.09 1.347-.66 1.326-1.678-.026-1.053-.933-1.855-2.359-1.845-1.5.005-2.317.88-2.348 1.898h1.116c.032-.498.498-.944 1.206-.944.703 0 1.206.435 1.206 1.07.005.64-.504 1.106-1.2 1.106h-.75v.96Z"/>
                                    </svg>
                                </span>
                                    <span class="form-control border-gray-300" id="manufacturing_id">{{$ticketCards->manufacturing_id}}</span>
                                </div>
                            </div>
                            <div class="form-group mb-4">
                                <label for="card_number">Card Number</label>
                                <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-credit-card-2-front-fill" viewBox="0 0 16 16">
                                        <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4zm2.5 1a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h2a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-2zm0 3a.5.5 0 0 0 0 1h5a.5.5 0 0 0 0-1h-5zm0 2a.5.5 0 0 0 0 1h1a.5.5 0 0 0 0-1h-1zm3 0a.5.5 0 0 0 0 1h1a.5.5 0 0 0 0-1h-1zm3 0a.5.5 0 0 0 0 1h1a.5.5 0 0 0 0-1h-1zm3 0a.5.5 0 0 0 0 1h1a.5.5 0 0 0 0-1h-1z"/>
                                    </svg>
                                </span>
                                    <span class="form-control border-gray-300" id="card_number">{{$ticketCards->card_number}}</span>
                                </div>
                            </div>
                            <div class="form-group mb-4">
                                <label for="card_type">Card Type</label>
                                <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-people-fill" viewBox="0 0 16 16">
                                        <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                                        <path fill-rule="evenodd" d="M5.216 14A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216z"/>
                                        <path d="M4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"/>
                                    </svg>
                                </span>
                                    @if($ticketCards->card_type == '1')
                                        <span class="form-control border-gray-300" id="card_type">Passenger</span>
                                    @elseif($ticketCards->card_type == '2')
                                        <span class="form-control border-gray-300" id="card_type">Agent</span>
                                    @elseif($ticketCards->card_type == '3')
                                        <span class="form-control border-gray-300" id="card_type">Sub-Agent</span>
                                    @elseif($ticketCards->card_type == '4')
                                        <span class="form-control border-gray-300" id="card_type">Mobile-Agent</span>
                                    @elseif($ticketCards->card_type == '5')
                                        <span class="form-control border-gray-300" id="card_type">UPM Student</span>
                                    @elseif($ticketCards->card_type == '6')
                                        <span class="form-control border-gray-300" id="card_type">KTB Staff</span>
                                    @elseif($ticketCards->card_type == '7')
                                        <span class="form-control border-gray-300" id="card_type">Inspector</span>
                                    @else
                                        <span class="form-control border-gray-300" id="card_type">Driver</span>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group mb-4">
                                <label for="card_holder_name">Cardholder Name</label>
                                <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-lines-fill" viewBox="0 0 16 16">
                                        <path d="M6 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm-5 6s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H1zM11 3.5a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 0 1h-4a.5.5 0 0 1-.5-.5zm.5 2.5a.5.5 0 0 0 0 1h4a.5.5 0 0 0 0-1h-4zm2 3a.5.5 0 0 0 0 1h2a.5.5 0 0 0 0-1h-2zm0 3a.5.5 0 0 0 0 1h2a.5.5 0 0 0 0-1h-2z"/>
                                    </svg>
                                </span>
                                    <span class="form-control border-gray-300" id="card_holder_name">{{$ticketCards->card_holder_name}}</span>
                                </div>
                            </div>
                            <div class="form-group mb-4">
                                <label for="id_number">IC Number</label>
                                <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-123" viewBox="0 0 16 16">
                                        <path d="M2.873 11.297V4.142H1.699L0 5.379v1.137l1.64-1.18h.06v5.961h1.174Zm3.213-5.09v-.063c0-.618.44-1.169 1.196-1.169.676 0 1.174.44 1.174 1.106 0 .624-.42 1.101-.807 1.526L4.99 10.553v.744h4.78v-.99H6.643v-.069L8.41 8.252c.65-.724 1.237-1.332 1.237-2.27C9.646 4.849 8.723 4 7.308 4c-1.573 0-2.36 1.064-2.36 2.15v.057h1.138Zm6.559 1.883h.786c.823 0 1.374.481 1.379 1.179.01.707-.55 1.216-1.421 1.21-.77-.005-1.326-.419-1.379-.953h-1.095c.042 1.053.938 1.918 2.464 1.918 1.478 0 2.642-.839 2.62-2.144-.02-1.143-.922-1.651-1.551-1.714v-.063c.535-.09 1.347-.66 1.326-1.678-.026-1.053-.933-1.855-2.359-1.845-1.5.005-2.317.88-2.348 1.898h1.116c.032-.498.498-.944 1.206-.944.703 0 1.206.435 1.206 1.07.005.64-.504 1.106-1.2 1.106h-.75v.96Z"/>
                                    </svg>
                                </span>
                                    <span class="form-control border-gray-300" id="id_number">{{$ticketCards->id_number}}</span>
                                </div>
                            </div>
                            <div class="form-group mb-4">
                                <label for="title">Title</label>
                                <div class="input-group">
                                    <span class="input-group-text border-gray-300" id="basic-addon3">
                                        <i class="fas fa-user-alt fa-fw"></i>
                                    </span>
                                    <select wire:model="state.title" class="form-select border-gray-300" id="title" autofocus required>
                                        <option value="">Choose The Title</option>
                                        <option value="Encik">Encik</option>
                                        <option value="Puan<">Puan</option>
                                        <option value="Cik">Cik</option>
                                    </select>
                                    @if ($errors->has('title'))
                                        <span class="text-danger">{{ $errors->first('title') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group mb-4">
                                <label for="date_of_birth">Date of Birth</label>
                                <div class="input-group">
                                    <span class="input-group-text border-gray-300" id="basic-addon3">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-calendar" viewBox="0 0 16 16">
                                            <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/>
                                        </svg>
                                    </span>
                                    <input wire:model="state.date_of_birth" class="form-control border-gray-300" type="date" id="date_of_birth" autofocus required>
                                    @if ($errors->has('date_of_birth'))
                                        <span class="text-danger">{{ $errors->first('date_of_birth') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group mb-4">
                                <label for="address1">Address 1</label>
                                <div class="input-group">
                                    <span class="input-group-text border-gray-300" id="basic-addon3">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-house-door-fill" viewBox="0 0 16 16">
                                            <path d="M6.5 14.5v-3.505c0-.245.25-.495.5-.495h2c.25 0 .5.25.5.5v3.5a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5v-7a.5.5 0 0 0-.146-.354L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.354 1.146a.5.5 0 0 0-.708 0l-6 6A.5.5 0 0 0 1.5 7.5v7a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5z"/>
                                        </svg>
                                    </span>
                                    <input wire:model="state.address1" class="form-control border-gray-300" id="address1" autofocus required>
                                </div>
                                @if ($errors->has('address1'))
                                    <span class="text-danger">{{ $errors->first('address1') }}</span>
                                @endif
                            </div>
                            <div class="form-group mb-4">
                                <label for="address2">Address 2</label>
                                <div class="input-group">
                                    <span class="input-group-text border-gray-300" id="basic-addon3">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-house-door-fill" viewBox="0 0 16 16">
                                            <path d="M6.5 14.5v-3.505c0-.245.25-.495.5-.495h2c.25 0 .5.25.5.5v3.5a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5v-7a.5.5 0 0 0-.146-.354L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.354 1.146a.5.5 0 0 0-.708 0l-6 6A.5.5 0 0 0 1.5 7.5v7a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5z"/>
                                        </svg>
                                    </span>
                                    <input wire:model="address2" class="form-control border-gray-300" id="address2" autofocus required>
                                    @if ($errors->has('address2'))
                                        <span class="text-danger">{{ $errors->first('address2') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group mb-4">
                                <label for="home_phone">Home Phone</label>
                                <div class="input-group">
                                    <span class="input-group-text border-gray-300" id="basic-addon3">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-telephone-fill" viewBox="0 0 16 16">
                                            <path fill-rule="evenodd" d="M1.885.511a1.745 1.745 0 0 1 2.61.163L6.29 2.98c.329.423.445.974.315 1.494l-.547 2.19a.678.678 0 0 0 .178.643l2.457 2.457a.678.678 0 0 0 .644.178l2.189-.547a1.745 1.745 0 0 1 1.494.315l2.306 1.794c.829.645.905 1.87.163 2.611l-1.034 1.034c-.74.74-1.846 1.065-2.877.702a18.634 18.634 0 0 1-7.01-4.42 18.634 18.634 0 0 1-4.42-7.009c-.362-1.03-.037-2.137.703-2.877L1.885.511z"/>
                                        </svg>
                                    </span>
                                    <input wire:model="state.home_phone" class="form-control border-gray-300" id="home_phone" autofocus required>
                                    @if ($errors->has('home_phone'))
                                        <span class="text-danger">{{ $errors->first('home_phone') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group mb-4">
                                <label for="cell_phone">Cell Phone</label>
                                <div class="input-group">
                                    <span class="input-group-text border-gray-300" id="basic-addon3">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-phone-fill" viewBox="0 0 16 16">
                                            <path d="M3 2a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V2zm6 11a1 1 0 1 0-2 0 1 1 0 0 0 2 0z"/>
                                        </svg>
                                    </span>
                                    <input wire:model="state.cell_phone" class="form-control border-gray-300" id="cell_phone" autofocus required>
                                    @if ($errors->has('cell_phone'))
                                        <span class="text-danger">{{ $errors->first('cell_phone') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group mb-4">
                                <label for="email">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text border-gray-300" id="basic-addon3">
                                        <svg class="icon icon-xxs" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
                                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
                                        </svg>
                                    </span>
                                    <input wire:model="state.email" class="form-control border-gray-300" type="email" id="email" autofocus required>
                                    @if ($errors->has('email'))
                                        <span class="text-danger">{{ $errors->first('email') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group mb-4">
                                <label for="gender">Gender</label>
                                <div class="input-group">
                                    <span class="input-group-text border-gray-300" id="basic-addon3">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-gender-ambiguous" viewBox="0 0 16 16">
                                            <path fill-rule="evenodd" d="M11.5 1a.5.5 0 0 1 0-1h4a.5.5 0 0 1 .5.5v4a.5.5 0 0 1-1 0V1.707l-3.45 3.45A4 4 0 0 1 8.5 10.97V13H10a.5.5 0 0 1 0 1H8.5v1.5a.5.5 0 0 1-1 0V14H6a.5.5 0 0 1 0-1h1.5v-2.03a4 4 0 1 1 3.471-6.648L14.293 1H11.5zm-.997 4.346a3 3 0 1 0-5.006 3.309 3 3 0 0 0 5.006-3.31z"/>
                                        </svg>
                                    </span>
                                    <select wire:model="state.gender" class="form-select border-gray-300" id="gender" autofocus required >
                                        <option value="">Choose Gender</option>
                                        <option value="1<">Female</option>
                                        <option value="2">Male</option>
                                    </select>
                                    @if ($errors->has('gender'))
                                        <span class="text-danger">{{ $errors->first('gender') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group mb-4">
                                <label for="race">Race</label>
                                <div class="input-group">
                                    <span class="input-group-text border-gray-300" id="basic-addon3">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-people-fill" viewBox="0 0 16 16">
                                          <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                                          <path fill-rule="evenodd" d="M5.216 14A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216z"/>
                                          <path d="M4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"/>
                                        </svg>
                                    </span>
                                    <select wire:model="state.race" class="form-select border-gray-300" id="race" autofocus required >
                                        <option value="">Choose Race</option>
                                        <option value="1<">Malay</option>
                                        <option value="2">Chinese</option>
                                        <option value="3">Indian</option>
                                        <option value="4">Others</option>
                                    </select>
                                    @if ($errors->has('race'))
                                        <span class="text-danger">{{ $errors->first('race') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group mb-4">
                                <label for="nationality">Nationality</label>
                                <div class="input-group">
                                    <span class="input-group-text border-gray-300" id="basic-addon3">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-flag-fill" viewBox="0 0 16 16">
                                            <path d="M14.778.085A.5.5 0 0 1 15 .5V8a.5.5 0 0 1-.314.464L14.5 8l.186.464-.003.001-.006.003-.023.009a12.435 12.435 0 0 1-.397.15c-.264.095-.631.223-1.047.35-.816.252-1.879.523-2.71.523-.847 0-1.548-.28-2.158-.525l-.028-.01C7.68 8.71 7.14 8.5 6.5 8.5c-.7 0-1.638.23-2.437.477A19.626 19.626 0 0 0 3 9.342V15.5a.5.5 0 0 1-1 0V.5a.5.5 0 0 1 1 0v.282c.226-.079.496-.17.79-.26C4.606.272 5.67 0 6.5 0c.84 0 1.524.277 2.121.519l.043.018C9.286.788 9.828 1 10.5 1c.7 0 1.638-.23 2.437-.477a19.587 19.587 0 0 0 1.349-.476l.019-.007.004-.002h.001"/>
                                        </svg>
                                    </span>
                                    <input wire:model="state.nationality" class="form-control border-gray-300" id="nationality" placeholder="{{ __('Nationality') }}" autofocus required>
                                    @if ($errors->has('nationality'))
                                        <span class="text-danger">{{ $errors->first('nationality') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group mb-4">
                                <label for="marital_status">Marital Status</label>
                                <div class="input-group">
                                    <span class="input-group-text border-gray-300" id="basic-addon3">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-people-fill" viewBox="0 0 16 16">
                                          <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                                          <path fill-rule="evenodd" d="M5.216 14A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216z"/>
                                          <path d="M4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"/>
                                        </svg>
                                    </span>
                                    <select wire:model="state.marital_status" class="form-select border-gray-300" id="marital_status" autofocus required >
                                        <option value="">Choose Marital Status</option>
                                        <option value="1">Single</option>
                                        <option value="2">Married</option>
                                        <option value="3">Others</option>
                                    </select>
                                    @if ($errors->has('marital_status'))
                                        <span class="text-danger">{{ $errors->first('marital_status') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group mb-4">
                                <label for="work">Job</label>
                                <div class="input-group">
                                    <span class="input-group-text border-gray-300" id="basic-addon3">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-briefcase-fill" viewBox="0 0 16 16">
                                          <path d="M6.5 1A1.5 1.5 0 0 0 5 2.5V3H1.5A1.5 1.5 0 0 0 0 4.5v1.384l7.614 2.03a1.5 1.5 0 0 0 .772 0L16 5.884V4.5A1.5 1.5 0 0 0 14.5 3H11v-.5A1.5 1.5 0 0 0 9.5 1h-3zm0 1h3a.5.5 0 0 1 .5.5V3H6v-.5a.5.5 0 0 1 .5-.5z"/>
                                          <path d="M0 12.5A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5V6.85L8.129 8.947a.5.5 0 0 1-.258 0L0 6.85v5.65z"/>
                                        </svg>
                                    </span>
                                    <select wire:model="state.work" class="form-select border-gray-300" id="work" autofocus required >
                                        <option value="">Choose Job</option>
                                        <option value="1<">Businessmen</option>
                                        <option value="2">Executive</option>
                                        <option value="3">Manager</option>
                                        <option value="4">Government's Servant</option>
                                        <option value="5">Others</option>
                                    </select>
                                    @if ($errors->has('work'))
                                        <span class="text-danger">{{ $errors->first('work') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group mb-4">
                                <label for="single_mother">Single Mother</label>
                                {{--<div class="input-group">
                                    <span class="input-group-text border-gray-300" id="basic-addon3">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-123" viewBox="0 0 16 16">
                                            <path d="M2.873 11.297V4.142H1.699L0 5.379v1.137l1.64-1.18h.06v5.961h1.174Zm3.213-5.09v-.063c0-.618.44-1.169 1.196-1.169.676 0 1.174.44 1.174 1.106 0 .624-.42 1.101-.807 1.526L4.99 10.553v.744h4.78v-.99H6.643v-.069L8.41 8.252c.65-.724 1.237-1.332 1.237-2.27C9.646 4.849 8.723 4 7.308 4c-1.573 0-2.36 1.064-2.36 2.15v.057h1.138Zm6.559 1.883h.786c.823 0 1.374.481 1.379 1.179.01.707-.55 1.216-1.421 1.21-.77-.005-1.326-.419-1.379-.953h-1.095c.042 1.053.938 1.918 2.464 1.918 1.478 0 2.642-.839 2.62-2.144-.02-1.143-.922-1.651-1.551-1.714v-.063c.535-.09 1.347-.66 1.326-1.678-.026-1.053-.933-1.855-2.359-1.845-1.5.005-2.317.88-2.348 1.898h1.116c.032-.498.498-.944 1.206-.944.703 0 1.206.435 1.206 1.07.005.64-.504 1.106-1.2 1.106h-.75v.96Z"/>
                                        </svg>
                                    </span>
                                    <input wire:model="state.single_mother" name="single_mother" type="radio" value="0" /> No
                                    <input wire:model="state.single_mother" name="single_mother" type="radio" value="1" /> Yes
                                    @if ($errors->has('single_mother'))
                                        <span class="text-danger">{{ $errors->first('single_mother') }}</span>
                                    @endif
                                </div>--}}
                                <div class="form-check">
                                    <input  wire:model="state.single_mother" class="form-check-input" type="radio" value="0" name="single_mother">
                                    <label class="form-check-label" for="single_mother">No</label>
                                </div>
                                    <div class="form-check">
                                    <input  wire:model="state.single_mother" class="form-check-input" type="radio" value="1" name="single_mother">
                                    <label class="form-check-label" for="single_mother">Yes</label>
                                </div>
                                @if ($errors->has('single_mother'))
                                    <span class="text-danger">{{ $errors->first('single_mother') }}</span>
                                @endif
                            </div>
                            <div class="form-group mb-4">
                                <label for="orphan">Orphan</label>
                                {{--<div class="input-group">
                                    <span class="input-group-text border-gray-300" id="basic-addon3">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-123" viewBox="0 0 16 16">
                                            <path d="M2.873 11.297V4.142H1.699L0 5.379v1.137l1.64-1.18h.06v5.961h1.174Zm3.213-5.09v-.063c0-.618.44-1.169 1.196-1.169.676 0 1.174.44 1.174 1.106 0 .624-.42 1.101-.807 1.526L4.99 10.553v.744h4.78v-.99H6.643v-.069L8.41 8.252c.65-.724 1.237-1.332 1.237-2.27C9.646 4.849 8.723 4 7.308 4c-1.573 0-2.36 1.064-2.36 2.15v.057h1.138Zm6.559 1.883h.786c.823 0 1.374.481 1.379 1.179.01.707-.55 1.216-1.421 1.21-.77-.005-1.326-.419-1.379-.953h-1.095c.042 1.053.938 1.918 2.464 1.918 1.478 0 2.642-.839 2.62-2.144-.02-1.143-.922-1.651-1.551-1.714v-.063c.535-.09 1.347-.66 1.326-1.678-.026-1.053-.933-1.855-2.359-1.845-1.5.005-2.317.88-2.348 1.898h1.116c.032-.498.498-.944 1.206-.944.703 0 1.206.435 1.206 1.07.005.64-.504 1.106-1.2 1.106h-.75v.96Z"/>
                                        </svg>
                                    </span>
                                    <input wire:model="state.orphan" name="orphan" type="radio" value="0" /> No
                                    <input wire:model="state.orphan" name="orphan" type="radio" value="1" /> Yes
                                </div>--}}
                                <div class="form-check">
                                    <input wire:model="state.orphan" name="orphan" class="form-check-input" type="radio" value="0">
                                    <label class="form-check-label" for="single_mother">No</label>
                                </div>
                                <div class="form-check">
                                    <input wire:model="state.orphan" name="orphan" class="form-check-input" type="radio" value="1">
                                    <label class="form-check-label" for="single_mother">Yes</label>
                                </div>
                                @if ($errors->has('orphan'))
                                    <span class="text-danger">{{ $errors->first('orphan') }}</span>
                                @endif
                            </div>
                            <div class="form-group mb-4">
                                <label for="disabled">Disabled</label>
                                {{--<div class="input-group">
                                    <span class="input-group-text border-gray-300" id="basic-addon3">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-123" viewBox="0 0 16 16">
                                            <path d="M2.873 11.297V4.142H1.699L0 5.379v1.137l1.64-1.18h.06v5.961h1.174Zm3.213-5.09v-.063c0-.618.44-1.169 1.196-1.169.676 0 1.174.44 1.174 1.106 0 .624-.42 1.101-.807 1.526L4.99 10.553v.744h4.78v-.99H6.643v-.069L8.41 8.252c.65-.724 1.237-1.332 1.237-2.27C9.646 4.849 8.723 4 7.308 4c-1.573 0-2.36 1.064-2.36 2.15v.057h1.138Zm6.559 1.883h.786c.823 0 1.374.481 1.379 1.179.01.707-.55 1.216-1.421 1.21-.77-.005-1.326-.419-1.379-.953h-1.095c.042 1.053.938 1.918 2.464 1.918 1.478 0 2.642-.839 2.62-2.144-.02-1.143-.922-1.651-1.551-1.714v-.063c.535-.09 1.347-.66 1.326-1.678-.026-1.053-.933-1.855-2.359-1.845-1.5.005-2.317.88-2.348 1.898h1.116c.032-.498.498-.944 1.206-.944.703 0 1.206.435 1.206 1.07.005.64-.504 1.106-1.2 1.106h-.75v.96Z"/>
                                        </svg>
                                    </span>
                                    <input wire:model="state.disabled" name="disabled" type="radio" value="0" /> No
                                    <input wire:model="state.disabled" name="disabled" type="radio" value="1" /> Yes
                                    @if ($errors->has('disabled'))
                                        <span class="text-danger">{{ $errors->first('disabled') }}</span>
                                    @endif
                                </div>--}}
                                <div class="form-check">
                                    <input wire:model="state.disabled" name="disabled" class="form-check-input" type="radio" value="0">
                                    <label class="form-check-label" for="disabled">No</label>
                                </div>
                                <div class="form-check">
                                    <input wire:model="state.disabled" name="disabled" class="form-check-input" type="radio" value="1">
                                    <label class="form-check-label" for="disabled">Yes</label>
                                </div>
                                @if ($errors->has('orphan'))
                                    <span class="text-danger">{{ $errors->first('orphan') }}</span>
                                @endif
                            </div>
                            <div class="form-group mb-4">
                                <label for="elderly">Elderly</label>
                                {{--<div class="input-group">
                                    <span class="input-group-text border-gray-300" id="basic-addon3">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-123" viewBox="0 0 16 16">
                                            <path d="M2.873 11.297V4.142H1.699L0 5.379v1.137l1.64-1.18h.06v5.961h1.174Zm3.213-5.09v-.063c0-.618.44-1.169 1.196-1.169.676 0 1.174.44 1.174 1.106 0 .624-.42 1.101-.807 1.526L4.99 10.553v.744h4.78v-.99H6.643v-.069L8.41 8.252c.65-.724 1.237-1.332 1.237-2.27C9.646 4.849 8.723 4 7.308 4c-1.573 0-2.36 1.064-2.36 2.15v.057h1.138Zm6.559 1.883h.786c.823 0 1.374.481 1.379 1.179.01.707-.55 1.216-1.421 1.21-.77-.005-1.326-.419-1.379-.953h-1.095c.042 1.053.938 1.918 2.464 1.918 1.478 0 2.642-.839 2.62-2.144-.02-1.143-.922-1.651-1.551-1.714v-.063c.535-.09 1.347-.66 1.326-1.678-.026-1.053-.933-1.855-2.359-1.845-1.5.005-2.317.88-2.348 1.898h1.116c.032-.498.498-.944 1.206-.944.703 0 1.206.435 1.206 1.07.005.64-.504 1.106-1.2 1.106h-.75v.96Z"/>
                                        </svg>
                                    </span>
                                    <input wire:model="state.elderly" name="elderly" type="radio" value="0" /> No
                                    <input wire:model="state.elderly" name="elderly" type="radio" value="1" /> Yes
                                    @if ($errors->has('elderly'))
                                        <span class="text-danger">{{ $errors->first('elderly') }}</span>
                                    @endif
                                </div>--}}
                                <div class="form-check">
                                    <input wire:model="state.elderly" name="elderly" class="form-check-input" type="radio" value="0">
                                    <label class="form-check-label" for="elderly">No</label>
                                </div>
                                <div class="form-check">
                                    <input wire:model="state.elderly" name="elderly" class="form-check-input" type="radio" value="1">
                                    <label class="form-check-label" for="elderly">Yes</label>
                                </div>
                                @if ($errors->has('elderly'))
                                    <span class="text-danger">{{ $errors->first('elderly') }}</span>
                                @endif
                            </div>
                            <div class="form-group mb-4">
                                <label for="student">Student</label>
                                {{--<div class="input-group">
                                    <span class="input-group-text border-gray-300" id="basic-addon3">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-123" viewBox="0 0 16 16">
                                            <path d="M2.873 11.297V4.142H1.699L0 5.379v1.137l1.64-1.18h.06v5.961h1.174Zm3.213-5.09v-.063c0-.618.44-1.169 1.196-1.169.676 0 1.174.44 1.174 1.106 0 .624-.42 1.101-.807 1.526L4.99 10.553v.744h4.78v-.99H6.643v-.069L8.41 8.252c.65-.724 1.237-1.332 1.237-2.27C9.646 4.849 8.723 4 7.308 4c-1.573 0-2.36 1.064-2.36 2.15v.057h1.138Zm6.559 1.883h.786c.823 0 1.374.481 1.379 1.179.01.707-.55 1.216-1.421 1.21-.77-.005-1.326-.419-1.379-.953h-1.095c.042 1.053.938 1.918 2.464 1.918 1.478 0 2.642-.839 2.62-2.144-.02-1.143-.922-1.651-1.551-1.714v-.063c.535-.09 1.347-.66 1.326-1.678-.026-1.053-.933-1.855-2.359-1.845-1.5.005-2.317.88-2.348 1.898h1.116c.032-.498.498-.944 1.206-.944.703 0 1.206.435 1.206 1.07.005.64-.504 1.106-1.2 1.106h-.75v.96Z"/>
                                        </svg>
                                    </span>
                                    <input wire:model="state.student" name="student" type="radio" value="0" /> No
                                    <input wire:model="state.student" name="student" type="radio" value="1" /> Yes
                                </div>--}}
                                <div class="form-check">
                                    <input wire:model="state.student" name="student" class="form-check-input" type="radio" value="0">
                                    <label class="form-check-label" for="student">No</label>
                                </div>
                                <div class="form-check">
                                    <input wire:model="state.student" name="student" class="form-check-input" type="radio" value="1">
                                    <label class="form-check-label" for="student">Yes</label>
                                </div>
                                @if ($errors->has('student'))
                                    <span class="text-danger">{{ $errors->first('student') }}</span>
                                @endif
                            </div>
                            <div class="form-group mb-4">
                                <label for="bkk">Bantuan Khas Kerajaan</label>
                                {{--<div class="input-group">
                                    <span class="input-group-text border-gray-300" id="basic-addon3">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-123" viewBox="0 0 16 16">
                                            <path d="M2.873 11.297V4.142H1.699L0 5.379v1.137l1.64-1.18h.06v5.961h1.174Zm3.213-5.09v-.063c0-.618.44-1.169 1.196-1.169.676 0 1.174.44 1.174 1.106 0 .624-.42 1.101-.807 1.526L4.99 10.553v.744h4.78v-.99H6.643v-.069L8.41 8.252c.65-.724 1.237-1.332 1.237-2.27C9.646 4.849 8.723 4 7.308 4c-1.573 0-2.36 1.064-2.36 2.15v.057h1.138Zm6.559 1.883h.786c.823 0 1.374.481 1.379 1.179.01.707-.55 1.216-1.421 1.21-.77-.005-1.326-.419-1.379-.953h-1.095c.042 1.053.938 1.918 2.464 1.918 1.478 0 2.642-.839 2.62-2.144-.02-1.143-.922-1.651-1.551-1.714v-.063c.535-.09 1.347-.66 1.326-1.678-.026-1.053-.933-1.855-2.359-1.845-1.5.005-2.317.88-2.348 1.898h1.116c.032-.498.498-.944 1.206-.944.703 0 1.206.435 1.206 1.07.005.64-.504 1.106-1.2 1.106h-.75v.96Z"/>
                                        </svg>
                                    </span>
                                    <input wire:model="state.bkk" name="bkk" type="radio" value="0" /> No
                                    <input wire:model="state.bkk" name="bkk" type="radio" value="1" /> Yes
                                    @if ($errors->has('bkk'))
                                        <span class="text-danger">{{ $errors->first('bkk') }}</span>
                                    @endif
                                </div>--}}
                                <div class="form-check">
                                    <input wire:model="state.bkk" name="bkk" class="form-check-input" type="radio" value="0">
                                    <label class="form-check-label" for="bkk">No</label>
                                </div>
                                <div class="form-check">
                                    <input wire:model="state.bkk" name="bkk" class="form-check-input" type="radio" value="1">
                                    <label class="form-check-label" for="bkk">Yes</label>
                                </div>
                                @if ($errors->has('bkk'))
                                    <span class="text-danger">{{ $errors->first('bkk') }}</span>
                                @endif
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <span>Update Details</span>
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
                <div class="modal-header"></div>
            </div>
        </div>
    </div>
    <!-- End of Edit Passenger Card Content -->

    <!-- Generate Voucher Modal Content -->
    <div wire:ignore.self class="modal fade" id="generateVoucherModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-md-5">
                    <h2 class="h4 text-center">
                        <span>Generate Refund Voucher</span>
                    </h2>

                    @if (!is_null($ticketCards))
                    <!-- Form -->
                    <form wire:submit.prevent="{{'generateVoucher'}}">
                        @csrf
                        <div class="form-group mb-4">
                            <label for="card_holder_name">Cardholder Name</label>
                            <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-lines-fill" viewBox="0 0 16 16">
                                        <path d="M6 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm-5 6s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H1zM11 3.5a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 0 1h-4a.5.5 0 0 1-.5-.5zm.5 2.5a.5.5 0 0 0 0 1h4a.5.5 0 0 0 0-1h-4zm2 3a.5.5 0 0 0 0 1h2a.5.5 0 0 0 0-1h-2zm0 3a.5.5 0 0 0 0 1h2a.5.5 0 0 0 0-1h-2z"/>
                                    </svg>
                                </span>
                                <span class="form-control border-gray-300" id="card_holder_name">{{$ticketCards->card_holder_name}}</span>
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="id_number">ID Number</label>
                            <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-123" viewBox="0 0 16 16">
                                        <path d="M2.873 11.297V4.142H1.699L0 5.379v1.137l1.64-1.18h.06v5.961h1.174Zm3.213-5.09v-.063c0-.618.44-1.169 1.196-1.169.676 0 1.174.44 1.174 1.106 0 .624-.42 1.101-.807 1.526L4.99 10.553v.744h4.78v-.99H6.643v-.069L8.41 8.252c.65-.724 1.237-1.332 1.237-2.27C9.646 4.849 8.723 4 7.308 4c-1.573 0-2.36 1.064-2.36 2.15v.057h1.138Zm6.559 1.883h.786c.823 0 1.374.481 1.379 1.179.01.707-.55 1.216-1.421 1.21-.77-.005-1.326-.419-1.379-.953h-1.095c.042 1.053.938 1.918 2.464 1.918 1.478 0 2.642-.839 2.62-2.144-.02-1.143-.922-1.651-1.551-1.714v-.063c.535-.09 1.347-.66 1.326-1.678-.026-1.053-.933-1.855-2.359-1.845-1.5.005-2.317.88-2.348 1.898h1.116c.032-.498.498-.944 1.206-.944.703 0 1.206.435 1.206 1.07.005.64-.504 1.106-1.2 1.106h-.75v.96Z"/>
                                    </svg>
                                </span>
                                <span class="form-control border-gray-300" id="id_number">{{$ticketCards->id_number}}</span>
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="manufacturing_id">Manufacturing No.</label>
                            <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-123" viewBox="0 0 16 16">
                                        <path d="M2.873 11.297V4.142H1.699L0 5.379v1.137l1.64-1.18h.06v5.961h1.174Zm3.213-5.09v-.063c0-.618.44-1.169 1.196-1.169.676 0 1.174.44 1.174 1.106 0 .624-.42 1.101-.807 1.526L4.99 10.553v.744h4.78v-.99H6.643v-.069L8.41 8.252c.65-.724 1.237-1.332 1.237-2.27C9.646 4.849 8.723 4 7.308 4c-1.573 0-2.36 1.064-2.36 2.15v.057h1.138Zm6.559 1.883h.786c.823 0 1.374.481 1.379 1.179.01.707-.55 1.216-1.421 1.21-.77-.005-1.326-.419-1.379-.953h-1.095c.042 1.053.938 1.918 2.464 1.918 1.478 0 2.642-.839 2.62-2.144-.02-1.143-.922-1.651-1.551-1.714v-.063c.535-.09 1.347-.66 1.326-1.678-.026-1.053-.933-1.855-2.359-1.845-1.5.005-2.317.88-2.348 1.898h1.116c.032-.498.498-.944 1.206-.944.703 0 1.206.435 1.206 1.07.005.64-.504 1.106-1.2 1.106h-.75v.96Z"/>
                                    </svg>
                                </span>
                                <span class="form-control border-gray-300" id="manufacturing_id">{{$ticketCards->manufacturing_id}}</span>
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="card_number">Card Number</label>
                            <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-credit-card-2-front-fill" viewBox="0 0 16 16">
                                        <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4zm2.5 1a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h2a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-2zm0 3a.5.5 0 0 0 0 1h5a.5.5 0 0 0 0-1h-5zm0 2a.5.5 0 0 0 0 1h1a.5.5 0 0 0 0-1h-1zm3 0a.5.5 0 0 0 0 1h1a.5.5 0 0 0 0-1h-1zm3 0a.5.5 0 0 0 0 1h1a.5.5 0 0 0 0-1h-1zm3 0a.5.5 0 0 0 0 1h1a.5.5 0 0 0 0-1h-1z"/>
                                    </svg>
                                </span>
                                <span class="form-control border-gray-300" id="card_number">{{$ticketCards->card_number}}</span>
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="card_status">Card Status</label>
                            <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-square-fill" viewBox="0 0 16 16">
                                        <path d="M2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2zm10.03 4.97a.75.75 0 0 1 .011 1.05l-3.992 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.75.75 0 0 1 1.08-.022z"/>
                                    </svg>
                                </span>
                                @if($ticketCards->card_status == '1')
                                    <span class="form-control border-gray-300" id="card_status">Active</span>
                                @elseif($ticketCards->card_status == '2')
                                    <span class="form-control border-gray-300" id="card_status">Inactive</span>
                                @else
                                    <span class="form-control border-gray-300" id="card_status">Blacklisted</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="card_type">Card Type</label>
                            <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-people-fill" viewBox="0 0 16 16">
                                        <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                                        <path fill-rule="evenodd" d="M5.216 14A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216z"/>
                                        <path d="M4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"/>
                                    </svg>
                                </span>
                                @if($ticketCards->card_type == '1')
                                    <span class="form-control border-gray-300" id="card_type">Passenger</span>
                                @elseif($ticketCards->card_type == '2')
                                    <span class="form-control border-gray-300" id="card_type">Agent</span>
                                @elseif($ticketCards->card_type == '3')
                                    <span class="form-control border-gray-300" id="card_type">Sub-Agent</span>
                                @elseif($ticketCards->card_type == '4')
                                    <span class="form-control border-gray-300" id="card_type">Mobile-Agent</span>
                                @elseif($ticketCards->card_type == '5')
                                    <span class="form-control border-gray-300" id="card_type">UPM Student</span>
                                @elseif($ticketCards->card_type == '6')
                                    <span class="form-control border-gray-300" id="card_type">KTB Staff</span>
                                @elseif($ticketCards->card_type == '7')
                                    <span class="form-control border-gray-300" id="card_type">Inspector</span>
                                @else
                                    <span class="form-control border-gray-300" id="card_type">Driver</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="current_balance_old">Current Balance in Card (RM)</label>
                            <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <i class="fa fa-money-bill"></i>
                                </span>
C                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="current_balance_new">Balance to be Refunded (RM)</label>
                            <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <i class="fa fa-money-bill"></i>
                                </span>
                                <input wire:model.defer="state.current_balance" class="form-control border-gray-300" id="current_balance_new"  autofocus required>
                                @if ($errors->has('current_balance_new'))
                                    <span class="text-danger">{{ $errors->first('current_balance_new') }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <span>Generate</span>
                            </button>
                        </div>
                    </form>
                    @endif
                </div>
                <div class="modal-header"></div>
            </div>
        </div>
    </div>
    <!-- End of Generate Voucher Modal Content -->

    <!-- Change Region Modal Content -->
    <div wire:ignore.self class="modal fade" id="changeRegionModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-md-5">
                    <h2 class="h4 text-center">
                        <span>Change Region</span>
                    </h2>

                    @if (!is_null($ticketCards))
                    <!-- Form -->
                    <form wire:submit.prevent="{{ 'updateRegion' }}">
                        @csrf
                        <div class="form-group mb-4">
                            <label for="card_holder_name">Cardholder Name</label>
                            <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-lines-fill" viewBox="0 0 16 16">
                                        <path d="M6 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm-5 6s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H1zM11 3.5a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 0 1h-4a.5.5 0 0 1-.5-.5zm.5 2.5a.5.5 0 0 0 0 1h4a.5.5 0 0 0 0-1h-4zm2 3a.5.5 0 0 0 0 1h2a.5.5 0 0 0 0-1h-2zm0 3a.5.5 0 0 0 0 1h2a.5.5 0 0 0 0-1h-2z"/>
                                    </svg>
                                </span>
                                <span class="form-control border-gray-300" id="card_holder_name">{{$ticketCards->card_holder_name}}</span>
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="manufacturing_id">Manufacturing No.</label>
                            <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-123" viewBox="0 0 16 16">
                                        <path d="M2.873 11.297V4.142H1.699L0 5.379v1.137l1.64-1.18h.06v5.961h1.174Zm3.213-5.09v-.063c0-.618.44-1.169 1.196-1.169.676 0 1.174.44 1.174 1.106 0 .624-.42 1.101-.807 1.526L4.99 10.553v.744h4.78v-.99H6.643v-.069L8.41 8.252c.65-.724 1.237-1.332 1.237-2.27C9.646 4.849 8.723 4 7.308 4c-1.573 0-2.36 1.064-2.36 2.15v.057h1.138Zm6.559 1.883h.786c.823 0 1.374.481 1.379 1.179.01.707-.55 1.216-1.421 1.21-.77-.005-1.326-.419-1.379-.953h-1.095c.042 1.053.938 1.918 2.464 1.918 1.478 0 2.642-.839 2.62-2.144-.02-1.143-.922-1.651-1.551-1.714v-.063c.535-.09 1.347-.66 1.326-1.678-.026-1.053-.933-1.855-2.359-1.845-1.5.005-2.317.88-2.348 1.898h1.116c.032-.498.498-.944 1.206-.944.703 0 1.206.435 1.206 1.07.005.64-.504 1.106-1.2 1.106h-.75v.96Z"/>
                                    </svg>
                                </span>
                                <span class="form-control border-gray-300" id="manufacturing_id">{{$ticketCards->manufacturing_id}}</span>
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="card_number">Card Number</label>
                            <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-credit-card-2-front-fill" viewBox="0 0 16 16">
                                        <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4zm2.5 1a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h2a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-2zm0 3a.5.5 0 0 0 0 1h5a.5.5 0 0 0 0-1h-5zm0 2a.5.5 0 0 0 0 1h1a.5.5 0 0 0 0-1h-1zm3 0a.5.5 0 0 0 0 1h1a.5.5 0 0 0 0-1h-1zm3 0a.5.5 0 0 0 0 1h1a.5.5 0 0 0 0-1h-1zm3 0a.5.5 0 0 0 0 1h1a.5.5 0 0 0 0-1h-1z"/>
                                    </svg>
                                </span>
                                <span class="form-control border-gray-300" id="card_number">{{$ticketCards->card_number}}</span>
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="card_type">Card Type</label>
                            <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-people-fill" viewBox="0 0 16 16">
                                        <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                                        <path fill-rule="evenodd" d="M5.216 14A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216z"/>
                                        <path d="M4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"/>
                                    </svg>
                                </span>
                                @if($ticketCards->card_type == '1')
                                    <span class="form-control border-gray-300" id="card_type">Passenger</span>
                                @elseif($ticketCards->card_type == '2')
                                    <span class="form-control border-gray-300" id="card_type">Agent</span>
                                @elseif($ticketCards->card_type == '3')
                                    <span class="form-control border-gray-300" id="card_type">Sub-Agent</span>
                                @elseif($ticketCards->card_type == '4')
                                    <span class="form-control border-gray-300" id="card_type">Mobile-Agent</span>
                                @elseif($ticketCards->card_type == '5')
                                    <span class="form-control border-gray-300" id="card_type">UPM Student</span>
                                @elseif($ticketCards->card_type == '6')
                                    <span class="form-control border-gray-300" id="card_type">KTB Staff</span>
                                @elseif($ticketCards->card_type == '7')
                                    <span class="form-control border-gray-300" id="card_type">Inspector</span>
                                @else
                                    <span class="form-control border-gray-300" id="card_type">Driver</span>
                                @endif
                            </div>
                        </div>
                        @if (!is_null($regions))
                        <div class="form-group mb-4">
                            <label for=region_id">Region</label>
                            <div class="input-group">
                                <span class="input-group-text" id="basic-addon1">
                                     <i class="fas fa-address-card fa-fw"></i>
                                </span>
                                <select wire:model.defer="state.region_id" id="region_id" class="form-select border-gray-300" autofocus required>
                                    <option value="">Choose Region</option>
                                    @foreach($regions as $region)
                                        <option value="{{$region->id}}">{{$region->description}}</option>
                                    @endforeach
                                </select>
                                @if ($errors->has('region_id'))
                                    <span class="text-danger">{{ $errors->first('region_id') }}</span>
                                @endif
                            </div>
                        </div>
                        @endif
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                    @endif
                </div>
                <div class="modal-header"></div>
            </div>
        </div>
    </div>
    <!-- End of Change Region Modal Content -->

    <!-- Blacklist Modal Content -->
    <div wire:ignore.self class="modal fade" id="blacklistModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-md-5">
                    <h2 class="h4 text-center">
                        <span>Blacklist Card</span>
                    </h2>

                @if (!is_null($ticketCards))
                    <!-- Form -->
                        <form wire:submit.prevent="{{ 'blacklistCard' }}">
                            @csrf
                            <div class="form-group mb-4">
                                <label for="region_id">Region</label>
                                <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-lines-fill" viewBox="0 0 16 16">
                                        <path d="M6 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm-5 6s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H1zM11 3.5a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 0 1h-4a.5.5 0 0 1-.5-.5zm.5 2.5a.5.5 0 0 0 0 1h4a.5.5 0 0 0 0-1h-4zm2 3a.5.5 0 0 0 0 1h2a.5.5 0 0 0 0-1h-2zm0 3a.5.5 0 0 0 0 1h2a.5.5 0 0 0 0-1h-2z"/>
                                    </svg>
                                </span>
                                    <span class="form-control border-gray-300" id="region_id">{{$ticketCards->region->description}}</span>
                                </div>
                            </div>
                            <div class="form-group mb-4">
                                <label for="card_holder_name">Cardholder Name</label>
                                <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-lines-fill" viewBox="0 0 16 16">
                                        <path d="M6 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm-5 6s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H1zM11 3.5a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 0 1h-4a.5.5 0 0 1-.5-.5zm.5 2.5a.5.5 0 0 0 0 1h4a.5.5 0 0 0 0-1h-4zm2 3a.5.5 0 0 0 0 1h2a.5.5 0 0 0 0-1h-2zm0 3a.5.5 0 0 0 0 1h2a.5.5 0 0 0 0-1h-2z"/>
                                    </svg>
                                </span>
                                    <span class="form-control border-gray-300" id="card_holder_name">{{$ticketCards->card_holder_name}}</span>
                                </div>
                            </div>
                            <div class="form-group mb-4">
                                <label for="id_number">ID Number</label>
                                <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-123" viewBox="0 0 16 16">
                                        <path d="M2.873 11.297V4.142H1.699L0 5.379v1.137l1.64-1.18h.06v5.961h1.174Zm3.213-5.09v-.063c0-.618.44-1.169 1.196-1.169.676 0 1.174.44 1.174 1.106 0 .624-.42 1.101-.807 1.526L4.99 10.553v.744h4.78v-.99H6.643v-.069L8.41 8.252c.65-.724 1.237-1.332 1.237-2.27C9.646 4.849 8.723 4 7.308 4c-1.573 0-2.36 1.064-2.36 2.15v.057h1.138Zm6.559 1.883h.786c.823 0 1.374.481 1.379 1.179.01.707-.55 1.216-1.421 1.21-.77-.005-1.326-.419-1.379-.953h-1.095c.042 1.053.938 1.918 2.464 1.918 1.478 0 2.642-.839 2.62-2.144-.02-1.143-.922-1.651-1.551-1.714v-.063c.535-.09 1.347-.66 1.326-1.678-.026-1.053-.933-1.855-2.359-1.845-1.5.005-2.317.88-2.348 1.898h1.116c.032-.498.498-.944 1.206-.944.703 0 1.206.435 1.206 1.07.005.64-.504 1.106-1.2 1.106h-.75v.96Z"/>
                                    </svg>
                                </span>
                                    <span class="form-control border-gray-300" id="id_number">{{$ticketCards->id_number}}</span>
                                </div>
                            </div>
                            <div class="form-group mb-4">
                                <label for="manufacturing_id">Manufacturing No.</label>
                                <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-123" viewBox="0 0 16 16">
                                        <path d="M2.873 11.297V4.142H1.699L0 5.379v1.137l1.64-1.18h.06v5.961h1.174Zm3.213-5.09v-.063c0-.618.44-1.169 1.196-1.169.676 0 1.174.44 1.174 1.106 0 .624-.42 1.101-.807 1.526L4.99 10.553v.744h4.78v-.99H6.643v-.069L8.41 8.252c.65-.724 1.237-1.332 1.237-2.27C9.646 4.849 8.723 4 7.308 4c-1.573 0-2.36 1.064-2.36 2.15v.057h1.138Zm6.559 1.883h.786c.823 0 1.374.481 1.379 1.179.01.707-.55 1.216-1.421 1.21-.77-.005-1.326-.419-1.379-.953h-1.095c.042 1.053.938 1.918 2.464 1.918 1.478 0 2.642-.839 2.62-2.144-.02-1.143-.922-1.651-1.551-1.714v-.063c.535-.09 1.347-.66 1.326-1.678-.026-1.053-.933-1.855-2.359-1.845-1.5.005-2.317.88-2.348 1.898h1.116c.032-.498.498-.944 1.206-.944.703 0 1.206.435 1.206 1.07.005.64-.504 1.106-1.2 1.106h-.75v.96Z"/>
                                    </svg>
                                </span>
                                    <span class="form-control border-gray-300" id="manufacturing_id">{{$ticketCards->manufacturing_id}}</span>
                                </div>
                            </div>
                            <div class="form-group mb-4">
                                <label for="card_number">Card Number</label>
                                <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-credit-card-2-front-fill" viewBox="0 0 16 16">
                                        <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4zm2.5 1a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h2a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-2zm0 3a.5.5 0 0 0 0 1h5a.5.5 0 0 0 0-1h-5zm0 2a.5.5 0 0 0 0 1h1a.5.5 0 0 0 0-1h-1zm3 0a.5.5 0 0 0 0 1h1a.5.5 0 0 0 0-1h-1zm3 0a.5.5 0 0 0 0 1h1a.5.5 0 0 0 0-1h-1zm3 0a.5.5 0 0 0 0 1h1a.5.5 0 0 0 0-1h-1z"/>
                                    </svg>
                                </span>
                                    <span class="form-control border-gray-300" id="card_number">{{$ticketCards->card_number}}</span>
                                </div>
                            </div>
                            <div class="form-group mb-4">
                                <label for="card_status">Card Status</label>
                                <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-square-fill" viewBox="0 0 16 16">
                                        <path d="M2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2zm10.03 4.97a.75.75 0 0 1 .011 1.05l-3.992 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.75.75 0 0 1 1.08-.022z"/>
                                    </svg>
                                </span>
                                    @if($ticketCards->card_status == '1')
                                        <span class="form-control border-gray-300" id="card_status">Active</span>
                                    @elseif($ticketCards->card_status == '2')
                                        <span class="form-control border-gray-300" id="card_status">Inactive</span>
                                    @else
                                        <span class="form-control border-gray-300" id="card_status">Blacklisted</span>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group mb-4">
                                <label for="card_type">Card Type</label>
                                <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-people-fill" viewBox="0 0 16 16">
                                        <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                                        <path fill-rule="evenodd" d="M5.216 14A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216z"/>
                                        <path d="M4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"/>
                                    </svg>
                                </span>
                                    @if($ticketCards->card_type == '1')
                                        <span class="form-control border-gray-300" id="card_type">Passenger</span>
                                    @elseif($ticketCards->card_type == '2')
                                        <span class="form-control border-gray-300" id="card_type">Agent</span>
                                    @elseif($ticketCards->card_type == '3')
                                        <span class="form-control border-gray-300" id="card_type">Sub-Agent</span>
                                    @elseif($ticketCards->card_type == '4')
                                        <span class="form-control border-gray-300" id="card_type">Mobile-Agent</span>
                                    @elseif($ticketCards->card_type == '5')
                                        <span class="form-control border-gray-300" id="card_type">UPM Student</span>
                                    @elseif($ticketCards->card_type == '6')
                                        <span class="form-control border-gray-300" id="card_type">KTB Staff</span>
                                    @elseif($ticketCards->card_type == '7')
                                        <span class="form-control border-gray-300" id="card_type">Inspector</span>
                                    @else
                                        <span class="form-control border-gray-300" id="card_type">Driver</span>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group mb-4">
                                <label for="current_balance_old">Current Balance in Card (RM)</label>
                                <div class="input-group">
                                    <span class="input-group-text border-gray-300" id="basic-addon3">
                                        <i class="fa fa-money-bill"></i>
                                    </span>
                                    <span class="form-control border-gray-300" id="current_balance">{{$ticketCards->current_balance}}</span>
                                </div>
                            </div>
                            <div class="form-group mb-4">
                                <label for="createdBy_agent_id">Created By</label>
                                <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <i class="fa fa-money-bill"></i>
                                </span>
                                    <span class="form-control border-gray-300" id="createdBy_agent_id">{{$ticketCards->createdBy_agent_id}}</span>
                                </div>
                            </div>
                            <div class="form-group mb-4">
                                <label for="date_created">Created On</label>
                                <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <i class="fa fa-money-bill"></i>
                                </span>
                                    <span class="form-control border-gray-300" id="date_created">{{$ticketCards->date_created}}</span>
                                </div>
                            </div>
                            <div class="form-group mb-4">
                                <label for="expiry_date">Expiry On</label>
                                <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <i class="fa fa-money-bill"></i>
                                </span>
                                    <span class="form-control border-gray-300" id="expiry_date">{{$ticketCards->expiry_date}}</span>
                                </div>
                            </div>
                            <div class="form-group mb-4">
                                <label for="reason">Reason to Blacklist</label>
                                <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <i class="fa fa-money-bill"></i>
                                </span>
                                    <input wire:model.defer="state.reason" class="form-control border-gray-300" id="reason"  autofocus required>
                                    @if ($errors->has('reason'))
                                        <span class="text-danger">{{ $errors->first('reason') }}</span>
                                    @endif
                                </div>
                            </div>
                            <input type="hidden" wire:model.defer="state.id" class="form-control border-gray-300" id="card_id">

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <span>Blacklist Card</span>
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
                <div class="modal-header"></div>
            </div>
        </div>
    </div>
    <!-- End of Blacklist Modal Content -->

    <!-- Reset Modal -->
    <div wire:ignore.self class="modal fade" id="resetModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5>Reset Card</h5>
                </div>

                <div class="modal-body">
                    <h4>Are you sure you want to reset this card?</h4>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa fa-times mr-1"></i> Cancel</button>
                    <button type="button" wire:click.prevent="updateResetCard" class="btn btn-danger" data-bs-dismiss="modal">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-clockwise" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z"/>
                            <path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/>
                        </svg>
                        Reset Card
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- End of Remove User Modal Content -->
</div>
@section('script')
    <script>
        window.addEventListener('show-edit-form', event => {
            $('#editModal').modal('show');
        });
        window.addEventListener('show-form', event => {
            $('#generateVoucherModal').modal('show');
        });
        window.addEventListener('show-change-region-form', event => {
            $('#changeRegionModal').modal('show');
        });
        window.addEventListener('show-blacklist-form', event => {
            $('#blacklistModal').modal('show');
        });
        window.addEventListener('show-reset-form', event => {
            $('#resetModal').modal('show');
        });
    </script>
@endsection
