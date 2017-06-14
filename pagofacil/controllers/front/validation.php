<?php
/**
 * @since 1.5.0
 */
class PagofacilValidationModuleFrontController extends ModuleFrontController
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
            if ($module['name'] == 'pagofacil')
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
            ,'numeroTarjeta' => array(
                'message' => 'Debe capturar el n&uacute;mero de tarjeta'
            )
            ,'cvt' => array(
                'message' => 'Debe capturar el cvt'
            )
            ,'cp' => array(
                'message' => 'Debe capturar el cp'
            )
            ,'mesExpiracion' => array(
                'message' => 'Debe seleccionar el mes de expiraci&oacute;n'
            )
            ,'anyoExpiracion' => array(
                'message' => 'Debe seleccionar el a&ntilde;o de expiraci&oacute;n'
            )
            ,'email' => array(
                'message' => 'Debe capturar el email'
            )
            ,'telefono' => array(
                'message' => 'Debe capturar el tel&eacute;fono'
            )
            ,'celular' => array(
                'message' => 'Debe capturar el celular'
            )
            ,'calleyNumero' => array(
                'message' => 'Debe capturar la calle y n&uacute;mero'
            )
            //,'colonia' => array(
            //    'message' => 'Debe capturar la colonia'
            //)
            ,'municipio' => array(
                'message' => 'Debe capturar el municipio'
            )
            ,'estado' => array(
                'message' => 'Debe capturar el estado'
            )
            ,'pais' => array(
                'message' => 'Debe capturar el pais'
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
            Tools::redirect($this->context->link->getModuleLink('pagofacil', 'payment'));
        }

        $currency = $this->context->currency;
        $total = (float) $cart->getOrderTotal(true, Cart::BOTH);

        //Realizar el pago con pagofacil
        $data = array(
            'idServicio' => urlencode('3')
            , 'idSucursal' => urlencode(Configuration::get('PF_API_BRANCH'))
            , 'idUsuario' => urlencode(Configuration::get('PF_API_USER'))
            , 'nombre' => urlencode(Tools::getValue('nombre'))
            , 'apellidos' => urlencode(Tools::getValue('apellidos'))
            , 'numeroTarjeta' => urlencode(Tools::getValue('numeroTarjeta'))
            , 'cvt' => urlencode(Tools::getValue('cvt'))
            , 'cp' => urlencode(Tools::getValue('cp'))
            , 'mesExpiracion' => urlencode(Tools::getValue('mesExpiracion'))
            , 'anyoExpiracion' => urlencode(Tools::getValue('anyoExpiracion'))
            , 'monto' => urlencode($total)
            , 'email' => urlencode(Tools::getValue('email'))
            , 'telefono' => urlencode(Tools::getValue('telefono'))
            , 'celular' => urlencode(Tools::getValue('celular'))
            , 'calleyNumero' => urlencode(Tools::getValue('calleyNumero'))
            , 'colonia' => urlencode( ( trim(Tools::getValue('colonia')) == '' ? 'S/D' : trim(Tools::getValue('colonia')) ) )
            , 'municipio' => urlencode(Tools::getValue('municipio'))
            , 'estado' => urlencode(Tools::getValue('estado'))
            , 'pais' => urlencode(Tools::getValue('pais'))
            , 'idPedido' => urlencode($cart->id)
            , 'ip' => urlencode(Tools::getRemoteAddr())
            , 'httpUserAgent' => urlencode($_SERVER['HTTP_USER_AGENT'])
        );
        if (Configuration::get('PF_NO_MAIL') == '1')
        {
            $data = array_merge($data, array('noMail' => 1));
        }
        if (Configuration::get('PF_EXCHANGE') != 'MXN')
        {
            $data = array_merge($data, array('divisa' => Configuration::get('PF_EXCHANGE')));
        }
        if (Configuration::get('PF_INSTALLMENTS') == '1')
        {
            if (Tools::getValue('msi') != '' && Tools::getValue('msi') != '00')
            {
                $data = array_merge(
                    $data
                    ,array('plan' => 'MSI', 'mensualidades' => Tools::getValue('msi'))
                );
            }
        }

        // construccion de la peticion
        $url = 'https://api.pagofacil.net/Wsrtransaccion/index/format/json';
        if (Configuration::get('PF_ENVIRONMENT') == '2') {
            $url = 'https://www.pagofacil.net/ws/public/Wsrtransaccion/index/format/json';
        }
        $url .= '/?method=transaccion';
        foreach ($data as $key => $valor) {
            $url .= "&data[$key]=$valor";
        }
        //die($this->module->l($url, 'validation'));
        // consumo del servicio
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
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
            Tools::redirect($this->context->link->getModuleLink('pagofacil', 'payment'));
        }
        if (!isset($json['WebServices_Transacciones']['transaccion']))
        {
            session_start();
            $_SESSION['errores'] = array(
                'No existe WebServices_Transacciones - transaccion'
                ,'Respuesta del servicio: '.$response
            );
            Tools::redirect($this->context->link->getModuleLink('pagofacil', 'payment'));
        }
        
        $transaction = $json['WebServices_Transacciones']['transaccion'];        
        if (isset($transaction['autorizado']) && $transaction['autorizado'] == '1')
        {
            try
            {
                $this->module->validateOrder(
                    (int)$cart->id, 2, $total, $this->module->displayName,
                    NULL, array(), (int) $currency->id, false, $customer->secure_key
                );

                Tools::redirect(
                    'index.php?controller=order-confirmation&id_cart='
                    . (int) $cart->id . '&id_module=' . (int) $this->module->id
                    . '&id_order=' . $this->module->currentOrder . '&key='
                    . $customer->secure_key
                );
            }
            catch (Exception $error)
            {
                session_start();
                $_SESSION['errores'] = array($error->getMessage());
                Tools::redirect($this->context->link->getModuleLink('pagofacil', 'payment'));
            }
        }
        else
        {
            $arreglo_errores = array();
            if (is_array($transaction['error']))
            {
                foreach ($transaction['error'] as $key => $value)
                {
                    $arreglo_errores[$key] = $value;
                }
            }
            else
            {
                $arreglo_errores[] = $transaction['texto'];
            }
            session_start();
            $_SESSION['errores'] = $arreglo_errores;
            Tools::redirect($this->context->link->getModuleLink('pagofacil', 'payment'));
        }
    }
    
}