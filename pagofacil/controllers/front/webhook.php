<?php

if (!defined('_PS_VERSION_'))
{
	die('No direct script access');
}

/**
 * Pago Facil Webhook
 * Update Status Order
 */
class PagofacilWebhookModuleFrontController extends ModuleFrontController
{
    /**
     * Init
     * @return void 
     */
	public function initContent()
	{
		parent::initContent();
		$this->executeWebhook();
		exit();
	}

	/**
     * Execute Webhook
     * @return JSON JSON Response
     */
	protected function executeWebhook()
	{
		$orderId = Tools::getValue('customer_order');
        $status = Tools::getValue('status');

        if (empty($orderId)
            || empty($status)
            || !in_array($status, [2, 8])
        ) {
            return $this->setError();
        }
		
		$order = Order::getOrderByCartId($orderId);
        if ($order) {
            $this->setOrderStatus($order, $status);
        } else {
            return $this->setError();
        }
		return $this->setSuccess();
	}

    /**
     * Set Success Response
     */
    private function setSuccess()
    {
        echo $this->module->encode([
            'message' => 'Los datos han sido actualizados',
            'statusCode' => 200
        ]);
        return;
    }

    /**
     * Set Error Response
     */
    private function setError()
    {
        echo $this->module->encode([
            'error' => 'Values Not Found',
            'message' => 'Los parametros "customer_order", "status", "amount" no son validos',
            'statusCode' => 400
        ]);
        return;
    }

    /**
     * Update Order Status
     * @param int $orderId Order ID
     * @param int $status  Order Status
     */
	private function setOrderStatus($orderId, $status)
	{
		$history = new OrderHistory();
		$history->id_order = $orderId;
		$history->id_order_state = $status;
		$history->changeIdOrderState($status, $orderId);
		$history->addWithemail();
		
		return $history;
	}
}