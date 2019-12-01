<?php
/** @file BeerController.php
 *
 *  The beer controller holds the functionality to check the current beer quota, subtract beers, add crates or return crates.
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

    public function initialize($user_id, $house_id)
    {
        /* Add a crate to the user ID */
        $crate = new Beer;
        $crate->user_id = $user_id;
        $crate->house_id = $house_id;
        $crate->type = 'crate';
        $crate->value = 0;
        $crate->performed_by_user_id = Auth::id();
        $crate->created_at = now();
        $crate->updated_at = now();
        $crate->save();

        /* Add the beers of the crate to the same user ID */
        $beer = new Beer;
        $beer->user_id = $user_id;
        $beer->house_id = $house_id;
        $beer->type = 'beer';
        $beer->value = 0;
        $beer->performed_by_user_id = Auth::id();
        $beer->created_at = now();
        $beer->updated_at = now();
        $beer->save();
    }

    /**
     * @par API\BeerController@show (GET)
     * Get all the beers from the database for a certain house.
     *
     * @param house_id  Which house to retrieve the beers for.
     *
     * @retval JSON     Error 401
     * @retval JSON     Success 200
     */
    public function show($house_id)
    {
        $beers = DB::table('beers')->where('house_id', $house_id)->leftJoin('users', 'users.id', '=', 'beers.user_id')->select('users.name', 'user_id', 'type', DB::raw('sum(value) as total'))->groupBy('user_id', 'type')->get();

        return response()->json(['success' => $beers], $this->successStatus);
    }

    /**
     * @par API\BeerController@store (POST)
     * Subtract a beer, add a crate or return a crate using this API call.
     * Mentioned parameters are values to post using a POST method.
     *
     * @param user_id   The user ID to perform the action to.
     * @param house_id  The house ID to perform the action to (of which the user also belongs to).
     * @param action    The action to perform: "substractBeer", "addCrate" or "returnCrate".
     * @param amount    The amount of beers/crates.
     *
     * @retval JSON     Error 401
     * @retval JSON     Error 403
     * @retval JSON     Success 200
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
            return response()->json(['error' => $validator->errors()], 401);
        }

        if(DB::table('users_per_houses')->where('user_id', Auth::id())->where('house_id', $request->input('house_id'))->exists() == false) {
            return response()->json(['error' => 'User is not authorised to perform any actions for this house'], 403);
        }

        /* TODO(PATBRO): consider splitting up these actions into separate API calls */
        switch($request->input('action')) {
    	case 'substractBeer':
            /* Value of substractBeer equals the user ID where to substract a beer */
            if($request->input('amount') < 1)
                return response()->json(['error' => 'Specified amount incorrect'], 403);

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
                return response()->json(['error' => 'Specified amount incorrect'], 403);

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
                return response()->json(['error' => 'Specified amount incorrect'], 403);

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
    		return response()->json(['error' => 'Action not permitted'], 401);
            break;
    	}

    	return response()->json(['success' => $beer], $this->successStatus);
        /* TODO(PATBRO): return something different than the last array saved to the database */
    }
}
