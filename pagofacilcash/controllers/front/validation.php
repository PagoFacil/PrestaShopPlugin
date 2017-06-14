<?php
/**
 * @since 1.5.0
 */
class PagofacilcashValidationModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $cart = $this->context->cart;

        if ($cart->id_customer == 0
            || $cart->id_address_delivery == 0
            || $cart->id_address_invoice == 0
            || !$this->module->active
        ) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        // Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
        $authorized = false;
        foreach (Module::getPaymentModules() as $module)
        {
            if ($module['name'] == 'pagofacilcash')
            {
                $authorized = true;
                break;
            }
        }
        
        if (!$authorized)
        {
            die($this->module->l(
                'Este m&eacute;todo de pago no est&acute; disponible.'
                , 'validation'
            ));
        }

        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer))
        {
            Tools::redirect('index.php?controller=order&step=1');
        }

        // validacion        
        $arreglo_errores = array();
        $arreglo_validacion = array(
            'nombre' => array(
                'message' => 'Debe capturar el nombre'
            )
            ,'apellidos' => array(
                'message' => 'Debe capturar los apellidos'
            )
            ,'email' => array(
                'message' => 'Debe capturar el email'
            )
        );
        foreach ($arreglo_validacion as $key => $item)
        {
            if (trim(Tools::getValue($key)) == '')
            {
                array_push($arreglo_errores, $item['message']);
            }
        }
        if (count($arreglo_errores) > 0) {
            session_start();
            $_SESSION['errores'] = $arreglo_errores;
            Tools::redirect($this->context->link->getModuleLink('pagofacilcash', 'payment'));
        }
        
        
        $currency = $this->context->currency;
        $total = (float) $cart->getOrderTotal(true, Cart::BOTH);
        
        
        $transaction = array(
           
            'branch_key'	=> Configuration::get('PFC_API_BRANCH')
            ,'user_key'		=> Configuration::get('PFC_API_USER')
            ,'order_id'		=> $cart->id
            ,'product' 		=> Configuration::get('PFC_CONCEPTO')
            ,'amount'		=> $total
            ,'store_code'	=> Tools::getValue('tienda')
            ,'customer'		=> Tools::getValue('nombre') . ' ' . Tools::getValue('apellidos') 
            ,'email'		=> Tools::getValue('email')
        );

        // construccion de la peticion
        $url = 'https://www.pagofacil.net/ws/public/cash/charge';
        if ( Configuration::get('PFC_ENVIRONMENT') == 1 ) {
            $url = 'https://api.pagofacil.net/cash/charge';
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, count($transaction));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $transaction);
        // Blindly accept the certificate
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);
        
        // tratamiento de la respuesta del servicio
        if (($json = json_decode($response, true)) === NULL)
        {
            session_start();
            $_SESSION['errores'] = array(
                $response == NULL
                ? 'Sin respuesta del servicio'
                : 'Respuesta del servicio: '.$response
            );
            Tools::redirect($this->context->link->getModuleLink('pagofacilcash', 'payment'));
        }
        
        	
		//die(print_r($response, true));

		if( isset($json['error']) && $json['error'] == 0  and isset($json['charge']) ){
			
			try
            {
                $this->module->validateOrder(
                    (int)$cart->id, 1, $total, $this->module->displayName,
                    NULL, array(), (int) $currency->id, false, $customer->secure_key
                );
                
                
                session_start();
                $_SESSION['transaction'] = $json;
                
                Tools::redirect( $this->context->link->getModuleLink('pagofacilcash', 'confirm') );

                /*Tools::redirect(
	                
	                
	                
	                
                    'index.php?controller=order-confirmation&id_cart='
                    . (int) $cart->id . '&id_module=' . (int) $this->module->id
                    . '&id_order=' . $this->module->currentOrder . '&key='
                    . $customer->secure_key
                );*/
            }
            catch (Exception $error)
            {
                session_start();
                $_SESSION['errores'] = array($error->getMessage());
                Tools::redirect($this->context->link->getModuleLink('pagofacilcash', 'payment'));
            }

		}else{
			
			
			session_start();
            $_SESSION['errores'] = array(
                'Transaction Failed.'
                ,'Respuesta del servicio: '.$json['message']
            );
            Tools::redirect($this->context->link->getModuleLink('pagofacilcash', 'payment'));
            
            
		}
        
    }
    
}