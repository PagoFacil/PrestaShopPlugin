<?php


if (!defined('_PS_VERSION_'))
	exit;

class Pagofacil extends PaymentModule
{

	public function __construct()
	{
        $ver = "1.0";
        $by = "DreasmEngineering";
        $this->name = 'pagofacil';
        $this->tab = 'payments_gateways';
        $this->version = $ver;
        $this->author = $by;

        parent::__construct();

        $this->displayName = $this->l('PagoFácil');
        $this->description = $this->l('Módulo para aceptar pago de PagoFácil');
        $this->confirmUninstall = $this->l('¿Está seguro que desea borrar el módulo?');
    }

    public function install()
    {
      if (!parent::install() || !$this->registerHook('payment'))
         return false;
     return true;
 }

 public function uninstall()
 {
  if (!parent::uninstall())
     return false;
 return true;
}

public function hookPayment($params)
{
  if (!$this->active)
     return;

 $this->smarty->assign(array(
     'this_path' => $this->_path,
     'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/'
     ));
 return $this->display(__FILE__, 'payment.tpl');
}

	/*public function hookPaymentReturn($params)
	{
		if (!$this->active)
			return;

		$state = $params['objOrder']->getCurrentState();
		if ($state == Configuration::get('PS_OS_CHEQUE') || $state == Configuration::get('PS_OS_OUTOFSTOCK'))
		{
			$this->smarty->assign(array(
				'total_to_pay' => Tools::displayPrice($params['total_to_pay'], $params['currencyObj'], false),
				'chequeName' => $this->chequeName,
				'chequeAddress' => Tools::nl2br($this->address),
				'status' => 'ok',
				'id_order' => $params['objOrder']->id
			));
			if (isset($params['objOrder']->reference) && !empty($params['objOrder']->reference))
				$this->smarty->assign('reference', $params['objOrder']->reference);
		}
		else
			$this->smarty->assign('status', 'failed');
		return $this->display(__FILE__, 'payment_return.tpl');
	}*/
}
