<?php
class Tx_ZfextSample_Pi1_IndexController extends Zend_Controller_Action
{
	private static $count = 0;
	
	public function indexAction()
	{
		$this->view->forward = true;
		/* This is possible too, but not yet tested with realurl:
		$this->_helper->redirector->setPrependBase(false);
		$this->_redirect($this->_helper->url->url(array('action'=>'test')));
		//or $this->_helper->redirector->simple('test');
		*/
		$this->_forward('test');
	}
	
	public function testAction()
	{
		$table = new Tx_ZfextSample_Pi1_Model_DbTable_Pages();
		$this->view->pages = $table->fetchAll('deleted=0',null,20);
	}
}