<?php
/** @file HouseController.php
 *
 *  The house controller holds the functionality to create new houses or assign users to a specific house.
 */

namespace App\Http\Controllers\API;

use Auth;
use App\House;
use App\UsersPerHouses;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;

class HouseController extends Controller
{
    public $successStatus = 200;
    public $invalidStatus = 412;
    public $unauthorisedStatus = 401;

    public function __construct()
    {
        /* TODO(PATBRO): make use of construct in combination with middleware auth, or resolve this via routes/api. */
        // $this->middleware('auth');
    }

    /**
     * @par API\HouseController@show (POST)
     * Fetch a house
     *
     * @param house_id  The house ID to fetch the data from.
     *
     * @retval JSON     Error 412
     * @retval JSON     Success 200
     */
    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'house_id' => 'required',
        ]);

        if($validator->fails() == true) {
            return response()->json(['error' => $validator->errors()], $this->invalidStatus);
        }

        $house = DB::table('houses')->where('id', $request->input('house_id'))->get();

        return response()->json(['success' => $house], $this->successStatus);
    }

    /**
     * @par API\HouseController@store (POST)
     * Create a new house with the given parameters.
     *
     * @param name          The name of the house to create (required).
     * @param description   The description of the house to create (required).
     *
     * @retval JSON     Error 412
     * @retval JSON     Success 200
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'description' => 'required',
        ]);

        if($validator->fails() == true) {
            return response()->json(['error' => $validator->errors()], $this->invalidStatus);
        }

        $input = $request->all();
        $house = new House;
        $house->name = $input['name'];
        $house->description = $input['description'];
        $house->created_by = Auth::id();
        $house->save();

        /* Add the user who created the house to the house */
        $users_per_houses = new UsersPerHouses;
        $users_per_houses->house_id = $house->id;
        $users_per_houses->user_id = 1;
        $users_per_houses->role = 1; /* Standard, highest role */
        $users_per_houses->save();

        return response()->json(['success' => $house], $this->successStatus);
    }

    /**
     * @par API\HouseController@assignUser (POST)
     * Assign a user to an already existing house.
     *
     * @param house_id  House ID to add the user to (required).
     * @param user_id   ID of the user to add to the corresponding house (required).
     * @param role      Role of the user to add (required, between 1 and 9).
     *
     * @retval JSON     Error 412
     * @retval JSON     Success 200
     */
    public function assignUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'house_id' => 'required|integer',
            'user_id' => 'required|integer',
            'role' => 'required|integer|between:1,9', /* Role must be between 1 and 3 */
        ]);

        if($validator->fails() == true) {
            return response()->json(['error' => $validator->errors()], $this->invalidStatus);
        }

        /* Verify if user is authorised to add other users to a student house */
        if(DB::table('users_per_houses')->where('user_id', Auth::id())->where('house_id', $input['house_id'])->exists() == true) {
            return response()->json(['error' => 'You are not permitted to add a user to this house'], $this->invalidStatus);
        }

        if(DB::table('users_per_houses')->where('user_id', $input['user_id'])->where('house_id', $input['house_id'])->exists() == true) {
            /* User already belongs to this house */
            return response()->json(['error' => 'User already belongs to this house'], $this->invalidStatus);
        } else {
            /* User does not yet belong to this house, so add the user to the house */
            $users_per_houses = new UsersPerHouse;
            $users_per_houses->house_id = $input['house_id'];
            $users_per_houses->user_id = $input['user_id'];
            $users_per_houses->role = $input['role'];
            $users_per_houses->save();
        }

        return response()->json(['success' => $users_per_houses], $this->successStatus);
    }

    /**
     * @par API\HouseController@deleteUser (POST)
     * Delete a user from a house.
     *
     * @param house_id  House ID to delete the user from (required).
     * @param user_id   ID of the user to delete from the corresponding house (required).
     *
     * @retval JSON     Error 412
     * @retval JSON     Success 200
     */
    public function deleteUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'house_id' => 'required|integer',
            'user_id' => 'required|integer',
        ]);

        if($validator->fails() == true) {
            return response()->json(['error' => $validator->errors()], $this->invalidStatus);
        }

        /* Check if the current user is part of this house */
        if(DB::table('users_per_houses')->where('user_id', Auth::id())->where('house_id', $input['house_id'])->exists() == false) {
            /* TODO(PATBRO): also check its user role, whether the current user is permitted to remove another user */
            return response()->json(['error' => 'You are not permitted to remove a user from this house'], $this->invalidStatus);
        }

        /* Check if the user to remove belongs to this house */
        if(DB::table('users_per_houses')->where('user_id', $input['user_id'])->where('house_id', $input['house_id'])->exists() == true) {
            /* Set deleted to true */
            /* TODO(PATBRO): first check whether beer and WBW balance is even */
            $users_per_houses = DB::table('users_per_houses')->where('user_id', $input['user_id'])->where('house_id', $input['house_id'])->get();
            $users_per_houses->deleted = true;
            $users_per_houses->save();
        } else {
            /* TODO(PATBRO): make error less descriptive due to security reasons, otherwise this could be brute forced */
            return response()->json(['error' => 'User does not belong to this house'], $this->invalidStatus);
        }

        return response()->json(['success' => $users_per_houses], $this->successStatus);
    }
}
