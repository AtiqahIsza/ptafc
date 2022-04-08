@extends('layouts.app')

@section('content')
    <div class="row">
        @if (session()->has('message'))
            <div class="alert alert-success">
                {{ session('message') }}
            </div>
        @endif

        <div class="main py-4">
            <div class="d-block mb-md-0" style="position: relative">
                <h2>Topup Wallet</h2>
            </div>
            @livewire('wallet-topup')
        </div>
    </div>
@endsection
