<?php

namespace App\Http\Controllers\API;

use App\Container;
use Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

/** \file ContainerController.php
 *  \brief Container Controller
 *
 *  Details.
 */
class ContainerController extends Controller
{
    public $successStatus = 200;

    public function __construct()
    {
        // $this->middleware('auth');
    }

    /** \fn show()
     *  \brief Get the containers turn for a certain user
     *
     *  Perform a GET request to /api/containers.
     *  This will return a array with all upcoming container turns for the user.
     *
     *  \return 200 Success
     */
    public function show()
    {
        $containers = DB::table('containers')->join('users', 'containers.user_id', '=', 'users.id')->select('users.name', 'user_id', 'date')->orderBy('date', 'asc')->get();

        $nextTurn = 'unknown';
        foreach($containers as $container) {
            if($container->user_id == Auth::User()->id)
                $nextTurn = $container->date;
        }

        return response()->json(['success' => $containers], $this->successStatus);
    }

    /** \brief Update the container turns
     *
     * Calling this function will update the container turns by getting the last
     * from the database. Looping through all users.
     */
    public function updateContainerTurns()
    {
    	/* Prepare next container turn entry for database */
    	$next_container_turn = new Containers;
    	/* Fetch latest container entry */
        $container = Containers::orderBy('date', 'desc')->first();

    	$users = Users::All();
    	$i = 0;
        /* Determine next container turn by looping through all users (TODO(PATBRO): users of student house) */
    	foreach($users as $user) {
    		if($user->id == $container->user_id) {
    			$next_container_turn->user_id = $users[$i+1];
    			$next_container_turn->date = today()->addWeeks(2); /* TODO(PATBRO): make the addWeeks(2) easily adjustable */
    			break;
    		}

    		$i++;
    	}

    	if($next_container_turn->user_id == NULL)
    		abort(404, 'Updating container turns failed');

    	$next_container_turn->save();
    }
}
