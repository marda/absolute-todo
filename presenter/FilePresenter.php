<?php

namespace Absolute\Module\Todo\Presenter;

use Nette\Http\Response;
use Nette\Application\Responses\JsonResponse;
use Absolute\Core\Presenter\BaseRestPresenter;

class FilePresenter extends TodoBasePresenter
{

    /** @var \Absolute\Module\File\Manager\FileManager @inject */
    public $fileManager;

    /** @var \Absolute\Module\Todo\Manager\TodoManager @inject */
    public $todoManager;

    public function startup()
    {
        parent::startup();
    }

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
                        $this->_getFileRequest($resourceId, $subResourceId);
                    }
                    else
                    {
                        $this->_getFileListRequest($resourceId);
                    }
                }
                break;
            case 'POST':
                $this->_postFileRequest($resourceId, $subResourceId);
                break;
            case 'DELETE':
                $this->_deleteFileRequest($resourceId, $subResourceId);
            default:
                break;
        }
        $this->sendResponse(new JsonResponse(
                $this->jsonResponse->toJson(), "application/json;charset=utf-8"
        ));
    }

    private function _getFileListRequest($idTodo)
    {
        if ($this->todoManager->canUserView($idTodo, $this->user->id))
        {
            $ret = $this->fileManager->getTodoList($idTodo);
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
            $this->httpResponse->setCode(Response::S403_FORBIDDEN);
    }

    private function _getFileRequest($todoId, $teamId)
    {
        if ($this->todoManager->canUserView($todoId, $this->user->id))
        {
            $ret = $this->fileManager->getTodoItem($todoId, $teamId);
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

    private function _postFileRequest($urlId, $urlId2)
    {
        if ($this->todoManager->canUserEdit($urlId, $this->user->id))
        {
            $ret = $this->fileManager->fileTodoCreate($urlId, $urlId2);
            if (!$ret)
                $this->httpResponse->setCode(Response::S500_INTERNAL_SERVER_ERROR);
            else
                $this->httpResponse->setCode(Response::S201_CREATED);
        }else
            $this->httpResponse->setCode(Response::S403_FORBIDDEN);
    }

    private function _deleteFileRequest($urlId, $urlId2)
    {
        if ($this->todoManager->canUserEdit($urlId, $this->user->id))
        {
            $ret = $this->fileManager->fileTodoDelete($urlId, $urlId2);
            if (!$ret)
                $this->httpResponse->setCode(Response::S404_NOT_FOUND);
            else
                $this->httpResponse->setCode(Response::S200_OK);
        }else
            $this->httpResponse->setCode(Response::S403_FORBIDDEN);
    }

}
