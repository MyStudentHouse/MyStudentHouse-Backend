<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\VerifiesEmails;
use Validator;

class UserController extends Controller
{
    use VerifiesEmails;

    public $successStatus = 200;
    public $errorStatus = 400;
    public $unauthorisedStatus = 401;

    /**
     * @par API\UserController@index (POST)
     * Retrieve the details of the currently logged in user
     *
     * @retval 200 JSON OK
     * @retval 412 JSON Error
     */
    public function index()
    {
        if(Auth::check()) {
            $user = Auth::user();
            Log::info("Obtaining details for user ID ". $user->id ." succeeded");
            return response()->json(['success' => $user], $this->successStatus);
        } else {
            Log::info("Obtaining details failed");
            return response()->json(['error' => ''], $this->errorStatus);
        }
    }

    /**
     * @par API\UserController@store (POST)
     * Registers a new user.
     *
     * @param name              Name of the new user
     * @param email             Email address of the new user
     * @param password          Password of the new user
     * @param confirm_password  Should be the same value as 'password'
     *
     * @retval 200 JSON OK
     * @retval 412 JSON Error
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required',
            'confirm_password' => 'required|same:password',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], $this->errorStatus);
        }

        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);

        $success['token'] = $user->createToken('MyStudentHouse')->accessToken;
        $success['name'] = $user->name;

        $user->sendEmailVerificationNotification();

        Log::info("Registration for user ID ". $user->id ." succeeded");
        return response()->json(['success' => $success], $this->successStatus);
    }

    /**
     * @par API\UserController@show (POST)
     * Retrieve details for the user of the given user ID
     *
     * @param id    ID of the user
     * @retval 200 JSON User array with all the details
     * @retval 412 JSON Given user ID is unknown
     */
    public function show($id)
    {
        $user = DB::table('users')->where('id', $id)->get();
        return $user[0];
    }

    /**
     * @par API\UserController@update (POST)
     * Update the details of the current user
     *
     * @param avatar    Profile image of the user (not required to be posted)
     * @param email     Email address of the user (not required to be posted)
     * @param phone     Phone number of the user (not required to be posted)
     * @param iban      IBAN of the user (not required to be posted)
     *
     * @retval 200 JSON Array of the user instance
     * @retval 412 JSON Validator, or saving query failed
     */
    public function update(Request $request)
    {
        if ($request->hasFile('avatar') && !$request->file('avatar')->isValid()) {
            return response()->json(['error' => 'Uploaded avatar not valid'], $this->errorStatus);   
        }

        $validator = Validator::make($request->all(), [
            // Only validate when posted
            'avatar' => 'sometimes|mimes:jpeg,png|max:10240', // 10240 kB = 10 MB
            'email' => 'sometimes|email|unique:users,email',
            'phone' => 'sometimes|digits:10',
            'iban' => 'sometimes|size:18',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], $this->errorStatus);
        }

        $user = Auth::user();
        $original_user = $user;
        if ($request->has('avatar')) {
            $user->image = Storage::putFile('avatars', $request->file('avatar'));
        }
        if ($request->has('email')) {
            $user->email = $request->input('email');
        }
        if ($request->has('phone')) {
            $user->phone = $request->input('phone');
        }
        if ($request->has('iban')) {
            $user->iban = $request->input('iban');
        }
        $user->save();

        // Send verification notification to new email address if the email address was updated
        /* TODO(PATBRO): if new email address is entered incorrectly, the email address cannot be 
         * changed back if this route requires a verified email address. Exclude from verfied route?
         */
        if ($request->has('email') && ($original_user->email != $user->email)) {
            $user->sendEmailVerificationNotification();
        }

        Log::info("Details for user ID ". $user->id ." were updated successfully");
        return response()->json(['success' => $user], $this->successStatus);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * @par API\UserController@login (POST)
     * Login to the application.
     *
     * @param email     Email address of the user
     * @param password  Password of the user
     *
     * @retval 200 JSON Token to identify the logged in user
     * @retval 412 JSON Unauthorised
     */
    public function login()
    {
        if(Auth::attempt(['email' => request('email'), 'password' => request('password')])) {
            $user = Auth::user();
            $success['token'] = $user->createToken('MyStudentHouse')->accessToken;
            Log::info("Login for user ID ". $user->id ." succeeded");
            return response()->json(['success' => $success], $this->successStatus);
        } else {
            Log::info("Login for email ". request('email') ." failed");
            return response()->json(['error' => 'Login failed. Please check your credentials.'], $this->errorStatus);
        }
    }

    /**
     * @par API\UserController@logout (POST)
     * Perform a POST (to prevent XSS) to this route to log the user out.
     *
     * @retval 200 JSON OK
     * @retval 412 JSON Error
     */
    public function logout(Request $request)
    {
        if(Auth::check()) {
            $user = Auth::user();
            $user->token()->revoke();
            Log::info("User ID ". $user->id ." log out successful");
            return response()->json(['success' => 'You are successfully logged out'],  $this->successStatus);
        } else {
            Log::info("Log out failed");
            return response()->json(['error' => 'Logout failed'],  $this->errorStatus);
        }
    }
}
