<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class TaskController extends Controller
{
    public $successStatus = 200;
    public $errorStatus = 400;
    public $unauthorisedStatus = 401;

    /* Store a new task belonging to a student house */
    public function store(Request $request)
    {
        // TODO(PATBRO): Add task with general information to database (to a specific house)

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'description' => 'required',
            'house_id' => 'required',
            'users' => 'optional', /* TODO(PATBRO): make it possible to assign only a couple of users to a specific task */
            'interval' => 'required',
            'start_datetime' => 'required',
            'reminder' => 'optional|boolean',
            'mark_complete' => 'optional|boolean',
        ]);

        if(!app('HouseController::class')->userBelongsToHouse($request->input('house_id'), Auth::id())) {
            return response()->json(['error' => 'User does not belong to this house'], $this->errorStatus);
        }

        $task = new Task;
        $task->name = $request->input('name');
        $task->description = $request->input('description');
        $task->house_id = $request->input('house_id');
        $task->users = Auth::id();
        $task->repeat = $request->input('repeat');
        $task->time = $request->input('time');
        $task->reminder = $request->input('reminder');
        $task->mark_complete = $request->input('mark_complete');


    }

     /**
      * @par API\TaskController@index (GET)
      * Gets all the tasks belonging to a student house or user
      *
      * @param house_id  Which house to index the tasks for.
      *
      * @retval JSON     Success 200
      * @retval JSON     Failed 200
      */
    public function index($house_id)
    {
        if(app('HouseController::class')->userBelongsToHouse($house_id, Auth::id())) {
            // Return the different tasks for this house
            $tasks_per_house = DB::table('tasks_per_houses')->where('house_id', $house_id)->get();

            return response()->json(['success' => $tasks_per_house], $this->successStatus);
        }

        return response()->json(['error' => 'User does not belong to this house'], $this->errorStatus);
    }

    // Request tasks belonging to a student house, a user or a certain task type
    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'house_id' => 'nullable|numeric',
            'user_id' => 'nullable|numeric',
            'task' => 'nullable',
        ]);
    }

    // Request to edit an existing task, return needed data for front-end
    public function edit(Request $request)
    {

    }

    // Update an existing task
    public function update(Request $request)
    {

    }

    // Delete a task
    public function destroy(Request $request)
    {

    }
}
