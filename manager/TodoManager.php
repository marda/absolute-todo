<?php

namespace Absolute\Module\Todo\Manager;

use Nette\Database\Context;
use Absolute\Core\Manager\BaseManager;
use Absolute\Module\Todo\Classes\Todo;
use Absolute\Module\Todo\Classes\Link;
use Absolute\Module\File\Manager\FileManager;
use Absolute\Module\Team\Manager\TeamManager;
use Absolute\Module\Category\Manager\CategoryManager;
use Absolute\Module\User\Manager\UserManager;

class TodoManager extends BaseManager
{

    /** @var \Absolute\Module\Team\Manager\TeamManager  */
    public $teamManager;

    /** @var \Absolute\Module\File\Manager\FileManager  */
    public $fileManager;

    /** @var \Absolute\Module\Category\Manager\CategoryManager */
    public $categoryManager;

    /** @var \Absolute\Module\User\Manager\UserManager */
    public $userManager;

    public function __construct(Context $database, FileManager $fileManager, TeamManager $teamManager, CategoryManager $categoryManager, UserManager $userManager)
    {
        parent::__construct($database);
        $this->teamManager = $teamManager;
        $this->fileManager = $fileManager;
        $this->categoryManager = $categoryManager;
        $this->userManager = $userManager;
    }

    /* INTERNAL/EXTERNAL INTERFACE */

    protected function _getTodo($db)
    {
        if ($db == false)
        {
            return false;
        }
        $object = new Todo($db->id, $db->user_id, $db->parent_todo_id, $db->title, $db->note, $db->color, $db->start_date, $db->due_date, $db->starred, $db->priority, $db->done, $db->deleted, $db->created, $db->budget, $db->proposal_budget);
        foreach ($db->related('todo_user') as $userDb)
        {
            $user = $this->userManager->_getUser($userDb->user);
            if ($user)
            {
                $object->addUser($user);
            }
        }
        foreach ($db->related('todo_team') as $teamDb)
        {
            $team = $this->teamManager->_getTeam($teamDb->team);
            if ($team)
            {
                $object->addTeam($team);
            }
        }
        foreach ($db->related('todo_category') as $categoryDb)
        {
            $category = $this->categoryManager->getCategory($categoryDb->category);
            if ($category)
            {
                $object->addCategory($category);
            }
        }
        foreach ($db->related('todo_file') as $fileDb)
        {
            $file = $this->fileManager->_getFile($fileDb->file);
            if ($file)
            {
                $object->addFile($file);
            }
        }
        return $object;
    }

    protected function _getLink($db)
    {
        if ($db == false)
        {
            return false;
        }
        $object = new Link($db->id, $db->source_todo, $db->target_todo, $db->type);
        return $object;
    }

    public function _getById($id)
    {
        $db = $this->database->table('todo')->get($id);
        $object = $this->_getTodo($db);
        // Labels
        foreach ($db->related('todo_label') as $labelDb)
        {
            $label = $this->_getLabel($labelDb->label);
            if ($label)
            {
                $object->addLabel($label);
            }
        }
        // Project
        $projectDb = $db->related('project_todo')->fetch();
        if ($projectDb)
        {
            $object->setProjectId($projectDb->project_id);
        }
        return $object;
    }

    private function _getList($userId, $offset, $limit)
    {
        $offset = $offset == null ? 0 : $offset;
        $limit = $limit == null ? 50 : $limit;
        $ret = array();
        $resultDb = $this->database->table('todo')->limit($limit, $offset)->order('start_date ASC');
        foreach ($resultDb as $db)
        {
            $object = $this->_getTodo($db);
            if ($this->canUserView($object->getId(), $userId))
                $ret[] = $object;
        }
        return $ret;
    }

    private function _getLinks()
    {
        $ret = array();
        $resultDb = $this->database->table('todo_link');
        foreach ($resultDb as $db)
        {
            $object = $this->_getLink($db);
            $ret[] = $object;
        }
        return $ret;
    }

    private function _getProjectList($projectId)
    {
        $labels = $this->database->table('project_label')->where('project_id', $projectId)->fetchPairs('label_id', 'label_id');
        $ret = [];
        $resultDb = $this->database->table('todo')->where(':project_todo.project_id', $projectId)->order('start_date ASC');
        foreach ($resultDb as $db)
        {
            $object = $this->_getTodo($db);
            // Labels
            /*foreach ($db->related('todo_label')->where('label_id', $labels) as $labelDb)
            {
                $label = $this->_getLabel($labelDb->label);
                if ($label)
                {
                    $object->addLabel($label);
                }
            }*/
            $ret[] = $object;
        }
        return $ret;
    }

