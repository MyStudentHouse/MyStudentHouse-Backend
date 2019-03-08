<?php

namespace App\Http\Controllers\API;

use Auth;
use App\User;
use App\Container;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;

class HouseController extends Controller
{
    public $successStatus = 200;
    public $invalidStatus = 412;
    public $unauthorisedStatus = 401;

    /* Create a new house */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ]);

        if($validator->fails()) {
            return response()->json(['error' => $validator->errors()], $this->invalidStatus);
        }

        $input = $request->all();
        $house = new House;
        $house->name = $input['name'];
        $house->description = $input['description'];
        $house->created_by = Auth::id();
        $house->save();

        /* Add the user who created the house to the house */
        $users_per_house = new UsersPerHouse;
        $users_per_house->house_id = $house->id;
        $users_per_house->user_id = Auth::id();
        $users_per_house->role = 1; /* Standard, highest role */
        $users_per_house->save();

        return response()->json(['success' => $house], $this->successStatus);
    }

    /* Assign a user to an already existing house */
    public function assign_user(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'house_id' => 'required|integer',
            'user_id' => 'required|integer',
            'role' => 'required|integer|between:1,9', /* Role must be between 1 and 3 */
        ]);

        if($validator->fails()) {
            return response()->json(['error' => $validator->errors()], $this->invalidStatus);
        }

        /* Verify if user is authorised to add other users to a student house */
        $user_id = Auth::id();
        $boolean = DB:table('users_per_house')->where('user_id', $user_id)->exists();
        if($boolean == true) {
            return response()->json(['error' => 'You are not permitted to add a user to this house'], $this->invalidStatus);
        }

        if(DB:table('users_per_house')->where('user_id', $input['user_id']))->where('house_id', $input['house_id'])->exists() == true) {
            /* User already belongs to this house */
            return response()->json(['error' => 'User already belongs to this house'], $this->invalidStatus);
        } else {
            /* User does not yet belong to this house, so add the user to the house */
            $users_per_house = new UsersPerHouse;
            $users_per_house->house_id = $input['house_id'];
            $users_per_house->user_id = $input['user_id'];
            $users_per_house->role = $input['role'];
            $users_per_house->save();
        }

        return response()->json(['success' => $users_per_house], $this->successStatus);
    }

    public function remove_user(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'house_id' => 'required|integer',
            'user_id' => 'required|integer',
        ]);

        if($validator->fails()) {
            return response()->json(['error' => $validator->errors()], $this->invalidStatus);
        }

        /* Check if the current user is part of this house */
        $user_id = Auth::id();
        if(DB:table('users_per_house')->where('user_id', $user_id)->where('house_id', $input['house_id'])->exists() == false) {
            /* TODO(PATBRO): also check its user role, whether the current user is permitted to remove another user */
            return response()->json(['error' => 'You are not permitted to remove a user from this house'], $this->invalidStatus);
        }

        /* Check if the user to remove belongs to this house */
        if(DB:table('users_per_house')->where('user_id', $input['user_id']))->where('house_id', $input['house_id'])->exists() == true) {
            /* Set deleted to true */
            $users_per_house = DB:table('users_per_house')->where('user_id', $input['user_id']))->where('house_id', $input['house_id'])->get();
            $users_per_house->deleted = true;
            $users_per_house->save();
        } else {
            /* TODO(PATBRO): make error less descriptive due to security reasons */
            return response()->json(['error' => 'User does not belong to this house'], $this->invalidStatus);
        }

        return response()->json(['success' => $users_per_house], $this->successStatus);
    }
}
