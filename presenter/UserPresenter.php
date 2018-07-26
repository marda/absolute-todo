<?php

namespace Absolute\Module\Todo\Presenter;

use Nette\Http\Response;
use Nette\Application\Responses\JsonResponse;

class UserPresenter extends TodoBasePresenter
{

    /** @var \Absolute\Module\User\Manager\UserManager @inject */
    public $userManager;

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
                        $this->_getUserRequest($resourceId, $subResourceId);
                    }
                    else
                    {
                        $this->_getUserListRequest($resourceId);
                    }
                }
                break;
            case 'POST':
                $this->_postUserRequest($resourceId, $subResourceId);
                break;
            case 'DELETE':
                $this->_deleteUserRequest($resourceId, $subResourceId);
            default:
                break;
        }
        $this->sendResponse(new JsonResponse(
                $this->jsonResponse->toJson(), "application/json;charset=utf-8"
        ));
    }

    private function _getUserListRequest($resourceId)
    {
        if ($this->todoManager->canUserView($resourceId, $this->user->id))
        {
            $ret = $this->userManager->getTodoList($resourceId);
            if (!$ret)
                $this->httpResponse->setCode(Response::S404_NOT_FOUND);
            else
            {
                $this->jsonResponse->payload = array_map(function($n)
                {
                    return $n->toJson();
                }, $ret);
                $this->httpResponse->setCode(Response::S200_OK);
            }
        }
        else
        {
            $this->httpResponse->setCode(Response::S403_FORBIDDEN);
        }
    }

    private function _getUserRequest($resourceId, $subResourceId)
    {
        if ($this->todoManager->canUserView($resourceId, $this->user->id))
        {
            $ret = $this->userManager->getTodoItem($resourceId, $subResourceId);
            if (!$ret)
                $this->httpResponse->setCode(Response::S404_NOT_FOUND);
            else
            {
                $this->jsonResponse->payload = $ret->toJson();
                $this->httpResponse->setCode(Response::S200_OK);
            }
        }
        else
            $this->httpResponse->setCode(Response::S403_FORBIDDEN);
    }

    private function _postUserRequest($urlId, $urlId2)
    {
        if (!isset($urlId) || !isset($urlId2))
        {
            $this->httpResponse->setCode(Response::S400_BAD_REQUEST);
            return;
        }
        if ($this->todoManager->canUserEdit($urlId, $this->user->id))
        {
            $ret = $this->userManager->userTodoCreate($urlId, $urlId2);
            if (!$ret)
                $this->httpResponse->setCode(Response::S500_INTERNAL_SERVER_ERROR);
            else
                $this->httpResponse->setCode(Response::S201_CREATED);
        }else
        {
            $this->httpResponse->setCode(Response::S403_FORBIDDEN);
        }
    }

    private function _deleteUserRequest($urlId, $urlId2)
    {
        if (!isset($urlId) || !isset($urlId2))
        {
            $this->httpResponse->setCode(Response::S400_BAD_REQUEST);
            return;
        }

        if ($this->todoManager->canUserEdit($urlId, $this->user->id))
        {
            $ret = $this->userManager->userTodoDelete($urlId, $urlId2);
            if (!$ret)
                $this->httpResponse->setCode(Response::S404_NOT_FOUND);
            else
                $this->httpResponse->setCode(Response::S200_OK);
        }else
        {
            $this->httpResponse->setCode(Response::S403_FORBIDDEN);
        }
    }

}
