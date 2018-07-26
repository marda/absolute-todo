<?php

namespace Absolute\Module\Todo\Manager;

use Nette\Database\Context;
use Absolute\Core\Manager\BaseCRUDManager;
use Absolute\Core\Helper\DateHelper;

class TodoCRUDManager extends BaseCRUDManager
{

    public function __construct(Context $database)
    {
        parent::__construct($database);
    }

    // CUD METHODS

    public function connectUsers($id, $users)
    {
        $users = array_unique(array_filter($users));
        // DELETE
        $this->database->table('todo_user')->where('todo_id', $id)->delete();
        // INSERT NEW
        $data = [];
        foreach ($users as $userId)
        {
            $data[] = array(
                "todo_id" => $id,
                "user_id" => $userId,
            );
        }

        if (!empty($data))
        {
            $this->database->table('todo_user')->insert($data);
        }
        return true;
    }

    public function connectTeams($id, $teams)
    {
        $teams = array_unique(array_filter($teams));
        // DELETE
        $this->database->table('todo_team')->where('todo_id', $id)->delete();
        // INSERT NEW
        $data = [];
        foreach ($teams as $team)
        {
            $data[] = [
                "team_id" => $team,
                "todo_id" => $id,
            ];
        }
        if (!empty($data))
        {
            $this->database->table("todo_team")->insert($data);
        }
        return true;
    }

    public function connectCategories($id, $categories)
    {
        $categories = array_unique(array_filter($categories));
        // DELETE
        $this->database->table('todo_category')->where('todo_id', $id)->delete();
        // INSERT NEW
        $data = [];
        foreach ($categories as $category)
        {
            $data[] = [
                "category_id" => $category,
                "todo_id" => $id,
            ];
        }
        if (!empty($data))
        {
            $this->database->table("todo_category")->insert($data);
        }
        return true;
    }

    public function connectProjectLabels($id, $labels, $projectId)
    {
        $labels = array_filter($labels);
        $data = [];
        foreach ($labels as $labelId)
        {
            $data[] = array(
                "todo_id" => $id,
                "label_id" => $labelId,
            );
        }
        $projectLabels = $this->database->table('project_label')->where('project_id', $projectId)->fetchPairs('label_id', 'label_id');
        $this->database->table('todo_label')->where('todo_id', $id)->where('label_id', $projectLabels)->delete();
        if (!empty($data))
        {
            $this->database->table('todo_label')->insert($data);
        }
        return true;
    }

    public function connectUserLabels($id, $labels, $userId)
    {
        $labels = array_filter($labels);
        $data = [];
        foreach ($labels as $labelId)
        {
            $data[] = array(
                "todo_id" => $id,
                "label_id" => $labelId,
            );
        }
        $userLabels = $this->database->table('label')->where('user_id', $userId)->fetchPairs('id', 'id');
        $this->database->table('todo_label')->where('todo_id', $id)->where('label_id', $userLabels)->delete();
        if (!empty($data))
        {
            $this->database->table('todo_label')->insert($data);
        }
        return true;
    }

    public function connectProject($id, $projectId)
    {
        $this->database->table('project_todo')->where('todo_id', $id)->delete();
        return $this->database->table('project_todo')->insert(array(
                    "todo_id" => $id,
                    "project_id" => $projectId
        ));
    }

    public function connectFile($id, $fileId)
    {
        return $this->database->table('todo_file')->insert(array(
                    "todo_id" => $id,
                    "file_id" => $fileId,
        ));
    }

    // CUD METHODS

    public function create($userId, $parentTodo, $title, $note, $startDate, $dueDate, $color, $budget, $proposalBudget)
    {
        $result = $this->database->table('todo')->insert(array(
            'user_id' => $userId,
            'title' => $title,
            'note' => $note,
            'parent_todo_id' => $parentTodo,
            'start_date' => DateHelper::validateDate($startDate),
            'due_date' => DateHelper::validateDate($dueDate),
            'created' => new \DateTime(),
            'color' => $color,
            'budget' => $budget,
            'proposal_budget' => $proposalBudget,
        ));
        return $result;
    }

