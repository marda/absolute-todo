<?php

namespace Absolute\Module\Todo\Presenter;

use Nette\Http\Response;
use Nette\Application\Responses\JsonResponse;
use Absolute\Core\Presenter\BaseRestPresenter;

class DefaultPresenter extends TodoBasePresenter
{

    /** @var \Absolute\Module\Todo\Manager\TodoCRUDManager @inject */
    public $todoCRUDManager;

    /** @var \Absolute\Module\Todo\Manager\TodoManager @inject */
    public $todoManager;

    public function startup()
    {
        parent::startup();
    }

    public function renderDefault($resourceId)
    {
        switch ($this->httpRequest->getMethod())
        {
            case 'GET':
                if ($resourceId != null)
                {
                    $this->_getRequest($resourceId);
                }
                else
                {
                    $this->_getListRequest($this->getParameter('offset'), $this->getParameter('limit'));
                }
                break;
            case 'POST':
                $this->_postRequest();
                break;
            case 'PUT':
                $this->_putRequest($resourceId);
                break;
            case 'DELETE':
                $this->_deleteRequest($resourceId);
            default:

                break;
        }
        $this->sendResponse(new JsonResponse(
                $this->jsonResponse->toJson(), "application/json;charset=utf-8"
        ));
    }

    private function _getRequest($id)
    {
        if ($this->todoManager->canUserView($id, $this->user->id))
        {
            $label = $this->todoManager->getById($id);
            if (!$label)
            {
                $this->httpResponse->setCode(Response::S404_NOT_FOUND);
                return;
            }
            $this->jsonResponse->payload = $label->toJson();
            $this->httpResponse->setCode(Response::S200_OK);
        }
        else
            $this->httpResponse->setCode(Response::S403_FORBIDDEN);
    }

    private function _getListRequest($offset, $limit)
    {
        //TODO
        $labels = $this->todoManager->getList($this->user->id,$offset, $limit);
        $this->jsonResponse->payload = array_map(function($n)
        {
            return $n->toJson();
        }, $labels);
        $this->httpResponse->setCode(Response::S200_OK);
    }

    private function _putRequest($id)
    {
        $post = json_decode($this->httpRequest->getRawBody(), true);

        if ($id == null)
            $this->httpResponse->setCode(Response::S400_BAD_REQUEST);
        else if ($this->todoManager->canUserEdit($id, $this->user->id))
        {
            $ret = $this->todoCRUDManager->updateWithArray($id, $post);
            if ($ret)
                $this->httpResponse->setCode(Response::S200_OK);
            else
                $this->httpResponse->setCode(Response::S500_INTERNAL_SERVER_ERROR);
        }
        else
        {
            $this->jsonResponse->payload = [];
            $this->httpResponse->setCode(Response::S403_FORBIDDEN);
        }
    }

    private function _postRequest()
    {
        $post = json_decode($this->httpRequest->getRawBody(), true);
        $ret = $this->todoCRUDManager->create(
        $this->user->id, $post['parent_todo_id'], $post['title'], $post['note'], $post['start_date'], $post['due_date'], $post['color'], $post['budget'], $post['proposal_budget']);

        if (!$ret)
            $this->httpResponse->setCode(Response::S500_INTERNAL_SERVER_ERROR);
        else
            $this->httpResponse->setCode(Response::S201_CREATED);
    }

    private function _deleteRequest($id)
    {
        if ($this->todoManager->canUserEdit($id, $this->user->id) || true)
        {
            $ret = $this->todoCRUDManager->delete($id);
            if ($ret)
                $this->httpResponse->setCode(Response::S200_OK);
            else
                $this->httpResponse->setCode(Response::S404_NOT_FOUND);
        }
        else
        {
            $this->httpResponse->setCode(Response::S403_FORBIDDEN);
        }
    }

}
