<?php
 
namespace App\Services\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RespuestaInventarioTerceros extends Mailable
{
    use Queueable, SerializesModels;

    public $correoId;
    public $nombreArchivo;
    public $remitente;
    public $causas;
    /**
     * Create a new message instance.
     *
     * @param string $correoId ID del correo que no contiene archivos Excel.
     * @return void
     */
    public function __construct($correoId,$nombreArchivo,$remitente,$causas)
    {
        $this->correoId = $correoId;
        $this->nombreArchivo = $nombreArchivo;
        $this->remitente = $remitente;
        $this->causas = $causas;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $correoId= $this->correoId;
        $nombreArchivo= $this->nombreArchivo;
        $remitente= $this->remitente;
        $causas= $this->causas;
        return $this->subject('Respuesta Automatica | Simmons Ecuador')
                    ->view('emails.inventarioTerceros', compact('correoId','nombreArchivo','remitente','causas'));
    }
}
