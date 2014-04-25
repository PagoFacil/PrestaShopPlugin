{capture name=path}{l s='pagofacil payment' mod='pagofacil'}{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}

<h2>{l s='Pagofácil' mod='pagofacil'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if isset($nbProducts) && $nbProducts <= 0}
	<p class="warning">{l s='Su carrito se encuentra vacío.'}</p>
{else}

<form target="_blank" name="formularioPago" method="post" action="https://www.pagofacil.net/st/public/Payform">
	<p>
		<img src="{$this_path}pagofacil.jpg" alt="{l s='pagofacil' mod='pagofacil'}" >
		<br/>{l s='Escogió pagar con PagoFácil.' mod='pagofacil'}
	</p>
	<p style="margin-top:20px;">
		- {l s='El total de su orden es:' mod='pagofacil'}
		<span id="amount" class="price">{displayPrice price=$total}</span>
	</p>
	<p>
		Para realizar su pago en el sitio de PagoFácil haga click en el botón compra
	</p>
	<input type="hidden" value="8ad3877dbbeeaecd8b3b1971879685056b1bfdb3" name="idSucursal">
	<input type="hidden" value="fd5b20c115ba82128a400ed21a20f2662be258c8" name="idUsuario">
	<input type="hidden" value="1" name="idServicio">
	<input type="hidden" value="{$idcart}" name="idPedido">
	<input type="hidden" value="{$total}" name="monto">
	<input type="hidden" value="1" name="redireccion">
	<input type="hidden" value="https://www.farmasmart.com/module/pagofacil/validation" name="urlResponse">
	<input class="" name="submit" type="submit" value='compra'>
</form>
{/if}
