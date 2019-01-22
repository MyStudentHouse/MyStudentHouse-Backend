<?php

namespace App\Http\Controllers;

use App\Beer;
use Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class BeerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function show()
    {
    	$beerquote = DB::table('beers')->join('users', 'beers.user_id', '=', 'users.id')->select('users.name', 'user_id', 'type', DB::raw('sum(value) as total'))->groupBy('user_id', 'type')->get();

    	return view('beer', compact('beerquote'));
    }

    public function store(Request $request)
    {
    	if(isset($request->substractBeer)) {
    		/* Value of substractBeer equals the user ID where to substract a beer */
    		if($request->beerAmount < 1)
    			abort(403, "This value is not permitted.");

    		/* Substract a beer from the given user ID */
    		$beer = new Beer;
    		$beer->user_id = $request->substractBeer;
    		$beer->type = 'beer';
    		$beer->value = -1 * $request->beerAmount;
    		$beer->performed_by_user_id = Auth::User()->id;
    		$beer->created_at = now();
    		$beer->updated_at = now();
    		$beer->save();
    	} elseif(isset($request->addCrate)) {
    		/* Value of addCrate equals the user ID where to add a crate */
    		if($request->crateAmount < 1)
    			abort(403, "This value is not permitted.");

    		/* Add a crate to the user ID */
    		$crate = new Beer;
    		$crate->user_id = $request->addCrate;
    		$crate->type = 'crate';
    		$crate->value = $request->crateAmount;
    		$crate->performed_by_user_id = Auth::User()->id;
    		$crate->created_at = now();
    		$crate->updated_at = now();
    		$crate->save();

    		/* Add the beers of the crate to the same user ID */
    		$beer = new Beer;
    		$beer->user_id = $request->addCrate;
    		$beer->type = 'beer';
    		$beer->value = $request->crateAmount * 24;
    		$beer->performed_by_user_id = Auth::User()->id;
    		$beer->created_at = now();
    		$beer->updated_at = now();
    		$beer->save();
    	} elseif(isset($request->returnCrate)) {
    		/* Value of returnCrate equals the user ID of the logged in user */
    		if($request->returnCrate != Auth::User()->id)
    			back();

    		if($request->returnCrate < 1)
    			abort(403, "This value is not permitted.");

    		$beer = new Beer;
    		$beer->user_id = NULL;
    		$beer->type = 'crate';
    		$beer->value = -1 * $request->returnCrate;
    		$beer->performed_by_user_id = Auth::User()->id;
    		$beer->created_at = now();
    		$beer->updated_at = now();
    		$beer->save();
    	} else {
    		dd('Something went terribly wrong.');
    	}

    	return back();
    }
}
