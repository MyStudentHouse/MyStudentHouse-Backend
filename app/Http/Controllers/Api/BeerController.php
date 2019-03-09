<?php
/** @file BeerController.php
 *  @brief Beer Controller
 *
 *  The beer controller holds the functionality to, check the user' current beer quota, subtract beers, add crates or return crates.
 */

namespace App\Http\Controllers\API;

use Auth;
use App\User;
use App\Beer;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;

class BeerController extends Controller
{
    public $successStatus = 200;

    public function __construct()
    {
        /* TODO(PATBRO): make use of construct in combination with middleware auth, or resolve this via routes/api. */
        // $this->middleware('auth');
    }

    public function show()
    {
        $beerquota = DB::table('beers')->join('users', 'beers.user_id', '=', 'users.id')->select('users.name', 'user_id', 'type', DB::raw('sum(value) as total'))->groupBy('user_id', 'type')->get();

        return response()->json(['success' => $beerquota], $this->successStatus);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'action' => 'required', /* TODO(PATBRO): consider to change this to an enum */
            'amount' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }

        $input = $request->all();

        if(DB::table('users_per_houses')->where('user_id', Auth::id())->where('house_id', $input['house_id'])->exists() == false) {
            return response()->json(['error' => 'User is not authorised to perform any actions for this house'], 403);
        }

        /* TODO(PATBRO): consider splitting up these actions into separate API calls */
        switch($input['action']) {
    	case 'substractBeer':
    		/* Value of substractBeer equals the user ID where to substract a beer */
    		if($input['amount'] < 1)
    			return response()->json(['error' => 'Specified amount incorrect'], 403);

    		/* Substract a beer from the given user ID */
    		$beer = new Beer;
    		$beer->user_id = $input['user_id'];
            $beer->house_id = $input['house_id'];
    		$beer->type = 'beer';
    		$beer->value = -1 * $input['amount'];
    		$beer->performed_by_user_id = Auth::id();
    		$beer->created_at = now();
    		$beer->updated_at = now();
    		$beer->save();
            break;

    	case 'addCrate':
    		/* Value of addCrate equals the user ID where to add a crate */
    		if($input['amount'] < 1)
    			return response()->json(['error' => 'Specified amount incorrect'], 403);

    		/* Add a crate to the user ID */
    		$crate = new Beer;
    		$crate->user_id = $input['user_id'];
            $beer->house_id = $input['house_id'];
    		$crate->type = 'crate';
    		$crate->value = $input['amount'];
    		$crate->performed_by_user_id = Auth::id();
    		$crate->created_at = now();
    		$crate->updated_at = now();
    		$crate->save();

    		/* Add the beers of the crate to the same user ID */
    		$beer = new Beer;
    		$beer->user_id = $input['user_id'];
            $beer->house_id = $input['house_id'];
    		$beer->type = 'beer';
    		$beer->value = $input['amount'] * 24; /* TODO(PATBRO): implement possibility to add half a crate as well */
    		$beer->performed_by_user_id = Auth::id();
    		$beer->created_at = now();
    		$beer->updated_at = now();
    		$beer->save();

    	case 'returnCrate':
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
            break;

    	default:
    		return response()->json(['error' => 'Action not permitted'], 401);
            break;
    	}

    	return response()->json(['success' => $beer], $this->successStatus);
        /* TODO(PATBRO): return something different than the last array saved to the database */
    }
}
