<?php

if (!defined('_PS_VERSION_'))
{
	die('No direct script access');
}

/**
 * Class MollieReturnModuleFrontController
 * @method setTemplate
 * @property mixed context
 * @property Mollie module
 */

class PagofacilcashWebhookModuleFrontController extends ModuleFrontController
{
	/**
	 * @see FrontController::initContent()
	 */
	public function initContent()
	{
		parent::initContent();

		echo $this->_executeWebhook();
		exit;
	}


	/**
	 * @return string
	 */
	protected function _executeWebhook()
	{

		$order_id = Tools::getValue('customer_order');

		if (empty($order_id)){
			return 'NO ID';
		}
		
		
		$status = Tools::getValue('status');
		
		
		if (empty($status) || $status != 4 ){
			return 'NO STATUS';
		}
		
		$amount = Tools::getValue('status');
		
		
		if (empty($amount) ){
			return 'NO AMOUNT';
		}
		
		
		$order = Order::getOrderByCartId($order_id);
		
		if ($order)
		{
			$this->module->setOrderStatus($order, 12);
			
		}else{
			return 'NO ORDER';
		}
		
		
		return 'OK';

	}

}
