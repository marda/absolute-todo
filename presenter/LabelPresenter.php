<?php

namespace Absolute\Module\Todo\Presenter;

use Nette\Http\Response;
use Nette\Application\Responses\JsonResponse;
use Absolute\Core\Presenter\BaseRestPresenter;

class LabelPresenter extends TodoBasePresenter
{

    /** @var \Absolute\Module\Label\Manager\LabelManager @inject */
    public $labelManager;

    /** @var \Absolute\Module\Todo\Manager\TodoManager @inject */
    public $todoManager;

    public function startup()
    {
        parent::startup();
    }

    //LABEL

    public function renderDefault($resourceId, $subResourceId)
    {
        switch ($this->httpRequest->getMethod())
        {
            case 'GET':
                if (!isset($resourceId))
                    $this->httpResponse->setCode(Response::S400_BAD_REQUEST);
                else
                {
                    if (isset($subResourceId))
                    {
                        $this->_getTodoLabelRequest($resourceId, $subResourceId);
                    }
                    else
                    {
                        $this->_getTodoLabelListRequest($resourceId);
                    }
                }
                break;
            case 'POST':
                $this->_postTodoLabelRequest($resourceId, $subResourceId);
                break;
            case 'DELETE':
                $this->_deleteTodoLabelRequest($resourceId, $subResourceId);
            default:
                break;
        }
        $this->sendResponse(new JsonResponse(
                $this->jsonResponse->toJson(), "application/json;charset=utf-8"
        ));
    }

    //Todo
    private function _getTodoLabelListRequest($todoId)
    {
        if ($this->todoManager->canUserView($todoId, $this->user->id))
        {
            $todosList = $this->labelManager->getTodoList($todoId);
            if (!$todosList)
            {
                $this->httpResponse->setCode(Response::S404_NOT_FOUND);
            }
            else
            {
                $this->jsonResponse->payload = array_map(function($n)
                {
                    return $n->toJson();
                }, $todosList);
                $this->httpResponse->setCode(Response::S200_OK);
            }
        }
        else
        {
            $this->httpResponse->setCode(Response::S403_FORBIDDEN);
        }
    }

    private function _getTodoLabelRequest($todoId, $labelId)
    {
        if ($this->todoManager->canUserView($todoId, $this->user->id))
        {
            $ret = $this->labelManager->getTodoItem($todoId, $labelId);
            if (!$ret)
            {
                $this->httpResponse->setCode(Response::S404_NOT_FOUND);
            }
            else
            {
                $this->jsonResponse->payload = $ret->toJson();
                $this->httpResponse->setCode(Response::S200_OK);
            }
        }
        else
        {
            $this->httpResponse->setCode(Response::S403_FORBIDDEN);
        }
    }

    private function _postTodoLabelRequest($urlId, $urlId2)
    {
        if ($this->todoManager->canUserView($urlId, $this->user->id))
        {
            $ret = $this->labelManager->labelTodoCreate($urlId, $urlId2);
            if (!$ret)
            {
                $this->jsonResponse->payload = [];
                $this->httpResponse->setCode(Response::S500_INTERNAL_SERVER_ERROR);
            }
            else
            {
                $this->jsonResponse->payload = [];
                $this->httpResponse->setCode(Response::S201_CREATED);
            }
        }
        else
        {
            $this->httpResponse->setCode(Response::S403_FORBIDDEN);
        }
    }

    private function _deleteTodoLabelRequest($urlId, $urlId2)
    {
        if ($this->todoManager->canUserView($urlId, $this->user->id))
        {
            $ret = $this->labelManager->labelTodoDelete($urlId, $urlId2);
            if (!$ret)
            {
                $this->httpResponse->setCode(Response::S404_NOT_FOUND);
            }
            else
            {
                $this->httpResponse->setCode(Response::S200_OK);
            }
        }
        else
            $this->httpResponse->setCode(Response::S403_FORBIDDEN);
    }

}
