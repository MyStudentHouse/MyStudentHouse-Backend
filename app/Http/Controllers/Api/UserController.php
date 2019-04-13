<?php

namespace App\Http\Controllers\API;

use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Validator;

class UserController extends Controller
{
    public $successStatus = 200;
    public $unauthorisedStatus = 401;

    /**
     * login api
     *
     * @return \Illuminate\Http\Response
     */
    public function login()
    {
        if(Auth::attempt(['email' => request('email'), 'password' => request('password')])){
            $user = Auth::user();
            $success['token'] = $user->createToken('MyStudentHouse')-> accessToken;
            return response()->json(['success' => $success], $this->successStatus);
        } else {
            return response()->json(['error' => 'Unauthorised'], $this->unauthorisedStatus);
        }
    }

    /**
     * logout api
     *
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        Auth::logout();
        return response()->json(['success' => 'You are successfully logged out'],  $this->successStatus);
    }

    /**
     * register api
     *
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required',
            'confirm_password' => 'required|same:password',
        ]);

        if ($validator->fails()) {
            return response()->json(['error'=>$validator->errors()], $this->unauthorisedStatus);
        }

        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);
        $success['token'] = $user->createToken('MyStudentHouse')->accessToken;
        $success['name'] = $user->name;

        return response()->json(['success'=>$success], $this->successStatus);
    }

    /**
     * details api
     *
     * @return \Illuminate\Http\Response
     */
    public function details()
    {
        $user = Auth::user();
        return response()->json(['success' => $user], $this->successStatus);
    }

    /**
     * updateDetails API
     *
     * @return \Illuminate\Htpp\Response
     */
    public function updateDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], $this->unauthorisedStatus);
        }

        $user = Auth::user();
        /* Validate user */
        if($user->id != $request->name('id')) {
            return response()->json(['error' => 'Unauthorised'], $this->unauthorisedStatus);
        }

        $user->image = $request->name('image');
        $user->email = $request->name('email');
        $user->phone = $request->name('phone'); /* TODO(PATBRO): write a validation rule to validate phone numbers */
        $user->iban = $request->name('iban'); /* TODO(PATBRO): write a validation rule to validate an IBAN */
        $user->save();

        return response()->json(['success' => $user], $this->successStatus);
    }
}
?>
