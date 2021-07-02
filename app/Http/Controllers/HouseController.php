<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mail;
use Validator;

class HouseController extends Controller
{
    use SoftDeletes;

    public $successStatus = 200;
    public $errorStatus = 400;
    public $unauthorisedStatus = 401;

    /**
     * @par API\HouseController@index (GET)
     * Return all student houses
     *
     * @retval 200 JSON Array of all student houses
     */
    public function index()
    {
        $houses = DB::table('houses')->get();

        return response()->json(['success' => $houses], $this->successStatus);
    }

    /**
     * @par API\HouseController@store (POST)
     * Create a new house with the given parameters.
     *
     * @param name          The name of the house to create (required).
     * @param description   The description of the house to create (required).
     * @param avatar        Avatar of the student house (optional).
     *
     * @retval 200 JSON OK
     * @retval 412 JSON Validator, or save query failed
     */
    public function store(Request $request)
    {
        if ($request->hasFile('avatar') && !$request->file('avatar')->isValid()) {
            return response()->json(['error' => 'Uploaded avatar not valid'], $this->errorStatus);   
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'description' => 'required',
            'avatar' => 'sometimes|mimes:jpeg,png|max:1024', // Only validate when posted
        ]);

        if($validator->fails() == true) {
            return response()->json(['error' => $validator->errors()], $this->errorStatus);
        }

        $house = new House;
        $house->name = $request->input('name');
        $house->description = $request->input('description');
        if ($request->has('avatar')) {
            $house->image = Storage::putFile('avatars', $request->file('avatar'));
        }
        $house->created_by = Auth::id();
        $house->updated_by = Auth::id();
        $house->save();

        /* Add the user who created the house to the house */
        $users_per_houses = new UsersPerHouses;
        $users_per_houses->house_id = $house->id;
        $users_per_houses->user_id = Auth::id();
        $users_per_houses->role = 1; /* Standard, highest role */
        $users_per_houses->save();

        /* TODO: make use of initialize function of BeerController instead */
        /* Add a crate to the user ID */
        $crate = new Beer;
        $crate->user_id = Auth::id();
        $crate->house_id = $house->id;
        $crate->type = 'crate';
        $crate->value = 0;
        $crate->performed_by_user_id = Auth::id();
        $crate->created_at = now();
        $crate->updated_at = now();
        $crate->save();

        /* Add the beers of the crate to the same user ID */
        $beer = new Beer;
        $beer->user_id = Auth::id();
        $beer->house_id = $house->id;
        $beer->type = 'beer';
        $beer->value = 0;
        $beer->performed_by_user_id = Auth::id();
        $beer->created_at = now();
        $beer->updated_at = now();
        $beer->save();

