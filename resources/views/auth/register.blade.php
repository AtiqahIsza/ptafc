@extends('layouts.guest')

@section('content')
    <div class="container cont">
        <div class="row justify-content-center overflowreg">
            <div class="col-12 d-flex align-items-center justify-content-center">
                <div class="bg-white shadow border-0 rounded border-light p-4 p-lg-5 w-75">
                {{--<div class="bg-white shadow border-0 rounded border-light ">--}}
                    <div class="text-center text-md-center mb-4 mt-md-0">
                        <h1 class="mt-n3 mb-0 h3">{{ __('Create Account') }}</h1>
                    </div>
                    <form method="POST" action="{{ route('register') }}">
                        @csrf
                        <!--1st row-->
                        <div style="width: 100%; display: table; margin-bottom:15px;">
                            <div style="display: table-row">
                                <div class="form-group mt-4 mb-12" style="width: 50%; padding-right:5px; display: table-cell;">
                                    <label for="name">{{ __('Your Full Name') }}</label>
                                    <div class="input-group">
                                        <span class="input-group-text" id="basic-addon1">
                                            <i class="fas fa-user-alt fa-fw"></i>
                                        </span>
                                        <input name="fullname" id="fullname" type="name" class="form-control" placeholder="{{ __('Full Name') }}" value="{{ old('fullname') }}" autofocus required>
                                    </div>
                                    @if ($errors->has('fullname'))
                                        <span class="text-danger">{{ $errors->first('fullname') }}</span>
                                    @endif
                                </div>
                                <div class="form-group mt-4 mb-4" style="width: 50%; padding-right:5px; display: table-cell;">
                                    <label for="userrole">{{ __('Register As') }}</label>
                                    <div class="input-group">
                                       <span class="input-group-text" id="basic-addon1">
                                            <i class="fas fa-user-lock fa-fw"></i>
                                        </span>
                                        <select name="userrole" id="userrole" class="form-select" autofocus required>
                                            <option value="" disabled selected>Select your user role</option>
                                            <option value="1">Administrator</option>
                                            <option value="2">Report User</option>
                                        </select>
                                    </div>
                                    @if ($errors->has('userrole'))
                                        <span class="text-danger">{{ $errors->first('userrole') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!--2nd row-->
                        <div style="width: 100%; display: table; margin-bottom:15px;">
                            <div style="display: table-row">
                                {{-- <div class="form-group mt-4 mb-4" style="width: 50%; padding-right:5px; display: table-cell;">
                                    <label for="icnum">{{ __('Your IC Number') }}</label>
                                    <div class="input-group" >
                                        <span class="input-group-text" id="basic-addon1">
                                            <i class="fas fa-id-badge fa-fw"></i>
                                        </span>
                                        <input name="icnum" id="icnum" class="form-control" placeholder="{{ __('IC Number') }}" value="{{ old('icnum') }}" autofocus required>
                                    </div>
                                    @if ($errors->has('icnum'))
                                        <span class="text-danger">{{ $errors->first('icnum') }}</span>
                                    @endif
                                </div> --}}
                                <div class="form-group mt-4 mb-4" style="width: 50%; padding-right:5px; display: table-cell;">
                                    <label for="email">{{ __('Your Email') }}</label>
                                    <div class="input-group">
                                         <span class="input-group-text" id="basic-addon1">
                                            <svg class="icon icon-xs text-gray-600" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
                                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
                                            </svg>
                                        </span>
                                        <input name="email" id="email" type="email" class="form-control" placeholder="{{ __('Email') }}" value="{{ old('email') }}" autofocus required>
                                    </div>
                                    @if ($errors->has('email'))
                                        <span class="text-danger">{{ $errors->first('email') }}</span>
                                    @endif
                                </div>
                                <div class="form-group mt-4 mb-4" style="width: 50%; padding-right:5px; display: table-cell;">
                                    <label for="username">{{ __('Your Username') }}</label>
                                    <div class="input-group">
                                        <span class="input-group-text" id="basic-addon1">
                                            <i class="fas fa-user-alt fa-fw"></i>
                                        </span>
                                        <input name="username" id="username" class="form-control" placeholder="{{ __('Username') }}" value="{{ old('username') }}" autofocus required>
                                    </div>
                                    @if ($errors->has('username'))
                                        <span class="text-danger">{{ $errors->first('username') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!--3rd row-->
                        <div style="width: 100%; display: table; margin-bottom:15px;">
                            <div style="display: table-row">
                                <div class="form-group mt-4 mb-4" style="width: 50%; padding-right:5px; display: table-cell;">
                                    <label for="phonenum">{{ __('Your Phone Number') }}</label>
                                    <div class="input-group">
                                        <span class="input-group-text" id="basic-addon1">
                                            <i class="fas fa-phone-alt fa-fw"></i>
                                        </span>
                                        <input name="phonenum" id="phonenum" class="form-control" placeholder="{{ __('Phone Number') }}" value="{{ old('phonenum') }}" autofocus required>
                                    </div>
                                    @if ($errors->has('phonenum'))
                                        <span class="text-danger">{{ $errors->first('phonenum') }}</span>
                                    @endif
                                </div>
                                <div class="form-group mt-4 mb-4" style="width: 50%; padding-right:5px; display: table-cell;">
                                    <label for="password">{{ __('Your Password') }}</label>
                                    <div class="input-group">
                                        <span class="input-group-text" id="basic-addon2">
                                            <svg class="icon icon-xs text-gray-600" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                                            </svg>
                                        </span>
                                        <input name="password" type="password" placeholder="{{ __('Password') }}" class="form-control" id="password" required autocomplete="new-password">
                                    </div>
                                    @if ($errors->has('password'))
                                        <span class="text-danger">{{ $errors->first('password') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!--4th row-->
                        <div style="width: 100%; display: table; margin-bottom:15px;">
                            <div style="display: table-row">
                                <div class="form-group mt-4 mb-4" style="width: 50%; padding-right:5px; display: table-cell;">
                                    <label for="company">{{ __('Your Company') }}</label>
                                    <div class="input-group">
                                        <span class="input-group-text" id="basic-addon1">
                                            <i class="fas fa-building fa-fw"></i>
                                        </span>
                                        <select name="company" id="company" class="form-select">
                                            <option value="" disabled selected>Select your company</option>
                                            @foreach($companies as $company)
                                                <option value="{{$company->id}}">{{$company->company_name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @if ($errors->has('company'))
                                        <span class="text-danger">{{ $errors->first('company') }}</span>
                                    @endif
                                </div>
                                <div class="form-group mt-4 mb-4" style="width: 50%; padding-right:5px; display: table-cell;">
                                    <label for="password_confirmation">{{ __('Confirm Password') }}</label>
                                    <div class="input-group">
                                        <span class="input-group-text" id="basic-addon3">
                                            <svg class="icon icon-xs text-gray-600" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                                            </svg>
                                        </span>
                                        <input name="password_confirmation" type="password" placeholder="{{ __('Confirm Password') }}" class="form-control" id="password_confirmation" required>
                                    </div>
                                    @if ($errors->has('password_confirmation'))
                                        <span class="text-danger">{{ $errors->first('password_confirmation') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- <div style="width: 100%; display: table; margin-bottom:15px;">
                            <div style="display: table-row">
                                <div class="form-group mt-4 mb-4" style="width: 50%; padding-right:5px; display: table-cell;">
                                    <label for="email">{{ __('Your Email') }}</label>
                                    <div class="input-group">
                                         <span class="input-group-text" id="basic-addon1">
                                            <svg class="icon icon-xs text-gray-600" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
                                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
                                            </svg>
                                        </span>
                                        <input name="email" id="email" type="email" class="form-control" placeholder="{{ __('Email') }}" value="{{ old('email') }}" autofocus required>
                                    </div>
                                    @if ($errors->has('email'))
                                        <span class="text-danger">{{ $errors->first('email') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div> --}}


                        <br>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-gray-800">{{ __('Register') }}</button>
                        </div>
                    </form>

                    <div class="d-flex justify-content-center align-items-center mt-4">
                        <span class="fw-normal">
                            {{ __('Already have an account?') }}
                            <a href="{{ route('login') }}" class="fw-bold">{{ __('Login here') }}</a>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
