<?php
/**
 * @since 1.5.0
 */

class PagofacilPaymentModuleFrontController extends ModuleFrontController
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

        $arreglo_meses = array();

        for ($i=1; $i <= 12; $i++)
        {
            $arreglo_meses[] = sprintf("%02s", $i);
        }

        $arreglo_anyos = array();
        $anyo_actual = date("Y", time());
        for ($i=0; $i < 12; $i++)
        {
            $arreglo_anyos[] = substr($anyo_actual + $i, -2);
        }

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

        $this->context->smarty->assign(array(
            'nbProducts' => $cart->nbProducts()            
            ,'monto' => $cart->getOrderTotal(true, Cart::BOTH)
            ,'this_path' => $this->module->getPathUri()
            ,'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->module->name.'/'
            ,'nombre' => $client->firstname
            ,'apellidos' => $client->lastname
            ,'cp' => $invoice_address->postcode
            ,'monto' => $cart->getOrderTotal(true, Cart::BOTH)            
            ,'email' => $client->email
            ,'telefono' => $invoice_address->phone
			,'celular' => $invoice_address->phone_mobile
            ,'calleyNumero' => $invoice_address->address1
			,'colonia' => ''
            ,'municipio' => $invoice_address->city
            ,'estado' => $state->name
            ,'pais' => $invoice_address->country
            ,'anyos' => $arreglo_anyos
            ,'meses' => $arreglo_meses
            ,'errores' => $arreglo_errores
        ));
        $this->setTemplate('payment_execution.tpl');
    }
    
}
