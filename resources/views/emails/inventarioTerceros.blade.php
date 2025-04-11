<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificación de archivo adjunto</title>
    <style>
        /* Corrección para clientes de correo */
        body {
            width: 100% !important;
            min-width: 100%;
            margin: 0;
            padding: 0;
        }

        table {
            border-collapse: collapse;
        }
    </style>
</head>

<body style="width: 100% !important; min-width: 100%; margin: 0; padding: 0; background-color: #f4f4f4; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6;">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" height="100%" style="background-color: #f4f4f4; width: 100%; height: 100%; margin: 0; padding: 0;">
        <tr>
            <td align="center" valign="middle" style="padding: 20px 0;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" style="max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); overflow: hidden;">
                    <!-- Header -->
                    <tr>
                        <td style="background-color: {% if $estado === 'RECHAZADO' %}#FF5733{% elseif !$nombreArchivo %}#FFC300{% else %}#3CB371{% endif %}; color: #ffffff; text-align: center; padding: 20px 0; font-size: 1.5em; border-bottom: 2px solid {% if $estado === 'RECHAZADO' %}#FF8C8C{% elseif !$nombreArchivo %}#FFD787{% else %}#98FB98{% endif %};">
                            <div style="font-size: 1.2em; margin-bottom: 10px;">SIMMONS</div>
                        </td>
                    </tr>
                    <!-- Body -->
                    <tr>
                        <td style="padding: 20px; text-align: left;">
                            <div style="margin-bottom: 20px;">
                                <p>Estimado/a {{ $remitente }},</p>

                                @if ($estado === 'RECHAZADO')
                                <p>Lamentamos informarle que el correo enviado a <b>stockterceros@simmons.com.ec</b> no pudo ser procesado debido a lo siguiente:</p>

                                @if ($nombreArchivo)
                                <p>El archivo adjunto <b>{{ $nombreArchivo }}</b> no cumple con los formatos requeridos:</p>
                                @if ($causas)
                                <p>Posibles causas:</p>
                                <ul>
                                    @foreach ($causas as $item)
                                    <li>{{ $item }}</li>
                                    @endforeach
                                </ul>
                                @endif
                                @else
                                <p>No se encontró archivo adjunto en su correo enviado.</p>
                                @endif

                                <div style="border: 1px solid #ccc; padding: 10px; margin-bottom: 20px; background-color: #f9f9f9; border-radius: 4px;">
                                    <div style="font-weight: bold; border-bottom: 1px solid #ccc; padding-bottom: 5px; margin-bottom: 10px;">Recomendaciones para el formato del archivo adjunto:</div>
                                    <div style="margin-bottom: 5px;">1. El archivo debe ser tipo Excel con el nombre <b>[NombreProveedor]Stock.xlsx</b>. Sustituir <b>[NombreProveedor]</b> por el nombre del proveedor.</div>
                                    <div style="margin-bottom: 5px;">2. En la celda E1, incluir la fecha y hora de corte de las existencias.</div>
                                    <div style="margin-bottom: 5px;">3. A partir de la fila <b>5</b> y la columna <b>A</b>, incluir el listado de existencias con las siguientes columnas:</div>
                                    <ul>
                                        <li>A = <b>CODIGO:</b> Código SIMMONS del producto.</li>
                                        <li>B = <b>DESCRIPCION:</b> Descripción del Producto.</li>
                                        <li>C = <b>Referencia:</b> Código del proveedor para ese producto.</li>
                                        <li>D = <b>STA:</b> Estatus del producto (A = Activo, I = Inactivo).</li>
                                        <li>E = <b>Disponible:</b> Cantidad que podemos vender del producto.</li>
                                    </ul>
                                </div>
                                @elseif ($estado === 'PROCESADO')
                                <p>Nos complace informarle que el correo enviado a <b>stockterceros@simmons.com.ec</b>, con el archivo adjunto <b>{{ $nombreArchivo }}</b> ha sido aceptado y procesado correctamente.</p>
                                @endif
                            </div>
                            <div style="font-size: 0.9em; margin-top: 10px; text-align: center;">
                                <p><b>No responda a este correo.</b><br>
                                    <i>Este es un mensaje generado automáticamente por nuestro sistema.</i>
                                </p>
                            </div>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="text-align: center; padding: 10px 0; background-color: {% if $estado === 'RECHAZADO' %}#FF5733{% elseif !$nombreArchivo %}#FFC300{% else %}#3CB371{% endif %}; font-size: 0.8em; color: #ffffff; border-top: 2px solid {% if $estado === 'RECHAZADO' %}#FF8C8C{% elseif !$nombreArchivo %}#FFD787{% else %}#98FB98{% endif %}; border-radius: 0 0 8px 8px;">
                            <p>&copy; {{ date('Y') }} Simmons. Todos los derechos reservados.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>