<?php

namespace Absolute\Module\Todo\Presenter;

use Nette\Http\Response;
use Nette\Application\Responses\JsonResponse;
use Absolute\Core\Presenter\BaseRestPresenter; 

class DefaultPresenter extends TodoBasePresenter {

    /** @var \Absolute\Module\Label\Manager\LabelManager @inject */
    public $labelManager;

    public function startup() {
        parent::startup();
    }

    public function renderLabel($urlId, $urlId2) {
        switch ($this->httpRequest->getMethod()) {
            case 'GET':
                if (!isset($urlId)) 
                    $this->httpResponse->setCode(Response::S400_BAD_REQUEST);
                 else {
                    if (isset($urlId2)) {
                        $this->_getTodoLabelRequest($urlId, $urlId2);
                    } else {
                        $this->_getTodoLabelListRequest($urlId);
                    }
                }
                break;
            case 'POST':
                $this->_postTodoLabelRequest($urlId, $urlId2);
                break;
            case 'DELETE':
                $this->_deleteTodoLabelRequest($urlId, $urlId2);
            default:
                break;
        }
        $this->sendResponse(new JsonResponse(
                $this->jsonResponse->toJson(), "application/json;charset=utf-8"
        ));
    }
    //Todo
    private function _getTodoLabelListRequest($idTodo) {
        $todosList = $this->labelManager->getTodoList($idTodo);
        if(!$todosList){
            $this->httpResponse->setCode(Response::S404_NOT_FOUND);
        }else{
            $this->jsonResponse->payload = $todosList;
            $this->httpResponse->setCode(Response::S200_OK);
            
        }
    }

    private function _getTodoLabelRequest($todoId, $labelId) {
        $ret=$this->labelManager->getTodoItem($todoId,$labelId);
        if(!$ret){
            $this->httpResponse->setCode(Response::S404_NOT_FOUND);
        }else{
            $this->jsonResponse->payload = $ret;
            $this->httpResponse->setCode(Response::S200_OK);
            
        }
    }

    private function _postTodoLabelRequest($urlId, $urlId2) {
        if(!isset($urlId)||!isset($urlId2)){
            $this->httpResponse->setCode(Response::S400_BAD_REQUEST);
            return;
        }
        $ret = $this->labelManager->labelTodoCreate($urlId, $urlId2);
        if (!$ret) {
            $this->jsonResponse->payload = [];
            $this->httpResponse->setCode(Response::S500_INTERNAL_SERVER_ERROR);
        } else {
            $this->jsonResponse->payload = [];
            $this->httpResponse->setCode(Response::S201_CREATED);
        }
    }

    private function _deleteTodoLabelRequest($urlId, $urlId2) {
        if(!isset($urlId)||!isset($urlId2)){
            $this->httpResponse->setCode(Response::S400_BAD_REQUEST);
            return;
        }
        $ret = $this->labelManager->labelTodoDelete($urlId, $urlId2);
        if (!$ret) {
            $this->jsonResponse->payload = [];
            $this->httpResponse->setCode(Response::S404_NOT_FOUND);
        } else {
            $this->jsonResponse->payload = [];
            $this->httpResponse->setCode(Response::S200_OK);
        }
    }

}
