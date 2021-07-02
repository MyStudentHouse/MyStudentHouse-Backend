<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;

class InventoryController extends Controller
{
    public $successStatus = 200;
    public $errorStatus = 400;
    public $unauthorisedStatus = 401;

    /**
     * @par API\InventoryController@store (POST)
     * Subtract a beer, add a crate or return a crate using this API call.
     *
     * @param user_id   The user ID to perform the action to.
     * @param house_id  The house ID to perform the action to (of which the user also belongs to).
     * @param action    The action to perform: "substractBeer", "addCrate" or "returnCrate".
     * @param amount    The amount of beers/crates.
     * @retval 200 JSON OK
     * @retval 400 JSON Error
     * @retval 401 JSON Error
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'house_id' => 'required',
            'action' => 'required', /* TODO(PATBRO): consider to change this to an enum */
            'amount' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], $this->unauthorisedStatus);
        }

        if(DB::table('users_per_houses')->where('user_id', Auth::id())->where('house_id', $request->input('house_id'))->exists() == false) {
            return response()->json(['error' => 'User is not authorised to perform any actions for this house'], $this->errorStatus);
        }

        /* TODO(PATBRO): consider splitting up these actions into separate API calls */
        switch($request->input('action')) {
    	case 'substractBeer':
            /* Value of substractBeer equals the user ID where to substract a beer */
            if($request->input('amount') < 1)
                return response()->json(['error' => 'Specified amount incorrect'], $this->errorStatus);

            /* Substract a beer from the given user ID */
            $beer = new Beer;
            $beer->user_id = $request->input('user_id');
            $beer->house_id = $request->input('house_id');
            $beer->type = 'beer';
            $beer->value = -1 * $request->input('amount');
            $beer->performed_by_user_id = Auth::id();
            $beer->created_at = now();
            $beer->updated_at = now();
            $beer->save();
            break;

    	case 'addCrate':
            /* Value of addCrate equals the user ID where to add a crate */
            if($request->input('amount') < 1)
                return response()->json(['error' => 'Specified amount incorrect'], $this->errorStatus);

            /* Add a crate to the user ID */
            $crate = new Beer;
            $crate->user_id = $request->input('user_id');
            $crate->house_id = $request->input('house_id');
            $crate->type = 'crate';
            $crate->value = $request->input('amount');
            $crate->performed_by_user_id = Auth::id();
            $crate->created_at = now();
            $crate->updated_at = now();
            $crate->save();

            /* Add the beers of the crate to the same user ID */
            $beer = new Beer;
            $beer->user_id = $request->input('user_id');
            $beer->house_id = $request->input('house_id');
            $beer->type = 'beer';
            $beer->value = $request->input('amount') * 24; /* TODO(PATBRO): implement possibility to add half a crate as well */
            $beer->performed_by_user_id = Auth::id();
            $beer->created_at = now();
            $beer->updated_at = now();
            $beer->save();
            break;

    	case 'returnCrate':
            if($request->input('amount') < 1)
                return response()->json(['error' => 'Specified amount incorrect'], $this->errorStatus);

            $beer = new Beer;
            $beer->user_id = $request->input('user_id');
            $beer->house_id = $request->input('house_id');
            $beer->type = 'crate';
            $beer->value = -1 * $request->input('amount'); /* TODO(PATBRO): implement possibility to return (single) bottles as well */
            $beer->performed_by_user_id = Auth::id();
            $beer->created_at = now();
            $beer->updated_at = now();
            $beer->save();
            break;

    	default:
    		return response()->json(['error' => 'Action not permitted'], $this->errorStatus);
            break;
    	}

    	return response()->json(['success' => $beer], $this->successStatus);
        /* TODO(PATBRO): return something different than the last array saved to the database */
    }

    /**
     * @par API\InventoryController@show (GET)
     * Get all the inventory from the database for a certain house.
     *
     * @param house_id  Which house to retrieve the inventory for.
     * @retval 200 JSON Array of inventory information
     * @retval 412 JSON Error
     */
    public function show($house_id)
    {
        $beers = DB::table('inventory')->where('house_id', $house_id)->leftJoin('users', 'users.id', '=', 'inventory.user_id')->select('users.name', 'user_id', 'type', DB::raw('sum(value) as total'))->groupBy('user_id', 'type')->get();

        return response()->json(['success' => $beers], $this->successStatus);
    }
}
