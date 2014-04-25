{capture name=path}{l s='pagofacil payment' mod='pagofacil'}{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}

<h2 class="title-img"><img src="{$this_path}logo.png" alt="{l s='pagofacil' mod='pagofacil'}" /></h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if isset($nbProducts) && $nbProducts <= 0}
<p class="warning">{l s='Su carrito se encuentra vacío.'}</p>
{else}


<form name="formularioPago" method="POST" action="https://farmasmart.com/module/pagofacil/validation">

	<div class="info">
		<p>
			{l s='Escogió pagar con PagoFácil.' mod='pagofacil'}
			- {l s='El total de su orden es:' mod='pagofacil'}
			<span id="amount" class="price">{displayPrice price=$monto}</span>
			<strong>Por Favor revise sus datos y complete el formulario.</strong>
			Para realizar su pago por medio de PagoFácil haga click en el botón comprar
		</p>
	</div>
	<div class="error">
		{foreach from=$errores item=error}
			<strong>{$error}</strong><br/>
		{/foreach}
	</div>

	<ul class="pay-easy">
		<li class="hidden">
			<label for="input-nombre">Nombre(s):</label>
			<input id="input-nombre" type="text" name="nombre" value="{$nombre}">
		</li>

		<li class="hidden">
			<label for="input-apellidos">Apellidos:</label>
			<input id="input-apellidos" type="text" name="apellidos" value="{$apellidos}">
		</li>

		<li>
			<label for="input-numeroTarjeta" class="required">Número de Tarjeta:</label>
			<input id="input-numeroTarjeta" type="text" name="numeroTarjeta">
		</li>
		<li>
			<label class="required">Expiración:</label>
			<label for="select-mesExpiracion" class="den-label">
				<select name="mesExpiracion" id="select-mesExpiracion" class="select">
					<option selected>Mes de expiracion:</option>
					{foreach from=$meses item=mes}
					<option value="{$mes}">{$mes}</option>
					{/foreach}
				</select>
			</label>


			<label for="select-anyoExpiracion" class="den-label">
				<select name="anyoExpiracion" id="select-anyoExpiracion" class="select">
					<option selected>Año de expiración:</option>
					{foreach from=$anyos item=anyo}
					<option value="{$anyo}">{$anyo}</option>
					{/foreach}
				</select>
			</label>
		</li>
		<li>
			<label for="input-cvt" class="required">Código Cvv2:</label>
			<input id="input-cvt" type="password" name="cvt" autocomplete="off">
		</li>

		<li>
			<label for="input-cp" class="required">Código Postal:</label>
			<input id="input-cp" type="text" name="cp" value="{$cp}">
		</li>

		<li>
			<label for="input-email" class="required">Correo Electrónico:</label>
			<input id="input-email" type="text" name="email" value="{$email}">
		</li>

		<li>
			<label for="input-telefono">Teléfono (10 dígitos):</label>
			<input id="input-telefono" type="text" name="telefono" value="{$telefono}">
		</li>

		<li>
			<label for="input-celular">Celular (10 dígitos):</label>
			<input id="input-celular" type="text" name="celular" value="{$celular}">
		</li>

		<li>
			<label for="input-calleyNumero" class="required">Calle y Número:</label>
			<input id="input-calleyNumero" type="text" name="calleyNumero" value="{$calleyNumero}">
		</li>

		<li>
			<label for="input-colonia" class="required">Colonia:</label>
			<input id="input-colonia" type="text" name="colonia" value="{$colonia}">
		</li>

		<li>
			<label for="input-municipio" class="required">Delegación o Municipio:</label>
			<input id="input-municipio" type="text" name="municipio" value="{$municipio}">
		</li>

		<li>
			<label for="input-estado" class="required">Estado:</label>
			<input id="input-estado" type="text" name="estado" value="{$estado}">
		</li>

		<li>
			<label for="input-pais" class="required">País:</label>
			<input id="input-pais" type="text" name="pais" value="{$pais}">
		</li>

		<input id="input-idPedido" type="hidden" name="idPedido" value="{$idPedido}">
		<input id="input-monto" type="hidden" name="monto" value="{$monto}">
		<input id="input-idSucursal" type="hidden" name="idSucursal" value="{$idSucursal}">
		<input id="input-idUsuario" type="hidden" name="idUsuario" value="{$idUsuario}">
		<input id="input-idServicio" type="hidden" name="idServicio" value="{$idServicio}">

		<li>
			<input class="btn" name="submit" type="submit" value='compra'>
		</li>
	</form>
</ul>
{/if}