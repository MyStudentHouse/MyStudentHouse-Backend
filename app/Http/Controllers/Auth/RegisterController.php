<?php

namespace App\Http\Controllers\Auth;

use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\User
     */
    protected function create(array $data)
    {
        /* Create new user */
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        /* Fetch latest container entry */
        $container = Containers::orderBy('date', 'desc')->first();

        /* Add container turn for the newly created user */
        $container_turn = new Containers;
        $container_turn->user_id = $user->id;
        if($container == NULL)
            $container_turn->date = today()->addWeeks(2);
        else
            $container_turn->date = Carbon::parse($container->date)->addWeeks(2);
        $container_turn->save();

        /* Initialize user with zero crates and zero beers */
        $beer = new Beer;
        $beer->user_id = $user->id;
        $beer->type = 'beer';
        $beer->value = 0;
        $beer->performed_by_user_id = $user->id;
        $beer->save();

        $crate = new Beer;
        $crate->user_id = $user->id;
        $crate->type = 'crate';
        $crate->value = 0;
        $crate->performed_by_user_id = $user->id;
        $crate->save();

        return $user;
    }
}