    public function delete($id)
    {
        $this->database->table('todo_category')->where('todo_id', $id)->delete();
        $this->database->table('todo_team')->where('todo_id', $id)->delete();
        $this->database->table('todo_user')->where('todo_id', $id)->delete();
        $this->database->table("project_todo")->where("todo_id", $id)->delete();
        $this->database->table("todo_label")->where("todo_id", $id)->delete();
        return $this->database->table('todo')->where('id', $id)->delete();
    }

    public function update($id, $parentTodo, $title, $note, $startDate, $dueDate, $color, $budget, $proposalBudget)
    {
        if ($parentTodo && !$this->_checkTodoRecursion($id, $parentTodo))
        {
            $parentTodo = null;
        }
        return $this->database->table('todo')->where('id', $id)->update(array(
                    'title' => $title,
                    'note' => $note,
                    'parent_todo_id' => $parentTodo,
                    'start_date' => DateHelper::validateDate($startDate),
                    'due_date' => DateHelper::validateDate($dueDate),
                    'color' => $color,
                    'budget' => $budget,
                    'proposal_budget' => $proposalBudget,
        ));
    }

    public function updateWithArray($id, $post)
    {
        unset($post['id']);
        unset($post['user_id']);
        //TODO uložit tyto pole?
        unset($post['users']);
        unset($post['teams']);
        unset($post['categories']);
        unset($post['labels']);
        unset($post['files']);

        if (isset($post['start_date']))
            $post['start_date'] = DateHelper::validateDate($post['start_date']);

        if (isset($post['due_date']))
            $post['due_date'] = DateHelper::validateDate($post['due_date']);

        if (isset($post['parent_todo_id']))
            if ($post['parent_todo_id'] && !$this->_checkTodoRecursion($id, $post['parent_todo_id']))
                $post['parent_todo_id'] = null;

        return $this->database->table('todo')->where('id', $id)->update($post);
    }

    public function move($id, $startDate, $dueDate)
    {
        return $this->database->table('todo')->where('id', $id)->update(array(
                    'start_date' => DateHelper::validateDate($startDate),
                    'due_date' => DateHelper::validateDate($dueDate),
        ));
    }

    public function updatePriority($id, $value)
    {
        return $this->_updateKey($id, 'priority', (bool) $value);
    }

    public function updateStarred($id, $value)
    {
        return $this->_updateKey($id, 'starred', (bool) $value);
    }

    public function updateDone($id, $value)
    {
        return $this->_updateKey($id, 'done', (bool) $value);
    }

    public function updateDeleted($id, $value)
    {
        return $this->_updateKey($id, 'deleted', (bool) $value);
    }

    private function _updateKey($id, $key, $value)
    {
        return $this->database->table('todo')->where('id', $id)->update(array(
                    $key => $value
        ));
    }

    // GANTT CHART

    public function addLink($source, $target, $type)
    {
        return $this->database->table('todo_link')->insert(array(
                    "source_todo" => $source,
                    "target_todo" => $target,
                    "type" => $type,
        ));
    }

    public function removeLink($source, $target)
    {
        return $this->database->table('todo_link')->where("source_todo", $source)->where("target_todo", $target)->delete();
    }

    // PRIVATE METHOD

    private function _checkTodoRecursion($id, $parentId)
    {
        if ($id == $parentId)
        {
            return false;
        }
        $parents = $this->database->query('SELECT q.id AS id, @pv:=q.parent_todo_id AS parent_todo_id FROM (SELECT * FROM todo ORDER BY id DESC) q JOIN (select @pv:=?) tmp WHERE q.id=@pv', $id)->fetchPairs('id', 'parent_todo_id');
        $parentsParent = $this->database->query('SELECT q.id AS id, @pv:=q.parent_todo_id AS parent_todo_id FROM (SELECT * FROM todo ORDER BY id DESC) q JOIN (select @pv:=?) tmp WHERE q.id=@pv', $parentId)->fetchPairs('id', 'parent_todo_id');
        if (array_key_exists($id, $parents) && $parents[$id] == $parentId)
        {
            unset($parents[$id]);
        }
        $parents = array_merge($parents, $parentsParent);
        if (in_array($parentId, $parents) || in_array($id, $parents))
        {
            return false;
        }
        return true;
    }

}
