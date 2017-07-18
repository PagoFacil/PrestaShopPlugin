<h2>Su pago ha sido procesado exitosamente con {$payment}</h2>
<hr>
<div class="row">
    <div class="col-md-8">
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
</div>