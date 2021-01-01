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
      * @par API\TaskController@storeTask (POST)
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
      * @retval JSON     Success 200: task of the type Task
      * @retval JSON     Failed 200: error message
      */
    public function storeTask(Request $request)
    {
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

        return response()->json(['success' => $task], $this->successStatus);
    }

    /**
     * @par API\TaskController@updateTask (POST)
     * Update an existing task
     * 
     * @param task_id          ID of the task to update
     * @param description      Describes the task in a few concise words
     * @param interval         After how many days the task shall repeat itself (uint)
     * @param start_datetime   At which datetime the task shall start (Y/m/d H:i:s)
     * @param reminder         Boolean which indicates a reminder needs to be sent (0 for false, 1 for true)
     * @param mark_complete    Boolean which indicates if the task need to be marked as complete (0 for false, 1 for true)
    *
     * @retval JSON     Success 200: returns the updated task of the type Task
     * @retval JSON     Failed 200: error message
     */
    public function updateTask(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'task_id' => 'required|integer',
            'name' => 'required|string',
            'description' => 'required|string',
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

        $task = DB::table('tasks')->where('task_id', $request->input('task_id'))->get();
        $task->name = $request->input('name');
        $task->description = $request->input('description');
        $task->interval = $request->input('interval');
        $task->start_datetime = $request->input('start_datetime');
        $task->reminder = $request->input('reminder');
        $task->mark_complete = $request->input('mark_complete');
        $task->save();

        return response()->json(['success' => $task], $this->successStatus);
    }

    /**
     * @par API\TaskController@destroyTask (POST)
     * Delete a task
     * 
     * @param task_id          ID of the task to delete
    *
     * @retval JSON     Success 200: returns the deleted task of the type Task
     * @retval JSON     Failed 200: error message
     */
    public function destroyTask(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'task_id' => 'required|integer',
        ]);

        if($validator->fails() == true) {
            return response()->json(['error' => $validator->errors()], $this->errorStatus);
        }

        $destroyed_task = Task::destroy($request->task_id);
        if($destroyed_task) {
            return response()->json(['success' => $destroyed_task], $this->successStatus);
        } else {
            return response()->json(['error' => $destroyed_task], $this->errorStatus);
        }
    }

    /**
     * @par API\TaskController@indexTask (GET)
     * Request information of a task, before it can be edited, data necessary data for front-end
     * 
     * @param task_id       ID of the task to retrieve
     * 
     * @retval JSON     Success 200: the requested task of the type Task
     * @retval JSON     Failed 200: error message
     */
    public function indexTask($task_id)
    {
        // 1. Check which house the task belongs to
        $task = DB::table('tasks')->where('task_id', $task_id)->get();

        // 2. Check whether user belongs to house
        $houseController = new HouseController();
        if(!$houseController->userBelongsToHouse($house_id, Auth::id())) {
            return response()->json(['error' => 'User does not belong to this house'], $this->errorStatus);
        }

        // 3. Return the task
        return response()->json(['success' => $task], $this->successStatus);
    }

    /**
     * @par API\TaskController@indexHouse (GET)
     * Gets all the tasks belonging to a student house
     *
     * @param house_id  Which house to index the tasks for
     *
     * @retval JSON     Success 200: tasks per house each of the type Task
     * @retval JSON     Failed 200: error message
     *
     */
    public function indexHouse($house_id)
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
     * @par API\TaskController@indexUser (GET)
     * Gets all the tasks belonging to the user
     *
     * @param house_id  Which user to index the tasks for
     *
     * @retval JSON     Success 200: tasks per user each of the type Task
     *
     */
    public function indexUser($user_id)
    {
        $tasks_per_user = DB::table('tasks')->where('user_id', $user_id)->get();

        return response()->json(['success' => $tasks_per_house], $this->successStatus);
    }

     /**
      * @par API\TaskController@indexTaskPerWeek (GET)
      * Retrieve a certain task ID for a certain number of weeks
      *
      * @param task_id      Task to return
      * @param no_weeks     Number of weeks to run the task for
      *
      * @retval JSON     Success 200: tasks per week each of type Task
      * @retval JSON     Failed 200: error message
      */
    public function indexTaskPerWeek($task_id, $no_weeks)
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
        $now = date('Y-m-d'); // Not including the time causes todays occurrence to be showed as well
        $past_occurrences = floor(((strtotime($now) - strtotime($db_task[0]->start_datetime)) / (24 * 60 * 60)) / $db_task[0]->interval + 1);
        if ($past_occurrences < 0) {
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
      * @par API\TaskController@indexHousePerWeek (GET)
      * Retrieve the tasks for a certain user for a certain number of upcoming weeks
      *
      * @param house_id     The ID of the user to retrieve the tasks for
      * @param no_weeks     The number of weeks to return tasks for
      *
      * @retval JSON     Success 200: tasks per week each of the type Task
      * @retval JSON     Failed 200: error message
      */
    public function indexHousePerWeek($house_id, $no_weeks)
    {
        $houseController = new HouseController();
        if(!$houseController->userBelongsToHouse($house_id, Auth::id())) {
            return response()->json(['error' => 'User does not belong to this house'], $this->errorStatus);
        }

        // Return the different tasks for this house
        $tasks = DB::table('tasks')->where('house_id', $house_id)->get();

        $tasks_per_week = tasks_per_week($tasks);

        return response()->json(['success' => $tasks_per_week], $this->successStatus);
    }

    /**
      * @par API\TaskController@indexUserPerWeek (GET)
      * Retrieve the tasks for a certain user for a certain number of upcoming weeks
      *
      * @param house_id     The ID of the user to retrieve the tasks for
      * @param no_weeks     The number of weeks to return tasks for
      *
      * @retval JSON     Success 200
      * @retval JSON     Failed 200
      */
      public function indexUserPerWeek($user_id, $no_weeks)
      {
          $houseController = new HouseController();
          if(!$houseController->userBelongsToHouse($house_id, Auth::id())) {
              return response()->json(['error' => 'User does not belong to this house'], $this->errorStatus);
          }
  
          // Return the different tasks for the requested user
          $tasks = DB::table('tasks')->where('user_id', $user_id)->get();
  
          $tasks_per_week = tasksPerWeek($tasks);
  
          return response()->json(['success' => $tasks_per_week], $this->successStatus);
      }

    private function tasksPerWeek($tasks)
    {
        // Determine earliest start datetime of all tasks assigned to this house
        $start_datetime = date('Y-m-d');
        foreach($tasks as $task) {
            if ($task->start_datetime < $start_datetime) {
                $start_datetime = $task->start_datetime;
            }
        }

        $tasks_per_day = tasksPerDay($tasks);

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
    }

    private function tasksPerDay($tasks)
    {
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
                        $occurrence_date = strtotime(date('Y-m-d')) + (24 * 60 * 60 * $i); // Not including the time causes todays occurrence to be showed as well
                        $past_occurrences = floor((($occurrence_date - strtotime($db_task[0]->start_datetime)) / (24 * 60 * 60)) / $db_task[0]->interval + 1);
                        if ($past_occurrences < 1) {
                            // There were no past occurrences
                        } else {
                            $task_occurrence = array();
                            $seconds_since_start = $past_occurrences * $db_task[0]->interval * (24 * 60 * 60);
                            $task_occurrence['name'] = $db_task[0]->name;
                            $task_occurrence['date'] = date('Y-m-d', strtotime($db_task[0]->start_datetime) + $seconds_since_start);
                            $task_occurrence['assignee'] = $assignees[$i % sizeof($assignees)];
                            array_push($tasks_per_day, $task_occurrence);
                        }
                    }
                }
            }
        }
    }

    /**
     * @par API\TaskController@assignUser (POST)
     * Assign a certain user to a certain task. Only possible if the authenticated user belongs to the same house the task belongs to.
     * 
     * @param task_id       ID of the task for the user to assign to
     * @param user_email    Email address of the user to assign to the task
     * 
     * @retval JSON     Success 200
     * @retval JSON     Failed 200
     */
    public function assignUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'task_id' => 'required|integer',
            'user_email' => 'required|email',
        ]);

        if($validator->fails() == true) {
            return response()->json(['error' => $validator->errors()], $this->errorStatus);
        }

        $user_id = findUserIdBasedOnEmail($request->input('user_email'));
        // Verify user is authorized to delete user from task
        CheckUserBelongsToHouseBasedOnTaskId($request->input('task_id'));

        // Assign user to this task
        $users_per_task = new UsersPerTask();
        $users_per_task->task_id = $request->input('task_id');
        $users_per_task->user_id = $user_id;
        $users_per_task->save();

        // TODO(PATBRO): retrieve all tasks user is assigned to and return that
        return response()->json(['success' => $users_per_task], $this->successStatus);
    }

    /**
     * @par API\TaskController@removeUser (POST)
     * Remove a certain user from a certain task. Only possible if the authenticated user belongs to the same house the task belongs to.
     * 
     * @param task_id       ID of the task to remove the user from
     * @param user_email    Email address of the user currently assigned to the task
     * 
     * @retval JSON     Success 200
     * @retval JSON     Failed 200
     */
    public function removeUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'task_id' => 'required|integer',
            'user_email' => 'required|email',
        ]);

        if($validator->fails() == true) {
            return response()->json(['error' => $validator->errors()], $this->errorStatus);
        }

        $user_id = findUserIdBasedOnEmail($request->input('user_email'));
        // Verify user is authorized to delete user from task
        CheckUserBelongsToHouseBasedOnTaskId($request->input('task_id'));

        // Check whether user actually belongs to the task ID
        try {
            DB::table('users_per_tasks')->where('task_id', $task_id)->where('user_id', $user_id)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => $e->getMessage()], $this->errorStatus);
        }

        $task = DB::table('users_per_tasks')->where('task_id', $task_id)->where('user_id', $user_id)->delete();
        if ($task) {
            return response()->json(['success' => $task], $this->successStatus);
        } else {
            return response()->json(['error' => $task], $this->errorStatus);
        }
    }

    /* TODO(PATBRO): move to UserController */
    private function findUserIdBasedOnEmail($user_email)
    {
        /* TODO(LUKANT): determine whether to assign user based on email address or user ID */
        if(DB::table('users')->where('email', $user_email)->exists() == false) {
            /* Check if user with this email address exists */
            return response()->json(['error' => 'User not found'], $this->errorStatus);
        }

        return DB::table('users')->where('email', $user_email)->value('id');
    }

    private function CheckUserBelongsToHouseBasedOnTaskId($task_id)
    {
        // Retrieve house ID this task belongs to
        $house_id = DB::table('tasks')->where('id', $task_id)->value('house_id');

        $houseController = new HouseController();
        if(!$houseController->userBelongsToHouse($house_id, Auth::id())) {
            return response()->json(['error' => 'User does not belong to this house'], $this->errorStatus);
        }
    }
}
