<?php

namespace App\Http\Controllers\Api;

use Auth;
use App\Task;
use App\UsersPerTask;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;

class TaskController extends Controller
{
    public $successStatus = 200;
    public $errorStatus = 400;
    public $unauthorisedStatus = 401;

    /**
      * @par API\TaskController@store (POST)
      * Create a new task belonging to a certain student house
      *
      * @param name             Name of the task to create
      * @param description      Describes the task in a few concise words
      * @param house_id         House ID the task will belong to
      * @param interval         After how many days the task shall repeat itself (uint)
      * @param start_datetime   At which datetime the task shall start (Y/m/d H:i:s)
      * @param reminder         Boolean which indicates a reminder needs to be sent (0 for false, 1 for true)
      * @param mark_complete    Boolean which indicates if the task need to be marked as complete (0 for false, 1 for true)
      *
      * @retval JSON     Success 200
      * @retval JSON     Failed 200
      */
    public function store(Request $request)
    {
        // TODO(PATBRO): Add task with general information to database (to a specific house)

        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'description' => 'required|string',
            'house_id' => 'required|integer',
            'interval' => 'required|integer',
            'start_datetime' => 'required|date',
            'reminder' => 'required|boolean',
            'mark_complete' => 'required|boolean',
        ]);

        if($validator->fails() == true) {
            return response()->json(['error' => $validator->errors()], $this->errorStatus);
        }

        $houseController = new HouseController();
        if(!$houseController->userBelongsToHouse($request->input('house_id'), Auth::id())) {
            return response()->json(['error' => 'User does not belong to this house'], $this->errorStatus);
        }

        $task = new Task;
        $task->name = $request->input('name');
        $task->description = $request->input('description');
        $task->house_id = $request->input('house_id');
        $task->interval = $request->input('interval');
        $task->start_datetime = $request->input('start_datetime');
        $task->reminder = $request->input('reminder');
        $task->mark_complete = $request->input('mark_complete');
        $task->save();

        return response()->json(['success' => $task->id], $this->successStatus);
    }

    /**
      * @par API\TaskController@overview (GET)
      * Retrieve the tasks for a certain house for a certain number of upcoming weeks
      *
      * @param house_id     The ID of the house to retrieve the tasks for
      * @param no_weeks     The number of weeks to return tasks for
      *
      * @retval JSON     Success 200
      * @retval JSON     Failed 200
      */
    public function overview($house_id, $no_weeks)
    {
        $houseController = new HouseController();
        if(!$houseController->userBelongsToHouse($house_id, Auth::id())) {
            return response()->json(['error' => 'User does not belong to this house'], $this->errorStatus);
        }

        // Return the different tasks for this house
        $tasks = DB::table('tasks')->where('house_id', $house_id)->get();

        // Determine earliest start datetime of all tasks assigned to this house
        $start_datetime = date('Y-m-d');
        foreach($tasks as $task) {
            if ($task->start_datetime < $start_datetime) {
                $start_datetime = $task->start_datetime;
            }
        }

        $days = $no_weeks * 7;
        $tasks_per_day = array();
        for ($i = 0; $i < $days; $i++) {
            foreach ($tasks as $task) {
                if ($i % $task->interval == 0) {
                    // Find assigned users for this task
                    $assigned_users = DB::table('users_per_tasks')->where('task_id', $task->id)->get();
                    $assignees = array();
                    foreach ($assigned_users as $user) {
                        $userController = new UserController();
                        $db_user = $userController->getDetailsPerUserId($user->user_id);
                        array_push($assignees, $db_user->name);
                    }

                    if (sizeof($assignees) > 0) {
                        $db_task = DB::table('tasks')->where('id', $task->id)->get();
                        // Calculate number of occurrences for the future day we are making the overview for
                        $occurrence_date = strtotime(date('Y-m-d')) + (24 * 60 * 60 * $i);
                        $past_occurrences = (($occurrence_date - strtotime($db_task[0]->start_datetime)) / (24 * 60 * 60)) / $db_task[0]->interval;
                        if ($past_occurrences < -1) {
                            // There were no past occurrences
                        } else {
                            $task_occurrence = array();
                            $seconds_since_start = $past_occurrences * $db_task[0]->interval * (24 * 60 * 60);
                            $task_occurrence['name'] = $db_task[0]->name;
                            $task_occurrence['date'] = date('Y-m-d', strtotime($db_task[0]->start_datetime) + $seconds_since_start);
                            $task_occurrence['assignee'] = $assignees[$i % sizeof($assignees)];
                            $task_occurrence['occ'] = $past_occurrences;
                            array_push($tasks_per_day, $task_occurrence);
                        }
                    }
                }
            }
        }

        // Populate array with task per week
        $tasks_per_week = array();
        for($i = 0; $i < sizeof($tasks_per_day); $i++) {
            $week = array();
            $week['week'] = date('W', strtotime($tasks_per_day[$i]['date']));
            $week['tasks'] = array();
            while (true) {
                array_push($week['tasks'], $tasks_per_day[$i]);
                if(($i + 1) == sizeof($tasks_per_day) || date('W', strtotime($tasks_per_day[$i]['date'])) != date('W', strtotime($tasks_per_day[$i + 1]['date']))) {
                    break;
                }

                $i++;
            }

            array_push($tasks_per_week, $week);
        }

        return response()->json(['success' => $tasks_per_week], $this->successStatus);
    }

     /**
      * @par API\TaskController@index (GET)
      * Retrieve a certain task ID for a certain number of weeks
      *
      * @param task_id      Task to return
      * @param no_weeks     Number of weeks to run the task for
      *
      * @retval JSON     Success 200
      * @retval JSON     Failed 200
      */
    public function index($task_id, $no_weeks)
    {
        $assigned_users = DB::table('users_per_tasks')->where('task_id', $task_id)->get();
        $assignees = array();
        foreach ($assigned_users as $user) {
            $userController = new UserController();
            $db_user = $userController->getDetailsPerUserId($user->user_id);
            array_push($assignees, $db_user->name);
        }

        if (sizeof($assignees) == 0) {
            return response()->json(['error' => 'No one is assigned to this task'], $this->successStatus);
        }

        $db_task = DB::table('tasks')->where('id', $task_id)->get();
        // Calculate number of occurrences until today
        $now = date('Y-m-d H:i:s');
        $past_occurrences = ((strtotime($now) - strtotime($db_task[0]->start_datetime)) / (24 * 60 * 60)) / $db_task[0]->interval;
        if ($past_occurrences < 1) {
            $past_occurrences = 0;
        }

        // Calculate number of occurrences in the future
        $future_occurrences = ($no_weeks * 7) / $db_task[0]->interval;
        $total_occurrences = $past_occurrences + $future_occurrences;

        // Populate array with task per day
        $tasks = array();
        for($i = $past_occurrences; $i < $total_occurrences; $i++) {
            $task = array();
            $seconds_since_start = $i * $db_task[0]->interval * (24 * 60 * 60);
            $task['name'] = $db_task[0]->name;
            $task['date'] = date('Y-m-d', strtotime($db_task[0]->start_datetime) + $seconds_since_start);
            $task['assignee'] = $assignees[$i % sizeof($assignees)];
            array_push($tasks, $task);
        }

        // Populate array with task per week
        $tasks_per_week = array();
        for($i = 0; $i < sizeof($tasks); $i++) {
            $week = array();
            $week['week'] = date('W', strtotime($tasks[$i]['date']));
            $week['tasks'] = array();
            while (true) {
                array_push($week['tasks'], $tasks[$i]);
                if(($i + 1) == sizeof($tasks) || date('W', strtotime($tasks[$i]['date'])) != date('W', strtotime($tasks[$i + 1]['date']))) {
                    break;
                }

                $i++;
            }

            array_push($tasks_per_week, $week);
        }

        return response()->json(['success' => $tasks_per_week], $this->successStatus);
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
        $houseController = new HouseController();
        if($houseController->userBelongsToHouse($house_id, Auth::id())) {
            // Return the different tasks for this house
            $tasks_per_house = DB::table('tasks')->where('house_id', $house_id)->get();

            return response()->json(['success' => $tasks_per_house], $this->successStatus);
        }

        return response()->json(['error' => 'User does not belong to this house'], $this->errorStatus);
    }

    /**
     * @par API\TaskController@assign (POST)
     * Assign a certain user to a certain task. Only possible if the authenticated user belongs to the same house the task belongs to.
     * 
     * @param task_id       ID of the task for the user to assign to
     * @param user_email    Email address of the user to assign to the task
     * 
     * @retval JSON     Success 200
     * @retval JSON     Failed 200
     */
    public function assign(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'task_id' => 'required|integer',
            'user_email' => 'required|email',
        ]);

        if($validator->fails() == true) {
            return response()->json(['error' => $validator->errors()], $this->errorStatus);
        }

        /* TODO(PATBRO): replace this implementation because this is considered not secure */
        if(DB::table('users')->where('email', $request->input('user_email'))->exists() == false) {
            /* Check if user with this email address exists */
            return response()->json(['error' => 'User not found'], $this->errorStatus);
        }

        $user_id = DB::table('users')->where('email', $request->input('user_email'))->value('id');
        // Retrieve house ID this task belongs to
        $house_id = DB::table('tasks')->where('id', $request->input('task_id'))->value('house_id');

        $houseController = new HouseController();
        if(!$houseController->userBelongsToHouse($house_id, Auth::id())) {
            return response()->json(['error' => 'User does not belong to this house'], $this->errorStatus);
        }

        // Assign user to this task
        $users_per_task = new UsersPerTask();
        $users_per_task->task_id = $request->input('task_id');
        $users_per_task->user_id = $user_id;
        $users_per_task->save();

        return response()->json(['success' => $users_per_task], $this->successStatus);
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