        return response()->json(['success' => $house], $this->successStatus);
    }

    /**
     * @par API\HouseController@show (GET)
     * Fetch a house
     *
     * @param   id  The house ID to fetch the data for
     * @retval  200 JSON OK
     * @retval  412 JSON House ID is non-existent
     */
    public function show($id)
    {
        $house = DB::table('houses')->where('id', $id)->get();

        return response()->json(['success' => $house], $this->successStatus);
    }

    /**
     * @par API\HouseController@update (POST)
     * Update the details of a house
     *
     * @param house_id      House ID to update the details for (required)
     * @param name          Name of the house (optional)
     * @param description   Description of the house (optional)
     * @param avatar        Avatar of the house (not required to be posted)
     *
     * @retval 200 JSON OK
     * @retval 412 JSON Validator, or save query failed
     */
    public function update(Request $request)
    {
        if ($request->hasFile('avatar') && !$request->file('avatar')->isValid()) {
            return response()->json(['error' => 'Uploaded avatar not valid'], $this->errorStatus);   
        }

        $validator = Validator::make($request->all(), [
            'house_id' => 'required|integer|unique:houses,id',
            'name' => 'sometimes|min:4|max:56',
            'description' => 'max:280',
            'avatar' => 'sometimes|mimes:jpeg,png|max:1024', // Only validate when posted
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], $this->errorStatus);
        }

        if($this->userBelongsToHouse($request->input('house_id'), Auth::id()) == false) {
            return response()->json(['error' => 'You do not belong to this house'], $this->errorStatus);
        }

        // Fetch house
        $house = DB::table('houses')->where('id', $request->input('house_id'))->get();
        // Update house details
        $house->name = $request->input('name');
        $house->description = $request->input('description');
        if ($request->has('avatar')) {
            $house->image = Storage::putFile('avatars', $request->file('avatar'));
        }
        $house->updated_by = Auth::id();
        $house->save();

        return response()->json(['success' => $house], $this->successStatus);
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
     * @par API\HouseController@userBelongsTo (GET)
     * Obtain to which house(s) the current user belongs.
     *
     * @retval 200 JSON Array of all houses the user belongs to
     */
    public function userBelongsTo()
    {
        $houses = DB::table('users_per_houses')->where('user_id', Auth::id())->where('deleted', false)->get();

        return response()->json(['success' => $houses], $this->successStatus);
    }

    /**
     * @par API\HouseController@inviteNonUser (POST)
     * Invite a non-user to join an already existing house.
     * 
     * @param house_id          House ID to add the non-user to (required).
     * @param non_user_email    Email address of the non-user to invite (required). Based on user input.
     * @param role              Role of the non-user to add (required, between 1 and 9).
     * 
     * @retval 200 JSON OK
     * @retval 412 JSON Error
     */
    public function inviteNonUser(Request $request)
    {
        $result = $this->AddUserVerification($request);
        if ($result != true) {
            return $result;
        }

        $user_name = DB::table('users')->where('id', Auth::id())->value('name');
        $nonUserAddedInfo = new UserAdded;
        $nonUserAddedInfo->email = $user[0]->email;
        $nonUserAddedInfo->houseName = $house_name;
        $userAddedInfo->nameAdded = $user_name;

        Mail::to($user)->send(new InviteNonUserToHouse($nonUserAddedInfo));

        return response()->json(['success' => 'The invite was successfully sent!'], $this->successStatus);
    }

    /**
     * @par API\HouseController@assignUser (POST)
     * Assign a user to an already existing house.
     *
     * @param house_id      House ID to add the user to (required).
     * @param user_email    Email address of the user to add to the corresponding house (required). Based on user input.
     * @param role          Role of the user to add (required, between 1 and 9).
     *
     * @retval 200 JSON OK
     * @retval 412 JSON Error
     */
    public function assignUser(Request $request)
    {
        $result = $this->AddUserVerification($request);
        if ($result != true) {
            return $result;
        }

        if(DB::table('users')->where('email', $request->input('user_email'))->exists() == false) {
            /* Check if user with this email address exists */
            return response()->json(['error' => 'User not found'], $this->errorStatus);
        }

        $user_id = DB::table('users')->where('email', $request->input('user_email'))->value('id');

        if($this->userBelongsToHouse($request->input('house_id'), $user_id) == true) {
            /* User already belongs to this house */
            return response()->json(['error' => 'User already belongs to this house'], $this->errorStatus);
        }

        /* User does not yet belong to this house, so add the user to the house */
        $users_per_houses = new UsersPerHouses;
        $users_per_houses->house_id = $request->input('house_id');
        $users_per_houses->user_id = $user_id;
        $users_per_houses->role = $request->input('role');
        $users_per_houses->save();

        /* TODO: make use of initialize function of BeerController instead */
        /* Add a crate to the user ID */
        $crate = new Beer;
        $crate->user_id = $user_id;
        $crate->house_id = $request->input('house_id');
        $crate->type = 'crate';
        $crate->value = 0;
        $crate->performed_by_user_id = Auth::id();
        $crate->created_at = now();
        $crate->updated_at = now();
        $crate->save();

        /* Add the beers of the crate to the same user ID */
        $beer = new Beer;
        $beer->user_id = $user_id;
        $beer->house_id = $request->input('house_id');
        $beer->type = 'beer';
        $beer->value = 0;
        $beer->performed_by_user_id = Auth::id();
        $beer->created_at = now();
        $beer->updated_at = now();
        $beer->save();

        // Prepare information for email to send to assigned user
        $user = DB::table('users')->where('email', $request->input('user_email'))->get();

        $house_name = DB::table('houses')->where('id', $request->input('house_id'))->value('name');
        $user_name = DB::table('users')->where('id', Auth::id())->value('name');
        $userAddedInfo = new UserAdded;
        $userAddedInfo->name = $user[0]->name;
        $userAddedInfo->email = $user[0]->email;
        $userAddedInfo->houseName = $house_name;
        $userAddedInfo->nameAdded = $user_name;

        Mail::to($user)->send(new UserAddedToHouse($userAddedInfo));

        return response()->json(['success' => $users_per_houses], $this->successStatus);
    }

    /**
     * @par API\HouseController@showUsers (GET)
     * Fetch all users belonging to a house
     *
     * @param house_id  The house ID to fetch the users for.
     *
     * @retval 200 JSON Array of all users belonging to the given house
     * @retval 412 JSON Error
     */
    public function showUsers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'house_id' => 'required|integer',
        ]);

        if($validator->fails() == true) {
            return response()->json(['error' => $validator->errors()], $this->errorStatus);
        }

        if($this->userBelongsToHouse($request->input('house_id'), Auth::id()) == false) {
            return response()->json(['error' => 'User does not belong to this house'], $this->errorStatus);
        }

        $users_per_houses = DB::table('users_per_houses')->where('house_id', $request->input('house_id'))->get();

        /* TODO(PATBRO): change to usage of Eloquent union instead of foreaching through array */
        $users = [];
        foreach ($user_per_houses as $user_per_house) {
            /* Append user details to users array which will be returned in the end */
            $user = DB::table('users')->where('user_id', $users_per_house['user_id'])->get();
            array_push($users, $user);
        }

        return response()->json(['success' => $users], $this->successStatus);
    }

    /**
     * @par API\HouseController@removeUser (POST)
     * Remove a user from a house.
     *
     * @param house_id  House ID to remove the user from (required).
     * @param user_id   ID of the user to remove from the corresponding house (required).
     *
     * @retval 200 JSON Array of houses the user still belongs to
     * @retval 412 JSON Error
     */
    public function removeUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'house_id' => 'required|integer',
            'user_id' => 'required|integer',
        ]);

        if($validator->fails() == true) {
            return response()->json(['error' => $validator->errors()], $this->errorStatus);
        }

        /* Check if the current user is part of this house */
        if(DB::table('users_per_houses')->where('user_id', Auth::id())->where('house_id', $request->input('house_id'))->exists() == false) {
            /* TODO(PATBRO): also check its user role, whether the current user is permitted to remove another user */
            return response()->json(['error' => 'You are not permitted to remove a user from this house'], $this->errorStatus);
        }

        /* Check if the user to remove belongs to this house */
        if($this->userBelongsToHouse($request->input('house_id'), $request->input('user_id')) == true) {
            /* Set deleted to true */
            /* TODO(PATBRO): first check whether beer and WBW balance is even */
            $users_per_houses = DB::table('users_per_houses')->where('user_id', $request->input('user_id'))->where('house_id', $request->input('house_id'))->update(['deleted' => true]);
        } else {
            return response()->json(['error' => 'User does not belong to this house'], $this->errorStatus);
        }

        return response()->json(['success' => $users_per_houses], $this->successStatus);
    }

    /**
     * @par HouseController@userBelongsToHouse
     * Only for internal usage, not exposed via the API
     *
     * @param house_id  ID of the house
     * @param user_id   ID of the user
     *
     * @retval True  BOOL Given user ID belongs to given house ID
     * @retval False BOOL Given user ID belongs not to given house ID
     */
    public function userBelongsToHouse($house_id, $user_id)
    {
        if(DB::table('users_per_houses')->where('user_id', $user_id)->where('house_id', $house_id)->where('deleted', false)->exists() == true) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @par HouseController@AddUserVerification
     * Verifies if user already belongs to the house given in the request
     * 
     * @param house_id  House ID given to verify if the user belongs to this house
     * @retval True  BOOL User belongs to given house ID
     * @retval False BOOL User does not belong to given house ID
     */
    private function AddUserVerification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'house_id' => 'required|integer',
            'user_email' => 'required|email',
            'role' => 'required|integer|between:1,9', /* Role must be between 1 and 9 */
        ]);

        if($validator->fails() == true) {
            return response()->json(['error' => $validator->errors()], $this->errorStatus);
        }

        /* Verify if user is authorised to add other users to a student house */
        if(DB::table('users_per_houses')->where('user_id', Auth::id())->where('house_id', $request->input('house_id'))->exists() == false) {
            return response()->json(['error' => 'You are not permitted to add a user to this house'], $this->errorStatus);
        }
        
        return true;
    }
}
