<form action="{$action}" id="pagofacilspei" method="POST">

{if count($errors) > 0}
    <div class="alert alert-danger">
        <h4>{l s='Errores'}</h4>
        <hr>
        <ol>
        {foreach from=$errors item=error}
            <li>
                <strong>{$error}</strong>
            </li>
        {/foreach}
        </ol>
    </div>
{/if}
	<div class="alert alert-success">
		<strong>{l s="Monto a pagar $ $total"}</strong>
	</div>

	<div class="form-group">
		<input type="hidden" class="form-control input-sm" name="id_pedido" id="id_pedido" value="{$id_order}" >
  	</div>

	  <div class="form-group">
		<input type="hidden" class="form-control input-sm" name="concepto" id="concepto" value="{$id_order}" >
  	</div>

	<div class="form-group">
    	<label for="nombre" class="control-label">{l s='Nombre(s)'}</label>
		<input type="text" class="form-control input-sm" name="nombre" id="nombre" value="{$nombre}" required="required">
  	</div>

  	<div class="form-group">
    	<label for="apellidos" class="control-label">{l s='Apellidos(s)'}</label>
		<input type="text" class="form-control input-sm" name="apellidos" id="apellidos" value="{$apellidos}" required="required">
  	</div>

  	<div class="form-group">
    	<label for="email" class="control-label">{l s='Email'}</label>
		<input type="email" class="form-control input-sm" id="email" name="email" value="{$email}" required="required" autocomplete="off">
  	</div>

    <div class="alert alert-warning">
        <strong>*Todos los campos son obligatorios</strong>
    </div>
</form>