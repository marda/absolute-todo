<?php

namespace Absolute\Module\Todo\Presenter;

use Nette\Http\Response;
use Nette\Application\Responses\JsonResponse;

class LinkPresenter extends TodoBasePresenter
{

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
                        $this->_getLinkRequest($resourceId, $subResourceId);
                    }
                    else
                    {
                        $this->_getLinkListRequest($resourceId);
                    }
                }
                break;
            case 'POST':
                $this->_postLinkRequest($resourceId, $subResourceId);
                break;
            case 'PUT':
                $this->_putLinkRequest($resourceId, $subResourceId);
                break;
            case 'DELETE':
                $this->_deleteLinkRequest($resourceId, $subResourceId);
            default:
                break;
        }
        $this->sendResponse(new JsonResponse(
                $this->jsonResponse->toJson(), "application/json;charset=utf-8"
        ));
    }

    private function _getLinkListRequest($idTodo)
    {
        if ($this->todoManager->canUserView($idTodo, $this->user->id))
        {
            $ret = $this->todoManager->getTodoLinkList($idTodo);
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

    private function _getLinkRequest($idTodo, $linkId)
    {
        if ($this->todoManager->canUserView($idTodo, $this->user->id))
        {
            $ret = $this->todoManager->getTodoLinkItem($idTodo, $linkId);
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

    private function _postLinkRequest($urlId, $urlId2)
    {
        $post = json_decode($this->httpRequest->getRawBody(), true);
        $type = isset($post["type"]) ? $post["type"] : 0;
        if ($this->todoManager->canUserEdit($urlId, $this->user->id))
        {
            $ret = $this->todoManager->linkTodoCreate($urlId, $urlId2, $type);
            if (!$ret)
                $this->httpResponse->setCode(Response::S500_INTERNAL_SERVER_ERROR);
            else
                $this->httpResponse->setCode(Response::S201_CREATED);
        }else
        {
            $this->httpResponse->setCode(Response::S403_FORBIDDEN);
        }
    }

    private function _putLinkRequest($urlId, $urlId2)
    {
        $post = json_decode($this->httpRequest->getRawBody(), true);
        if ($this->todoManager->canUserEdit($urlId, $this->user->id))
        {
            if (isset($post["type"]))
            {
                $ret = $this->todoManager->linkTodoUpdate($urlId, $urlId2, $post["type"]);
                if (!$ret)
                    $this->httpResponse->setCode(Response::S500_INTERNAL_SERVER_ERROR);
                else
                    $this->httpResponse->setCode(Response::S201_CREATED);
            }
            else
                $this->httpResponse->setCode(Response::S400_BAD_REQUEST);
        }else
        {
            $this->httpResponse->setCode(Response::S403_FORBIDDEN);
        }
    }

    private function _deleteLinkRequest($urlId, $urlId2)
    {
        if ($this->todoManager->canUserEdit($urlId, $this->user->id))
        {
            $ret = $this->todoManager->linkTodoDelete($urlId, $urlId2);
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
