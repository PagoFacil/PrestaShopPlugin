{capture name=path}{l s='pagofacilcash payment' mod='pagofacilcash'}{/capture}


<h2 class="title-img"><img src="{$this_path}logo.png" alt="{l s='pagofacilcash' mod='pagofacilcash'}" /></h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if isset($nbProducts) && $nbProducts <= 0}
    <p class="warning">{l s='Su carrito se encuentra vac&iacute;o' mod='pagofacilcash'}</p>
{else}
<style>
    ul.pay-form {
        margin: 0;
        padding: 0;
        display: block;
    }
    ul.pay-form > li {
        list-style: none;
    }
    ul.pay-form > li > label {
        width: 28%;
        height: 20px;
        display: inline-block;
    }
    ul.pay-form > li > input    
    ,ul.pay-form > li > select {
        border: 1px solid #D9D9D9;
        border-top: 1px solid #c0c0c0;
    }
    
    .small {
        width: 20%;
    }
    .medium {
        width: 40%;
    }
    .large {
        width: 60%;
    }
</style>
<form name="formularioPago" method="POST" action="{$link->getModuleLink('pagofacilcash', 'validation', [], true)|escape:'html'}">
	<div class="info">        
		Escogi&oacute; pagar con PagoF&aacute;cil<br/>
		El total de su orden es:<span id="amount" class="price">{displayPrice price=$monto}</span><br/>
		
		Para realizar su orden de pago por medio de PagoF&aacute;cil haga click en el bot&oacute;n comprar        
    </div>
            
    {if count($errores) > 0}
    <div class="error"  style="padding-top:5px;">
        {foreach from=$errores item=error}
            <strong>{$error}</strong><br/>
        {/foreach}
    </div>
    {/if}


	<div style="text-align:center; font-size:18px; font-weight:bold; padding-top:10px; padding-bottom:5px;">
		<strong>Por Favor elija la tienda de su preferencia.</strong><br/>
	</div>
	<ul class="pay-form">
		<li>            
			<label for="select-tienda">Tienda:</label>
			<select name="tienda" id="select-tienda" class="small">
				{foreach from=$store_codes key=key item=item}
					<option value="{$key}">{$item}</option>
				{/foreach}
			</select>                        
		</li>
		
		<li>
            <label for="input-email">Correo Electr&oacute;nico:</label>
            <input id="input-email" type="text" name="email" value="{$email}" autocomplete="off" class="medium">
        </li>
		<li>
			<label for="input-nombre">Nombre(s):</label>
			<input id="input-nombre" type="text" name="nombre" value="{$nombre}" autocomplete="off" class="large">
		</li>
		<li>
			<label for="input-apellidos">Apellidos:</label>
			<input id="input-apellidos" type="text" name="apellidos" value="{$apellidos}" autocomplete="off" class="large">
		</li>
	</ul>
		
	
    <p class="cart_navigation" id="cart_navigation">
        <input type="submit" value="{l s='Enviar mi pedido' mod='pagofacilcash'}" class="exclusive_large" />
        <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html'}" class="button_large">{l s='Otros métodos de pago' mod='pagofacilcash'}</a>
    </p>
</form>
{/if}