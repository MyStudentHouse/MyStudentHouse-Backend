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

    public function overview($house_id, $no_weeks)
    {
        if(app('HouseController::class')->userBelongsToHouse($house_id, Auth::id())) {
            // Return the different tasks for this house
            $tasks_per_house = DB::table('tasks_per_houses')->where('house_id', $house_id)->get();
        }

        // Determine earliest start datetime of all tasks assigned to this house
        $start_datetime = date('Y-m-d');
        foreach($tasks as $task) {
            if ($task->start_datetime < $start_datetime) {
                $start_datetime = $task->start_datetime;
            }
        }

        $days = array();
        for($i = 0; $i < $no_weeks * 7; $i++) { // Smallest interval (is per day)
            $days.append($datetime);
        }

        $tasks_per_day = array();
        foreach($days as $day) {
            foreach($tasks as $task) {
                if ($task->start_datetime >= $day) {
                    $no_iterations_passed = $day - $task->start_datetime;
                    $assignees = collect(json_decode($task->users));
                    $assignee = $assignees[($no_iterations_passed / $task->interval) % sizeof($assignees)];

                    // Prepare task to add
                    $task_per_day = array();
                    $task_per_day['date'] = $day;
                    $task_per_day['task_name'] = $task->name;
                    $task_per_day['assignee'] = $assignee;
                    // Add task to array
                    array_push($tasks_per_day, $task_per_day);
                }
            }
        }

        $offset = date($start_datetime, 'w');
        $tasks_per_week = array();
        $task_per_week = array();
        for($i = $offset; $i < sizeof($tasks_per_day); $i++) {
            array_push($task_per_week, $task_per_day);

            if (date($tasks_per_day[$i]['date'], 'w') != date($tasks_per_day[$i + 1]['date'], 'w')) {
                array_push($tasks_per_week, $task_per_week);
                $task_per_week = array(); // Reset task per week
            }
        }

        return response()->json(['success' => $tasks_per_week], $this->successStatus);
    }

     /**
      * @par API\TaskController@index (GET)
      * Gets all the tasks belonging to a student house or user
      *
      * @param task_id      Task to return
      * @param no_weeks     Number of weeks to run the task for
      *
      * @retval JSON     Success 200
      * @retval JSON     Failed 200
      */
    public function index($task_id, $no_weeks)
    {
        $dbTask = DB::table('tasks')->where('task_id', $task_id)->get();

        // Dynamically allocate user for each interval
        $users = collect(json_decode($task->users, true));

        $assignees = array();
        foreach ($users as $user_id) {
            $user = app('UserController::class')->getDetails($user_id);
            array_push($assignees, $user->name);
        }

        $tasks = array();
        for($i = 0; $i < $no_weeks; $i++) {
            $task = array();
            $task['date'] = $dbTask->start_datetime; // Convert to datetime format (with interval_type) using no_weeks
            $task['assignee'] = $assignees[$i % sizeof($assignees)];
            array_push($tasks, $task);
        }

        return response()->json(['success' => $tasks], $this->successStatus);
    }

    /**
     * @par API\TaskController@tasks_per_houses (GET)
     * Gets all the tasks belonging to a student house
     *
     * @param house_id  Which house to index the tasks for
     *
     * @retval JSON     Success 200
     * @retval JSON     Failed 200
     *
     */
    public function tasks_per_houses($house_id)
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