    // return all task which crete user
    private function _getUserList($userId)
    {
        $labels = $this->database->table('label')->where('user_id', $userId)->fetchPairs('id', 'id');
        $ret = [];
        $resultDb = $this->database->table('todo')->where('user_id', $userId)->order('start_date ASC');
        foreach ($resultDb as $db)
        {
            $object = $this->_getTodo($db);
            // Labels
            foreach ($db->related('todo_label')->where('label_id', $labels) as $labelDb)
            {
                $label = $this->_getLabel($labelDb->label);
                if ($label)
                {
                    $object->addLabel($label);
                }
            }
            // Project
            $projectDb = $db->related('project_todo')->fetch();
            if ($projectDb)
            {
                $object->setProjectId($projectDb->project_id);
            }
            $ret[] = $object;
        }
        return $ret;
    }

    // Return all todo tasks which are assigned to user
    private function _getUserAssignedList($userId)
    {
        $labels = $this->database->table('label')->where('user_id', $userId)->fetchPairs('id', 'id');
        $ret = [];
        $resultDb = $this->database->table('todo')->where(':todo_user.user_id', $userId)->order('start_date ASC');
        foreach ($resultDb as $db)
        {
            $object = $this->_getTodo($db);
            // Labels
            foreach ($db->related('todo_label')->where('label_id', $labels) as $labelDb)
            {
                $label = $this->_getLabel($labelDb->label);
                if ($label)
                {
                    $object->addLabel($label);
                }
            }
            // Project
            $projectDb = $db->related('project_todo')->fetch();
            if ($projectDb)
            {
                $object->setProjectId($projectDb->project_id);
            }
            $ret[] = $object;
        }
        return $ret;
    }

    private function _getUserDueDateList($userId, $dueDate)
    {
        $labels = $this->database->table('label')->where('user_id', $userId)->fetchPairs('id', 'id');
        $ret = [];
        $resultDb = $this->database->table('todo')->where('user_id', $userId)->where('DATE(due_date) = DATE(?)', $dueDate)->order('start_date ASC');
        foreach ($resultDb as $db)
        {
            $object = $this->_getTodo($db);
            // Labels
            foreach ($db->related('todo_label')->where('label_id', $labels) as $labelDb)
            {
                $label = $this->_getLabel($labelDb->label);
                if ($label)
                {
                    $object->addLabel($label);
                }
            }
            // Project
            $projectDb = $db->related('project_todo')->fetch();
            if ($projectDb)
            {
                $object->setProjectId($projectDb->project_id);
            }
            $ret[] = $object;
        }
        return $ret;
    }

    // Return all tasks form one project which are assigned to user
    private function _getUserProjectAssignedList($projectId, $userId)
    {
        $projectLabels = $this->database->table('project_label')->where('project_id', $projectId)->fetchPairs('label_id', 'label_id');
        $userLabels = $this->database->table('label')->where('user_id', $userId)->fetchPairs('id', 'id');
        $labels = array_merge($projectLabels, $userLabels);
        $ret = [];
        $resultDb = $this->database->table('todo')->where('user_id', $userId)->where(':project_todo.project_id', $projectId)->order('start_date ASC');
        foreach ($resultDb as $db)
        {
            $object = $this->_getTodo($db);
            $projectDb = $db->related('project_todo')->fetch();
            if ($projectDb && array_key_exists($projectDb->project_id, $projects))
            {
                $role = $projects[$projectDb->project_id];
                if ($role != "manager" && $role != "owner")
                {
                    $object->setEditable(false);
                }
            }
            else
            {
                $object->setEditable(false);
            }
            $object->setProjectId($projectDb->project_id);
            // Labels
            foreach ($db->related('todo_label')->where('label_id', $labels) as $labelDb)
            {
                $label = $this->_getLabel($labelDb->label);
                if ($label)
                {
                    $object->addLabel($label);
                }
            }
            $ret[] = $object;
        }
        return $ret;
    }

