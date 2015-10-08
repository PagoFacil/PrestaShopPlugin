<?php
/**
 * @since 1.5.0
 */

class PagofacilcashConfirmModuleFrontController extends ModuleFrontController
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
        $customer = new Customer($cart->id_customer);
        
        
        
        session_start();
        
        $url = _PS_BASE_URL_.__PS_BASE_URI__ . '/index.php?controller=order-confirmation&id_cart='
                    . (int) $cart->id . '&id_module=' . (int) $this->module->id
                    . '&id_order=' . $this->module->currentOrder . '&key='
                    . $customer->secure_key;
        
                
        
        $this->context->smarty->assign(array(
            'transaction' => $_SESSION['transaction']['charge'],
            'dateEx' => date('d-m-Y', strtotime($_SESSION['transaction']['charge']['expiration_date'])),
            'url' => $url
        ));
        
       
        $this->setTemplate('confirm.tpl');
    }
    
}
