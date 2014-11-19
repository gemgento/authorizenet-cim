<?php

class ParadoxLabs_AuthorizeNetCim_Model_Mysql4_Card extends Mage_Core_Model_Mysql4_Abstract
{
	public function _construct()
	{
		$this->_init('authnetcim/card', 'id');
	}
}
