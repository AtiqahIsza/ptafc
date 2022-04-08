@extends('layouts.app')

@section('content')
    <div class="row">
        @if (session()->has('message'))
            <div class="alert alert-success">
                {{ session('message') }}
            </div>
        @endif
        @livewire('view-wallet-driver' , ['records' => $records , 'drivers' => $drivers])
    </div>
    <div class="main py-4">
@endsection
