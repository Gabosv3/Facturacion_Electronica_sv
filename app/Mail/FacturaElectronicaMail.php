<?php

namespace App\Mail;

use App\Models\Factura;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class FacturaElectronicaMail extends Mailable
{
    use Queueable, SerializesModels;

    public $factura;

    /**
     * Create a new message instance.
     */
    public function __construct(Factura $factura)
    {
        $this->factura = $factura;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Factura Electrónica - ' . $this->factura->numero_control,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.factura_electronica',
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        $attachments = [];

        // Adjuntar JSON del DTE (sin credenciales sensibles)
        $attachments[] = Attachment::fromData(function () {
            // Crear una copia del DTE JSON sin credenciales sensibles
            $dteJsonSinCredenciales = $this->factura->dte_json;

            // Eliminar campos sensibles si existen
            if (isset($dteJsonSinCredenciales['nit'])) {
                unset($dteJsonSinCredenciales['nit']);
            }
            if (isset($dteJsonSinCredenciales['activo'])) {
                unset($dteJsonSinCredenciales['activo']);
            }
            if (isset($dteJsonSinCredenciales['passwordPri'])) {
                unset($dteJsonSinCredenciales['passwordPri']);
            }

            return json_encode($dteJsonSinCredenciales, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $this->factura->numero_control . '.json')
            ->withMime('application/json');

        // Adjuntar HTML/PDF
        $attachments[] = Attachment::fromData(function () {
            return view('facturas.pdf', ['factura' => $this->factura])->render();
        }, $this->factura->numero_control . '.html')
            ->withMime('text/html');

        return $attachments;
    }
}
