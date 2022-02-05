@extends('layouts.app')

@section('content')
    <div class="row">
        @if (session()->has('message'))
            <div class="alert alert-success">
                {{ session('message') }}
            </div>
        @endif
            {{--<div class="main py-4">
                <div class="card card-body border-0 shadow table-wrapper table-responsive">
                    <livewire:user-table/>
                </div>
            </div>--}}

        @livewire('users')
    </div>
    <div class="main py-4">
@endsection
