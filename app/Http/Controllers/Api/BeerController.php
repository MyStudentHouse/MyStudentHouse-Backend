<?php

namespace App\Http\Controllers\API;

use App\User;
use App\Beer;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Validator;

class BeerController extends Controller
{
    public $successStatus = 200;

    public function show()
    {
        if(!Auth::check()) {
            return response()->json(['message' => 'Unauthorised'], 403);
        }

    	$beerquote = DB::table('beers')->join('users', 'beers.user_id', '=', 'users.id')->select('users.name', 'user_id', 'type', DB::raw('sum(value) as total'))->groupBy('user_id', 'type')->get();

        return response()->json(['success' => $beerquote], $this->successStatus);
    }


    public function store(Request $request)
    {
        if(!Auth::check()) {
            return response()->json(['message' => 'Unauthorised'], 403);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'action' => 'required', /* TODO(PATBRO): consider to change this to an enum */
            'amount' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }

        $input = $request->all();
    	if($input['action'] == 'substractBeer') {
    		/* Value of substractBeer equals the user ID where to substract a beer */
    		if($input['amount'] < 1)
    			return response()->json(['error' => 'Specified amount incorrect'], 403);

    		/* Substract a beer from the given user ID */
    		$beer = new Beer;
    		$beer->user_id = $input['user_id']; /* TODO(PATBRO): check if user belongs to student house */
    		$beer->type = 'beer';
    		$beer->value = -1 * $input['amount'];
    		$beer->performed_by_user_id = Auth::id();
    		$beer->created_at = now();
    		$beer->updated_at = now();
    		$beer->save();
    	} elseif($input['action'] == 'addCrate') {
    		/* Value of addCrate equals the user ID where to add a crate */
    		if($input['amount'] < 1)
    			return response()->json(['error' => 'Specified amount incorrect'], 403);

    		/* Add a crate to the user ID */
    		$crate = new Beer;
    		$crate->user_id = $input['user_id']; /* TODO(PATBRO): check if user belongs to student house */
    		$crate->type = 'crate';
    		$crate->value = $input['amount'];
    		$crate->performed_by_user_id = Auth::id();
    		$crate->created_at = now();
    		$crate->updated_at = now();
    		$crate->save();

    		/* Add the beers of the crate to the same user ID */
    		$beer = new Beer;
    		$beer->user_id = $input['user_id']; /* TODO(PATBRO): check if user belongs to student house */
    		$beer->type = 'beer';
    		$beer->value = $input['amount'] * 24; /* TODO(PATBRO): implement possibility to add half a crate as well */
    		$beer->performed_by_user_id = Auth::id();
    		$beer->created_at = now();
    		$beer->updated_at = now();
    		$beer->save();
    	} elseif($input['action'] == 'returnCrate') {
    		/* Value of returnCrate equals the user ID of the logged in user */
    		if($input['action'] != Auth::id())
    			return response()->json(['error' => 'User action not permitted'], 403);

    		if($input['amount'] < 1)
                return response()->json(['error' => 'Specified amount incorrect'], 403);

    		$beer = new Beer;
    		$beer->user_id = NULL;
    		$beer->type = 'crate';
    		$beer->value = -1 * $input['amount']; /* TODO(PATBRO): implement possibility to return (single) bottles as well */
    		$beer->performed_by_user_id = Auth::id();
    		$beer->created_at = now();
    		$beer->updated_at = now();
    		$beer->save();
    	} else {
    		return response()->json(['error' => 'Action not permitted'], 401);
    	}

    	return response()->json(['success' => $beer], $this->successStatus);
        /* TODO(PATBRO): return something different than the last array saved to the database */
    }
}
