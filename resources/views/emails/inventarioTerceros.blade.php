<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificación de archivo adjunto</title>
    <style>
        /* Estilos generales */
        html,
        body {
            height: 100%;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #f4f4f4;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
        }

        .card {
            width: 100%;
            max-width: 600px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .header {
            background-color: #37388D;
            color: #ffffff;
            text-align: center;
            padding: 20px 0;
            font-size: 1.5em;
            border-bottom: 2px solid #8EB8E0;
        }

        .content {
            padding: 20px;
            text-align: left;
        }

        .footer {
            text-align: center;
            padding: 10px 0;
            background-color: #37388D;
            font-size: 0.8em;
            color: #ffffff;
            border-top: 2px solid #8EB8E0;
            border-radius: 0 0 8px 8px;
        }

        .company-name {
            font-size: 1.2em;
            margin-bottom: 10px;
        }

        .message {
            margin-bottom: 20px;
        }

        .message p {
            margin-bottom: 10px;
        }

        .details {
            font-size: 0.9em;
            margin-top: 10px;
        }

        .details i {
            display: block;
        }

        .excel-box {
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 20px;
            background-color: #f9f9f9;
            border-radius: 4px;
        }

        .excel-header {
            font-weight: bold;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }

        .excel-row {
            margin-bottom: 5px;
        }
    </style>
</head>

<body>
    <div class="card">
        <div class="header">
            <div class="company-name">SIMMONS</div>
        </div>
        <div class="content">
            <div class="message">
                <p>Estimado/a {{ $remitente }},</p>

                @if ($estado === 'RECHAZADO')
                    <p>Lamentamos informarle que el correo enviado a <b>stockterceros@simmons.com.ec</b> no pudo ser
                        procesado debido a lo siguiente:</p>

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

                    <div class="excel-box">
                        <div class="excel-header">Recomendaciones para el formato del archivo adjunto:</div>
                        <div class="excel-row">1. El archivo debe ser tipo Excel con el nombre
                            <b>[NombreProveedor]Stock.xlsx</b>. Sustituir <b>[NombreProveedor]</b> por el nombre del
                            proveedor.
                        </div>
                        <div class="excel-row">2. En la celda E1, incluir la fecha y hora de corte de las existencias.
                        </div>
                        <div class="excel-row">3. A partir de la fila <b>5</b> y la columna <b>A</b>, incluir el listado
                            de existencias con las siguientes columnas:</div>
                        <ul>
                            <li>A = <b>CODIGO:</b> Código SIMMONS del producto.</li>
                            <li>B = <b>DESCRIPCION:</b> Descripción del Producto.</li>
                            <li>C = <b>Referencia:</b> Código del proveedor para ese producto.</li>
                            <li>D = <b>STA:</b> Estatus del producto (A = Activo, I = Inactivo).</li>
                            <li>E = <b>Disponible:</b> Cantidad que podemos vender del producto.</li>
                        </ul>
                    </div>
                @elseif ($estado === 'PROCESADO')
                    <p>Nos complace informarle que el correo enviado a <b>stockterceros@simmons.com.ec</b>, con el
                        archivo adjunto <b>{{ $nombreArchivo }}</b> ha sido aceptado y procesado correctamente.</p>
                @endif
            </div>
            <div class="details">
                <p>
                    <i>
                        <center>Este es un mensaje generado automáticamente por nuestro sistema</center>
                    </i>
                </p>
            </div>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} Simmons. Todos los derechos reservados.</p>
        </div>
    </div>
</body>

</html>
