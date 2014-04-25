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

class PagofacilPaymentModuleFrontController extends ModuleFrontController
{
	public $ssl = true;

	/**
	 * @see FrontController::initContent()
	 */
	public function initContent()
	{
		$this->display_column_left = false;
		parent::initContent();

		$cart = $this->context->cart;
		$client = $this->context->customer;

		$invoice_address = new Address((int)$cart->id_address_invoice);
		$state = new State((int)$invoice_address->id_state);

		$arreglo_meses = array();

		for ($i=1; $i <= 12; $i++) {
			$arreglo_meses[] = sprintf("%02s", $i);
		}

		$arreglo_anyos = array();
		$anyo_actual = date("Y", time());
		for ($i=0; $i < 12; $i++) { 
			$arreglo_anyos[] = substr($anyo_actual + $i, -2);
		}

		session_start();
		$arreglo_errores = array();
		if(is_array($_SESSION['errores'])) {
			foreach ($_SESSION['errores'] as $key => $value) {
				$arreglo_errores[$key] = $value;
			}
		}

		$this->context->smarty->assign(array(
			'nbProducts' => $cart->nbProducts(),
			'cust_currency' => $cart->id_currency,
			'idPedido' => $cart->id,
			'currencies' => $this->module->getCurrency((int)$cart->id_currency),
			'monto' => $cart->getOrderTotal(true, Cart::BOTH),
			'this_path' => $this->module->getPathUri(),
			'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->module->name.'/',
			'nombre' => $client->firstname,
			'apellidos' => $client->lastname, 
			'cp' => $invoice_address->postcode,
			'monto' => $cart->getOrderTotal(true, Cart::BOTH),
			'idSucursal' => '8ad3877dbbeeaecd8b3b1971879685056b1bfdb3',
			'idUsuario' => 'fd5b20c115ba82128a400ed21a20f2662be258c8',
			'idServicio' => '3',
			'email' => $client->email,
			'telefono' => $invoice_address->phone,
			'calleyNumero' => $invoice_address->address1,
			'municipio' => $invoice_address->city,
			'estado' => $state->name,
			'pais' => $invoice_address->country,
			'anyos' => $arreglo_anyos,
			'meses' => $arreglo_meses,
			'errores' => $arreglo_errores
		));

		$this->setTemplate('payment_execution.tpl');
		
	}
}
