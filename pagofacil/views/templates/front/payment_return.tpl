{if $type != 'cash'}
<h2>Su pago ha sido procesado exitosamente con {$payment}</h2>
<hr>
{/if}

<div class="row">
    {if $type == 'tp'}
    <div class="col-md-6">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <th class="text-center">Descripción</th>
                    <th class="text-center">Dato</th>
                </thead>
                <tbody>
                    <tr>
                        <td>No. Transacción</td>
                        <td>{$transaction}</td>
                    </tr>
                    <tr>
                        <td>No. Autorización</td>
                        <td>{$no_authorization}</td>
                    </tr>
                    <tr>
                        <td>Descripción</td>
                        <td>{$description}</td>
                    </tr>
                    <tr>
                        <td>Mensaje</td>
                        <td>{$message}</td>
                    </tr>
                    <tr>
                        <td>Total Pagado</td>
                        <td>{$total}</td>
                    </tr>
                    <tr>
                        <td>Tienda</td>
                        <td>{$shop_name}</td>
                    </tr>
                    <tr>
                        <td>Orden</td>
                        <td>{$id_order}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    {/if}
    {if $type == 'cash'}
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-title">
                <h3>¡Felicitaciones! Su pedido ha sido generado correctamente, 
                verifica la orden de pago en tu e-mail.</h3>
                <hr>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-8">
                        <p>
                            <strong>
                                <i>Seguir los siguientes pasos:</i>
                            </strong>
                        </p>
                    </div>
                    <div class="col-md-4">
                        <p class="text-danger text-right">
                            <strong>
                                Último día para pagar <i>{$expiration_payment}</i>
                            </strong>
                        </p>
                    </div>
                </div>
                <hr>
                <ol>
                    <li>
                        <p>Ir a la caja {$convenience_store} de {$store_schedule}</p>
                        <img src="{$store_image}" alt="PagoFácil">
                    </li>
                    <li>
                        <p>Solicitar depósito a cuenta (debito): <strong>{$bank}</strong> 
                        - <strong>{$bank_account_number}</strong></p>
                    </li>
                    <li>
                        <p>Deposita la cantidad <strong>EXACTA</strong> de: <strong>${$total}</strong></p>
                    </li>
                </ol>
                <hr>

                <div class="alert alert-info">
                    <h3>
                        <strong>Importante</strong>
                    </h3>
                    <ul style="list-style-type:disc; padding-left: 5%;">
                        <li>El ID de control es: <strong>{$reference}</strong></li>
                        <li>El número de cuenta/tarjeta asignado es único por cada orden de compra.</li>
                        <li>
                            Orden válida antes de <strong>{$expiration_date}</strong>, 
                            en caso de vencimiento genera una nueva compra.
                        </li>
                        {if isset($store_fixed_rate) && $store_fixed_rate != ''}
                        <li>
                            <strong>{$convenience_store}</strong> cobra en caja una comisión de 
                            <strong>$ {$store_fixed_rate}</strong> por el concepto de recepción de cobranza.
                        </li>
                        {/if}
                    </ul>
                </div>
            </div>
        </div>
    </div>
    {/if}
</div>