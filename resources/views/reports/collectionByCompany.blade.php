@extends('layouts.app')

@section('content')
    <div class="row">
        @if (session()->has('message'))
            <div class="alert alert-success">
                {{ session('message') }}
            </div>
        @endif
        @livewire('report-collection-by-company')
    </div>
@endsection
