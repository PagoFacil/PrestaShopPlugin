<?php
/**
 * @since 1.5.0
 */

class PagofacilcashPaymentModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        $this->display_column_left = false;
        $this->display_column_right = false;
        parent::initContent();

        $cart = $this->context->cart;
        $client = $this->context->customer;

        $invoice_address = new Address((int)$cart->id_address_invoice);
        $state = new State((int)$invoice_address->id_state);



        session_start();
        $arreglo_errores = array();
        if(is_array($_SESSION['errores']))
        {
            foreach ($_SESSION['errores'] as $key => $value)
            {
                $arreglo_errores[$key] = $value;
            }
        }
        unset($_SESSION['errores']);
        
        
        
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://pagofacil.net/ws/public/index.php/cash/Rest_Conveniencestores");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$output = curl_exec($ch);
	$info = curl_getinfo($ch);
	curl_close($ch);
	$response = json_decode($output);
	if ($info['http_code'] != 200 || !is_object($response) || !property_exists($response, 'records')) {
		$store_codes_list = array();
	} else {
		$store_codes_list = $response->records;
	}
		
		$store_codes = array();
		
		foreach($store_codes_list as $store ){
			$store_codes[$store->code] = $store->name;
		}

        
        
        

        $this->context->smarty->assign(array(
            'nbProducts' => $cart->nbProducts()            
            ,'monto' => $cart->getOrderTotal(true, Cart::BOTH)
            ,'this_path' => $this->module->getPathUri()
            ,'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->module->name.'/'
            ,'nombre' => $client->firstname
            ,'apellidos' => $client->lastname
            ,'email' => $client->email
            ,'errores' => $arreglo_errores
            ,'store_codes' => $store_codes
        ));
        $this->setTemplate('payment_executions.tpl');
    }
    
}
