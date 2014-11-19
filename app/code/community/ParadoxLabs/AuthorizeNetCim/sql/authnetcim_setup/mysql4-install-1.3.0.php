<?php
/**
 * Authorize.Net CIM - Installation script.
 *
 * Paradox Labs, Inc.
 * http://www.paradoxlabs.com
 * 717-431-3330
 *
 * Having a problem with the plugin?
 * Not sure what something means?
 * Need custom development?
 * Give us a call!
 *
 * @category	ParadoxLabs
 * @package		ParadoxLabs_AuthorizeNetCim
 * @author		Ryan Hoerr <ryan@paradoxlabs.com>
 */


$this->startSetup();

$this->addAttribute('customer', 'authnetcim_profile_id', array(
	'label' 			=> 'Authorize.net CIM: Profile ID',
	'type' 				=> 'varchar',
	'input' 			=> 'text',
	'default'           => '',
	'position' 			=> 70,
	'visible'           => true,
	'required'          => false,
	'user_defined'      => true,
	'searchable'        => false,
	'filterable'        => false,
	'comparable'        => false,
	'visible_on_front'  => false,
	'unique'            => false
));


$table = $this->getTable('authnetcim/card');

$this->run("CREATE TABLE IF NOT EXISTS {$table} (
	id int auto_increment primary key,
	customer_id int,
	profile_id int,
	payment_id int,
	added varchar(255)
);");


$this->endSetup();

Mage::log( 'Authorize.net CIM - Payment Module Installed', null, 'authnetcim.log' );