    // Return all tasks from all project where user is assigned to
    private function _getUserProjectList($userId)
    {
        $projects = $this->database->table('project_user')->where('user_id', $userId)->fetchPairs('project_id', 'role');
        $projectLabels = $this->database->table('project_label')->where('project_id', array_keys($projects))->fetchPairs('label_id', 'label_id');
        $userLabels = $this->database->table('label')->where('user_id', $userId)->fetchPairs('id', 'id');
        $labels = array_merge($projectLabels, $userLabels);
        $ret = [];
        $resultDb = $this->database->table('todo')->where(':project_todo.project_id', array_keys($projects))->order('start_date ASC');
        foreach ($resultDb as $db)
        {
            $object = $this->_getTodo($db);
            $projectDb = $db->related('project_todo')->fetch();
            if ($projectDb && array_key_exists($projectDb->project_id, $projects))
            {
                $role = $projects[$projectDb->project_id];
                if ($role != "manager" && $role != "owner")
                {
                    $object->setEditable(false);
                }
            }
            else
            {
                $object->setEditable(false);
            }
            $object->setProjectId($projectDb->project_id);
            // Labels
            foreach ($db->related('todo_label')->where('label_id', $labels) as $labelDb)
            {
                $label = $this->_getLabel($labelDb->label);
                if ($label)
                {
                    $object->addLabel($label);
                }
            }
            $ret[] = $object;
        }
        return $ret;
    }

    private function _getUserProjectDueDateList($userId, $dueDate)
    {
        $projects = $this->database->table('project_user')->where('user_id', $userId)->fetchPairs('project_id', 'role');
        $projectLabels = $this->database->table('project_label')->where('project_id', array_keys($projects))->fetchPairs('label_id', 'label_id');
        $userLabels = $this->database->table('label')->where('user_id', $userId)->fetchPairs('id', 'id');
        $labels = array_merge($projectLabels, $userLabels);
        $ret = [];
        $resultDb = $this->database->table('todo')->where(':project_todo.project_id', array_keys($projects))->where('DATE(due_date) = DATE(?)', $dueDate)->order('start_date ASC');
        foreach ($resultDb as $db)
        {
            $object = $this->_getTodo($db);
            $projectDb = $db->related('project_todo')->fetch();
            if ($projectDb && array_key_exists($projectDb->project_id, $projects))
            {
                $role = $projects[$projectDb->project_id];
                if ($role != "manager" && $role != "owner")
                {
                    $object->setEditable(false);
                }
            }
            else
            {
                $object->setEditable(false);
            }
            $object->setProjectId($projectDb->project_id);
            // Labels
            foreach ($db->related('todo_label')->where('label_id', $labels) as $labelDb)
            {
                $label = $this->_getLabel($labelDb->label);
                if ($label)
                {
                    $object->addLabel($label);
                }
            }
            $ret[] = $object;
        }
        return $ret;
    }

    private function _getUserProjectRecentList($userId)
    {
        $projects = $this->database->table('project_user')->where('user_id', $userId)->fetchPairs('project_id', 'role');
        $projectLabels = $this->database->table('project_label')->where('project_id', array_keys($projects))->fetchPairs('label_id', 'label_id');
        $userLabels = $this->database->table('label')->where('user_id', $userId)->fetchPairs('id', 'id');
        $labels = array_merge($projectLabels, $userLabels);
        $ret = [];
        $resultDb = $this->database->table('todo')->where(':project_todo.project_id', array_keys($projects))->where('UNIX_TIMESTAMP(created) > (UNIX_TIMESTAMP(NOW()) - 24 * 60 * 60)')->order('start_date ASC');
        foreach ($resultDb as $db)
        {
            $object = $this->_getTodo($db);
            $projectDb = $db->related('project_todo')->fetch();
            if ($projectDb && array_key_exists($projectDb->project_id, $projects))
            {
                $role = $projects[$projectDb->project_id];
                if ($role != "manager" && $role != "owner")
                {
                    $object->setEditable(false);
                }
            }
            else
            {
                $object->setEditable(false);
            }
            $object->setProjectId($projectDb->project_id);
            // Labels
            foreach ($db->related('todo_label')->where('label_id', $labels) as $labelDb)
            {
                $label = $this->_getLabel($labelDb->label);
                if ($label)
                {
                    $object->addLabel($label);
                }
            }
            $ret[] = $object;
        }
        return $ret;
    }

