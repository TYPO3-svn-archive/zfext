<?php
class Zfext_Module
{
    /**
     * @var Zfext_Module
     */
    protected static $_instance;

    public $extKey;

    protected $_config;

    public function callModule($moduleSignature)
    {
        if (!isset($GLOBALS['TBE_MODULES']['_configuration'][$moduleSignature])) {
			return FALSE;
		}
        if (!$GLOBALS['TBE_MODULES']['_configuration'][$moduleSignature]['zfext']) {
			return FALSE;
		}
		$this->_config = $GLOBALS['TBE_MODULES']['_configuration'];
		$this->extKey = $this->_config['extKey'];
		self::$_instance = $this;
        return true;
    }

    /**
     * @return Zfext_Module
     */
    public static function getInstance()
    {
        return self::$_instance;
    }
}