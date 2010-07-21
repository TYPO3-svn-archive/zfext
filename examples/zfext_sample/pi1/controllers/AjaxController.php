<?php
class Tx_ZfextSample_Pi1_AjaxController extends Zend_Controller_Action
{
    public function init()
    {
        $this->view->addHelperPath('ZendX/JQuery/View/Helper', 'ZendX_JQuery_View_Helper');
    }
    
    public function indexAction()
    {
        if ($this->_request->isXmlHttpRequest())
        {
            $this->_forward('ajax');
        }
    }
    
    public function ajaxAction()
    {
        $this->_helper->viewRenderer->setNeverRender();
        
        echo 'Hi - i am the ajax action';
    }
}