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
		curl_setopt($ch, CURLOPT_URL, "http://api.compropago.com/v1/providers/true");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERPWD, "pk_live_508992456a45414a9");
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		$output = curl_exec($ch);
		curl_close($ch);
		
		$tmp_store_codes = json_decode($output);
		
		$store_codes = array();
		
		foreach($tmp_store_codes as $store ){
			$store_codes[$store->internal_name] = $store->name;
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
