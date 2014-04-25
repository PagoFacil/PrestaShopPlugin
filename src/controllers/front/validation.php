<?php
/*
* 2007-2012 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2012 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

/**
 * @since 1.5.0
 */
class PagofacilValidationModuleFrontController extends ModuleFrontController
{
	public function postProcess()
	{
		$cart = $this->context->cart;

		if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active)
			Tools::redirect('index.php?controller=order&step=1');

		// Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
		$authorized = false;
		foreach (Module::getPaymentModules() as $module)
			if ($module['name'] == 'pagofacil')
			{
				$authorized = true;
				break;
			}

		if (!$authorized)
			die($this->module->l('Este método de pago no está disponible.', 'validation'));

		$customer = new Customer($cart->id_customer);

		if (!Validate::isLoadedObject($customer))
			Tools::redirect('index.php?controller=order&step=1');

		$currency = $this->context->currency;
		$total = (float)$cart->getOrderTotal(true, Cart::BOTH);

		//Realizar el pago con pagofacil
		$json_to_send = array();
		$json_to_send['jsonrpc'] = '2.0';
		$json_to_send['method'] = 'transaccion';
		$json_to_send['id'] = $cart->id;
		$json_to_send['params'] = array('data' => array());

		$json_to_send['params']['data']['nombre'] = $_POST['nombre'];
		$json_to_send['params']['data']['apellidos'] = $_POST['apellidos'];
		$json_to_send['params']['data']['numeroTarjeta'] = $_POST['numeroTarjeta'];
		$json_to_send['params']['data']['mesExpiracion'] = $_POST['mesExpiracion'];
		$json_to_send['params']['data']['anyoExpiracion'] = $_POST['anyoExpiracion'];
		$json_to_send['params']['data']['cvt'] = $_POST['cvt'];
		$json_to_send['params']['data']['cp'] = $_POST['cp'];
		$json_to_send['params']['data']['email'] = $_POST['email'];
		$json_to_send['params']['data']['telefono'] = $_POST['telefono'];
		$json_to_send['params']['data']['celular'] = $_POST['celular'];
		$json_to_send['params']['data']['calleyNumero'] = $_POST['calleyNumero'];
		$json_to_send['params']['data']['colonia'] = $_POST['colonia'];
		$json_to_send['params']['data']['municipio'] = $_POST['municipio'];
		$json_to_send['params']['data']['estado'] = $_POST['estado'];
		$json_to_send['params']['data']['pais'] = $_POST['pais'];
		$json_to_send['params']['data']['monto'] = $total;
		$json_to_send['params']['data']['idSucursal'] = $_POST['idSucursal'];
		$json_to_send['params']['data']['idUsuario'] = $_POST['idUsuario'];
		$json_to_send['params']['data']['idServicio'] = $_POST['idServicio'];
		
		$url_to_send = "https://www.pagofacil.net/ws/public/Wsjtransaccion/";
		$json_to_send = json_encode($json_to_send);

		$ch = curl_init($url_to_send);
		curl_setopt($_h, CURLOPT_DNS_USE_GLOBAL_CACHE, false ); 
		curl_setopt($_h, CURLOPT_DNS_CACHE_TIMEOUT, 2 );                                                                      
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
		curl_setopt($ch, CURLOPT_POSTFIELDS, $json_to_send);                                                                  
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                  
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
		    'Content-Type: application/json',                                                                                
		    'Content-Length: ' . strlen($json_to_send))                                                                       
		);
		$result = curl_exec($ch);
		curl_close($ch);
		$result = json_decode($result,TRUE);

		//validar pagofacil
		if($result['result']['autorizado'] == 1){
			$this->module->validateOrder((int)$cart->id, 14, $total, $this->module->displayName, NULL, $mailVars, (int)$currency->id, false, $customer->secure_key);
			Tools::redirect('index.php?controller=order-confirmation&id_cart='.(int)$cart->id.'&id_module='.(int)$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
		} else {
			$arreglo_errores = array();
			$res_error = "\n\n".date("r")."\n";	
			$res_error .= "Texto: ".$result['result']['texto']."\n";
			if(is_array($result['result']['error'])){
				foreach ($result['result']['error'] as $key => $value) {
					$arreglo_errores[$key] = $value;
					$res_error .= "{$key}: {$value}\n";
				}
			} else {
				$res_error .= "Error: ".$result['result']['error'];	
			}
			
			file_put_contents($_SERVER['DOCUMENT_ROOT'].'/modules/pagofacil/log.txt', $res_error, FILE_APPEND);
			session_start();
			$_SESSION['errores'] = $arreglo_errores;
			header("Location: /module/pagofacil/payment");
			//$this->setTemplate('error.tpl');
			//echo "Ocurrió un error al procesar su pago, por favor contacte al sitio";
		}
	}
}
