<?php

namespace App\Http\Controllers;

use App\Container;
use Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ContainerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function updateContainerTurns()
    {
    	/* Prepare next container turn entry for database */
    	$next_container_turn = new Containers;
    	/* Fetch latest container entry */
        $container = Containers::orderBy('date', 'desc')->first();

    	$users = Users::All();
    	$i = 0;
    	foreach($users as $user) {
    		if($user->id == $container->user_id) {
    			$next_container_turn->user_id = $users[$i+1];
    			$next_container_turn->date = today()->addWeeks(2);
    			break;
    		}

    		$i++;
    	}

    	if($next_container_turn->user_id == NULL)
    		abort(404, 'Updating container turns failed');

    	$next_container_turn->save();
    }

    public function show()
    {
	$containers = DB::table('containers')->join('users', 'containers.user_id', '=', 'users.id')->select('users.name', 'user_id', 'date')->orderBy('date', 'asc')->get();

	$nextTurn = 'unknown';
	foreach($containers as $container) {
		if($container->user_id == Auth::User()->id)
			$nextTurn = $container->date;
	}

    	return view('containers', compact('containers', 'nextTurn'));
    }
}
