<?php

class ParadoxLabs_AuthorizeNetCim_Model_Mysql4_Card_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
	public function _construct()
	{
		$this->_init('authnetcim/card');
	}
}