    private function _getTodoLinkList($todoId)
    {
        $ret = array();
        $resultDb = $this->database->table('todo_link')->where('source_todo', $todoId);
        foreach ($resultDb as $db)
        {
            $object = $this->_getLink($db);
            $ret[] = $object;
        }
        return $ret;
    }

    private function _getTodoLinkItem($todoId, $linkId)
    {
        return $this->_getLink($this->database->table('todo_link')->where('source_todo', $todoId)->where("target_todo", $linkId)->fetch());
    }

    public function _linkTodoDelete($todoId, $linkId)
    {
        return $this->database->table('todo_link')->where('source_todo', $todoId)->where('target_todo', $linkId)->delete();
    }

    public function _linkTodoCreate($noteId, $linkId, $type)
    {
        return $this->database->table('todo_link')->insert(['target_todo' => $linkId, 'source_todo' => $noteId, 'type' => $type]);
    }

    public function _linkTodoUpdate($noteId, $linkId, $type)
    {
        return $this->database->table('todo_link')->where(['target_todo' => $linkId, 'source_todo' => $noteId])->update( ['type' => $type]);
    }

    public function linkTodoUpdate($noteId, $linkId, $type)
    {
        return $this->_linkTodoUpdate($noteId, $linkId, $type);
    }

    public function getTodoLinkList($todoId)
    {
        return $this->_getTodoLinkList($todoId);
    }

    public function getTodoLinkItem($todoId, $targetId)
    {
        return $this->_getTodoLinkItem($todoId, $targetId);
    }

    public function linkTodoDelete($todoId, $targetId)
    {
        return $this->_linkTodoDelete($todoId, $targetId);
    }

    public function linkTodoCreate($todoId, $targetId, $type)
    {
        return $this->_linkTodoCreate($todoId, $targetId, $type);
    }

    private function _getProjectDueDateCount($projectId, $dueDate)
    {
        return $this->database->table('todo')->where(':project_todo.project_id', $projectId)->where('DATE(due_date) = DATE(?)', $dueDate)->count("*");
    }

    private function _getProjectOverdueDateCount($projectId, $dueDate)
    {
        return $this->database->table('todo')->where(':project_todo.project_id', $projectId)->where('done', false)->where('DATE(due_date) < DATE(?)', $dueDate)->count("*");
    }

    private function _getProjectDoneDueDateCount($projectId, $dueDate)
    {
        return $this->database->table('todo')->where(':project_todo.project_id', $projectId)->where('done', true)->where('DATE(due_date) = DATE(?)', $dueDate)->count("*");
    }

    private function _getUserUndoneDueDateCount($userId, $dueDate)
    {
        return $this->database->table('todo')->where('todo.user_id ? OR :todo_user.user_id ?', $userId, $userId)->where('DATE(due_date) = DATE(?)', $dueDate)->where('done', false)->count("DISTINCT(todo.id)");
    }

    private function _getUserUndoneCount($userId)
    {
        return $this->database->table('todo')->where('todo.user_id ? OR :todo_user.user_id ?', $userId, $userId)->where('done', false)->count("DISTINCT(todo.id)");
    }

    private function _getUserOverdueCount($userId)
    {
        return $this->database->table('todo')->where('todo.user_id ? OR :todo_user.user_id ?', $userId, $userId)->where('DATE(due_date) < DATE(NOW())')->where('done', false)->count("DISTINCT(todo.id)");
    }

    // only roles manager and owner in project
    private function _canUserEdit($id, $userId)
    {
        $db = $this->database->table('todo')->get($id);
        if (!$db)
        {
            return false;
        }
        if ($db->user_id === $userId)
        {
            return true;
        }
        $projectsInManagement = $this->database->table('project_user')->where('user_id', $userId)->where('role', array('owner', 'manager'))->fetchPairs('project_id', 'project_id');
        $projects = $this->database->table('project_todo')->where('todo_id', $id)->fetchPairs('project_id', 'project_id');
        return (!empty(array_intersect($projects, $projectsInManagement))) ? true : false;
    }

