<?php

namespace Absolute\Module\Todo\Classes;

class Todo
{

  private $id;
  private $title;
  private $note;
  private $startDate;
  private $dueDate;
  private $starred;
  private $priority;
  private $done;
  private $deleted;
  private $created;
  private $parentId;
  private $userId;
  private $color;
  private $budget;
  private $proposalBudget;

  private $users = [];
  private $teams = [];
  private $categories = [];
  private $labels = [];
  private $files = [];

  private $editable = true;
  private $projectId = null;

  public function __construct($id, $userId, $parentId, $title, $note, $color, $startDate, $dueDate, $starred, $priority, $done, $deleted, $created, $budget, $proposalBudget)
  {
    $this->id = $id;
    $this->userId = $userId;
    $this->parentId = $parentId;
    $this->title = $title;
    $this->note = $note;
    $this->color = $color;
    $this->startDate = $startDate;
    $this->created = $created;
    $this->starred = ($starred) ? true : false;
    $this->priority = ($priority) ? true : false;
    $this->done = ($done) ? true : false;
    $this->deleted = ($deleted) ? true : false;
    $this->dueDate = $dueDate;
    $this->budget = $budget;
    $this->proposalBudget = $proposalBudget;
  }

  public function getId()
  {
    return $this->id;
  }

  public function getUserId()
  {
    return $this->userId;
  }

  public function getParentId()
  {
    return $this->parentId;
  }

  public function getColor()
  {
    return $this->color;
  }

  public function getTitle()
  {
    return $this->title;
  }

  public function getNote()
  {
    return $this->note;
  }

  public function getStartDate()
  {
    return $this->startDate;
  }

  public function getDueDate()
  {
    return $this->dueDate;
  }

  public function getStarred()
  {
    return $this->starred;
  }

  public function getCreated()
  {
    return $this->created;
  }

  public function getDone()
  {
    return $this->done;
  }

  public function getPriority()
  {
    return $this->priority;
  }

  public function getDeleted()
  {
    return $this->deleted;
  }

  public function getLabels()
  {
    return $this->labels;
  }

  public function getFiles()
  {
    return $this->files;
  }

  public function getProjectId()
  {
    return $this->projectId;
  }

  public function getUsers()
  {
    return $this->users;
  }

  public function getTeams()
  {
    return $this->teams;
  }

  public function getCategories()
  {
    return $this->categories;
  }

  public function getBudget()
  {
    return $this->budget;
  }

  public function getProposalBudget()
  {
    return $this->proposalBudget;
  }

  // IS?

  public function isEditable()
  {
    return ($this->editable) ? true : false;
  }

  // SETTERS

  public function setEditable($editable)
  {
    $this->editable = $editable;
  }

  public function setProjectId($projectId)
  {
    $this->projectId = $projectId;
  }

  // ADDERS

  public function addUser($user)
  {
    $this->users[$user->id] = $user;
  }

  public function addTeam($team)
  {
    $this->teams[$team->getId()] = $team;
  }

  public function addCategory($category)
  {
    $this->categories[$category->id] = $category;
  }

  public function addLabel($label)
  {
    $this->labels[] = $label;
  }

  public function addFile($file)
  {
    $this->files[] = $file;
  }

  // OTHER METHODS

  public function toJsonGanttChart()
  {
    return array(
      "id" => $this->id,
      "text" => $this->done ? "<s>" . $this->title . "</s>" : $this->title,
      "start_date" => ($this->startDate) ? $this->startDate->format("Y-m-d H:i") : "",
      "end_date" => ($this->dueDate) ? $this->dueDate->format("Y-m-d H:i") : "",
      "parent" => $this->parentId,
      "color" => $this->color,
      "users" => array_values(array_map(function ($user) {
        return $user->toJson();
      }, $this->users)),
    );
  }

  public function toJson()
  {
    return array(
      "id" => $this->id,
      "title" => $this->title,
      "start_date" => ($this->startDate) ? $this->startDate->format("m/d/Y") : "",
      "end_date" => ($this->dueDate) ? $this->dueDate->format("m/d/Y") : "",
      "parent" => $this->parentId,
      "editable" => $this->editable,
      "note" => $this->note,
      "users" => array_values(array_map(function ($user) {
        return $user->toJson();
      }, $this->users)),
      "teams" => array_values(array_map(function ($team) {
        return $team->toJson();
      }, $this->teams)),
      "categories" => array_values(array_map(function ($category) {
        return $category->toJson();
      }, $this->categories)),
      "labels" => array_map(function ($label) {
        return $label->toJson();
      }, $this->labels),
      "files" => array_map(function ($file) {
        return $file->toJson();
      }, $this->files),
      "project_id" => $this->projectId,
      "color" => $this->color,
      "budget" => $this->budget,
      "proposal_budget" => $this->proposalBudget,
    );
  }

  // for array unique
  public function __toString()
  {
    return (string)$this->id;
  }
}
