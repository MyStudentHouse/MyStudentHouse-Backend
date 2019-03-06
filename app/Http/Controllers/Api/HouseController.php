<?php

namespace App\Http\Controllers\API;

use Auth;
use App\Container;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HouseController extends Controller
{
    public $successStatus = 200;
    public $invalidStatus = 412;
    public $unauthorisedStatus = 401;

    public function create()
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ]);

        if($validator->fails()) {
            return response()->json(['error' => $validator->errors()], $this->invalidStatus);
        }

        $input = $request->all();
        $house = House::create($input);

        return response()->json(['success' => $house], $this->successStatus);
    }

    public function assign_user($house_id)
    {
        /* Verify if user is authorised to add other users to a student house */
        $user = Auth::user();
        $ = DB::table('user_specific')->where('user_id', $user->id)->get();


    }
}
