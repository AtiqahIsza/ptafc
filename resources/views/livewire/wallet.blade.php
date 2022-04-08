<div class="main py-4">
    <div class="d-block mb-md-0" style="position: relative">
        <h2>All Wallet Transaction Records</h2>
        <button onclick="window.location='{{ route('topupWallet') }}'" class="buttonAdd-map btn btn-gray-800 d-inline-flex align-items-center me-2">
            <i class="fa fa-plus-circle mr-1 fa-fw"></i>
            Top-up Wallet
        </button>
        <button  wire:click.prevent="editTopup" class="buttonAdd btn btn-warning d-inline-flex align-items-center me-2" data-bs-toggle="modal" data-bs-target="#modalPromo">
            <i class="fa fa-plus-circle mr-1 fa-fw"></i>
            Edit Top-up Promo
        </button>

        {{--<button wire:click.prevent="topupWallet" class="buttonAdd btn btn-gray-800 d-inline-flex align-items-center me-2" data-bs-toggle="modal" data-bs-target="#modalTopup">
            <i class="fa fa-plus-circle mr-1 fa-fw"></i>
            Topup Wallet
        </button>--}}
    </div>
    <br>
    <div class="card card-body border-3 shadow table-wrapper table-responsive">
        <livewire:wallet-table/>
    </div>

    <!-- Edit Commission % Modal Content -->
    <div wire:ignore.self class="modal fade" id="modalPromo" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-md-5">
                    <h2 class="h4 text-center">
                        <span>Edit Top-up Promo for Bus Driver</span>
                    </h2>
                    <br>

                    <!-- Form -->
                    <form wire:submit.prevent="{{ 'updatePromo' }}">
                        @csrf
                        <div class="form-group mb-4">
                            <label for="promo_value">Enter New Top-up Promo (%)</label>
                            <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-percent" viewBox="0 0 16 16">
                                        <path d="M13.442 2.558a.625.625 0 0 1 0 .884l-10 10a.625.625 0 1 1-.884-.884l10-10a.625.625 0 0 1 .884 0zM4.5 6a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm0 1a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5zm7 6a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm0 1a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"/>
                                    </svg>
                                </span>
                                <input wire:model.defer="state.promo_value" class="form-control border-gray-300" id="promo_valuet" placeholder="{{ __('Top-up Promo') }}" autofocus required>
                                @if ($errors->has('promo_value'))
                                    <span class="text-danger">{{ $errors->first('promo_value') }}</span>
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
    <!-- End of Edit Commission % Modal Content -->
</div>
@section('script')
    <script>
        window.livewire.on('closeModal', () => {
            $('#modalPromo').modal('hide');
        });
    </script>
@endsection
