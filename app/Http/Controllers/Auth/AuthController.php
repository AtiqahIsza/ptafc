<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Facade\FlareClient\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Write code on Method
     *
     * @return response|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        return view('auth.login');
    }

    /**
     * Write code on Method
     *
     * @return response|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function registration()
    {
        return view('auth.register');
    }

    /**
     * Write code on Method
     *
     * @return response()
     */
    public function postLogin(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $credentials = $request->only('username', 'password');
        if (Auth::attempt($credentials)) {
            return redirect()->intended('home')
                ->withSuccess('You Have Successfully Logged In');
        }

        return redirect("login")->withSuccess('You have entered invalid credentials');
    }

    /**
     * Write code on Method
     *
     * @return response()
     */
    public function postRegistration(Request $request)
    {
        $request->validate([
            'fullname' => ['required', 'string', 'max:255'],
            //'ic_number' => ['required', 'string', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone_number' => ['required', 'string', 'max:255'],
            'user_role' => ['required', 'int'],
            'username' => ['required', 'string', 'max:255', 'unique:users'],
            'company_id' => ['int'],
        ]);

        $data = $request->all();
        $check = $this->create($data);

        return redirect("home")->withSuccess('Great! You Have Successfully Made A New Account');
    }

    /**
     * Write code on Method
     *
     * @return response|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    /*public function dashboard()
    {
        if(Auth::check()){
            return view('home');
        }

        return redirect("login")->withSuccess('Opps! You do not have access');
    }*/

    /**
     * Write code on Method
     *
     * @return response()
     */
    public function create(array $data)
    {
        return User::create([
            'fullname' => $data['fullname'],
            //'ic_number' => $data['is_number'],
            'phone_number' => $data['phone_number'],
            'user_role' => $data['user_role'],
            'username' => $data['username'],
            'company_id' => $data['company_id'],
            'password' => Hash::make($data['password']),
        ]);
    }

    /**
     * Write code on Method
     *
     * @return response|\Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function logout() {
        Session::flush();
        Auth::logout();

        return Redirect('login');
    }
}