    // all users from project
    private function _canUserView($id, $userId)
    {
        $db = $this->database->table('todo')->get($id);
        if (!$db)
        {
            return false;
        }
        if ($db->user_id === $userId)
        {
            return true;
        }
        $projectsInManagement = $this->database->query("SELECT project_id AS id FROM project_user WHERE user_id = ? UNION SELECT project_id AS id FROM project_team JOIN team_user WHERE user_id = ? UNION SELECT project_id AS id FROM project_category JOIN category_user WHERE user_id = ?", $userId, $userId, $userId)->fetchPairs('id', 'id');
        $projects = $this->database->table('project_todo')->where('todo_id', $id)->fetchPairs('project_id', 'project_id');
        if (!empty(array_intersect($projects, $projectsInManagement)) && !empty($projects))
        {
            return true;
        }
        // Can view based on page assigned to users, teams or categories
        $db = $this->database->query("SELECT todo_id AS id FROM todo_user WHERE user_id = ? UNION SELECT todo_id AS id FROM todo_team JOIN team_user WHERE user_id = ? UNION SELECT todo_id AS id FROM todo_category JOIN category_user WHERE user_id = ? UNION SELECT id FROM todo WHERE id NOT IN (SELECT todo_id FROM todo_user) AND id NOT IN (SELECT todo_id FROM todo_team) AND id NOT IN (SELECT todo_id FROM todo_category)", $userId, $userId, $userId);
        $result = $db->fetchPairs("id", "id");
        if (array_key_exists($id, $result))
        {
            return true;
        }
        return false;
    }

    private function _getProjectItem($projectId, $todoId)
    {
        return $this->_getTodo($this->database->table('todo')->where(':project_todo.project_id', $projectId)->where("todo_id", $todoId)->fetch());
    }

    public function _todoProjectDelete($projectId, $todoId)
    {
        return $this->database->table('project_todo')->where('project_id', $projectId)->where('todo_id', $todoId)->delete();
    }

    public function _todoProjectCreate($projectId, $todoId)
    {
        return $this->database->table('project_todo')->insert(['project_id' => $projectId, 'todo_id' => $todoId]);
    }

    /* EXTERNAL METHOD */

    public function getProjectItem($projectId, $todoId)
    {
        return $this->_getProjectItem($projectId, $todoId);
    }

    public function todoProjectDelete($projectId, $todoId)
    {
        return $this->_todoProjectDelete($projectId, $todoId);
    }

    public function todoProjectCreate($projectId, $todoId)
    {
        return $this->_todoProjectCreate($projectId, $todoId);
    }

    public function getById($id)
    {
        return $this->_getById($id);
    }

    public function getList($userId, $offset, $limit)
    {
        return $this->_getList($userId, $offset, $limit);
    }

    public function getProjectList($projectId)
    {
        return $this->_getProjectList($projectId);
    }

    public function getUserList($userId)
    {
        return $this->_getUserList($userId);
    }

    public function getUserDueDateList($userId, $dueDate)
    {
        return $this->_getUserDueDateList($userId, $dueDate);
    }

    public function getUserProjectAssignedList($projectId, $userId)
    {
        return $this->_getUserProjectAssignedList($projectId, $userId);
    }

    public function getUserAssignedList($userId)
    {
        return $this->_getUserAssignedList($userId);
    }

    public function getUserProjectList($userId)
    {
        return $this->_getUserProjectList($userId);
    }

    public function getUserProjectDueDateList($userId, $dueDate)
    {
        return $this->_getUserProjectDueDateList($userId, $dueDate);
    }

    public function getUserProjectRecentList($userId)
    {
        return $this->_getUserProjectRecentList($userId);
    }

    public function getProjectDueDateCount($projectId, $dueDate)
    {
        return $this->_getProjectDueDateCount($projectId, $dueDate);
    }

    public function getProjectOverdueDateCount($projectId, $dueDate)
    {
        return $this->_getProjectOverdueDateCount($projectId, $dueDate);
    }

    public function getProjectDoneDueDateCount($projectId, $dueDate)
    {
        return $this->_getProjectDoneDueDateCount($projectId, $dueDate);
    }

    public function getUserUndoneDueDateCount($userId, $dueDate)
    {
        return $this->_getUserUndoneDueDateCount($userId, $dueDate);
    }

    public function getUserUndoneCount($userId)
    {
        return $this->_getUserUndoneCount($userId);
    }

    public function getUserOverdueCount($userId)
    {
        return $this->_getUserOverdueCount($userId);
    }

    public function getLinks()
    {
        return $this->_getLinks();
    }

    public function canUserEdit($id, $userId)
    {
        return $this->_canUserEdit($id, $userId);
    }

    public function canUserView($id, $userId)
    {
        return $this->_canUserView($id, $userId);
    }

}
