<?php
/** @file ContainerController.php
 *
 *  The container controller holds the functionality to retrieve or update the container turns.
 */

namespace App\Http\Controllers\API;

use Auth;
use App\Container;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Validator;

class ContainerController extends Controller
{
    public $successStatus = 200;

    public function __construct()
    {
        // $this->middleware('auth');
    }

     /**
      * @par API\ContainerController@show (POST)
      * Gets the containers turns for the desired user.
      *
      * @param user_id   The user ID to fetch the desired data for.
      *
      * @retval JSON     Error 401
      * @retval JSON     Success 200
      */
    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }

        $containers = DB::table('containers')->join('users', 'containers.user_id', '=', $request->input('user_id'))->select('name', 'user_id', 'date')->orderBy('date', 'asc')->get();
        $nextTurn = 'unknown';
        foreach($containers as $container) {
            if($container->user_id == $request->input('user_id'))
                $nextTurn = $container->date;
        }

        return response()->json(['success' => $containers], $this->successStatus);
    }

    /**
     * @par API\ContainerController@updateContainerTurns (POST)
     * Iterates through all users of a house and updates the turns.
     *
     * @param house_id   The house ID to update the data for.
     *
     * @retval JSON     Error 401
     * @retval JSON     Success 200
     */
    public function updateContainerTurns(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'house_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }

    	/* Prepare next container turn entry for database */
    	$next_container_turn = new Containers;
    	/* Fetch latest container entry */
        $container = Containers::where('house_id', $request->input('house_id'))->orderBy('date', 'desc')->first();
        /* Retrieve which users belong to this house */
    	$users = DB::table('users_per_houses')->where('house_id', $request->input('house_id'))->orderBy('created_at', 'desc')->get();
        $i = 0;
        $last_turn = $container->date;
        /* Determine next container turn by looping through all users */
    	foreach($users as $user) {
            if(i > count($users)) {
                i = 0;
            }

            /* TODO(PATBRO): also consider performed_by_user_id when generating new container turns */
			$next_container_turn->user_id = $users[$i+1]->id;
			$next_container_turn->date = $last_turn->addWeeks(2); /* TODO(PATBRO): make the addWeeks(2) easily adjustable */
            $next_container_turn->save();

            $last_turn = $next_container_turn->date;
    		$i++;
    	}

        /* Return the latest next container turn */
        return response()->json(['success' => $next_container_turn], $this->successStatus);
    }
}
