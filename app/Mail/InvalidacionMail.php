<?php

namespace App\Mail;

use App\Models\EventoInvalidacion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;

class InvalidacionMail extends Mailable
{
    use Queueable, SerializesModels;

    public EventoInvalidacion $evento;

    public function __construct(EventoInvalidacion $evento)
    {
        $this->evento = $evento;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invalidación de Documento Tributario - ' . $this->evento->factura->numero_control,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invalidacion',
            with: [
                'evento' => $this->evento,
            ],
        );
    }

    /**
     * Según la Normativa de Cumplimiento DTE 2.0 del MH, al invalidar un DTE
     * es obligatorio entregar al receptor: el JSON del evento, la representación
     * gráfica del evento de invalidación y la representación gráfica del DTE original.
     */
    public function attachments(): array
    {
        $attachments = [];
        $factura = $this->evento->factura;

        // 1. JSON del evento de invalidación
        $attachments[] = Attachment::fromData(function () {
            return json_encode($this->evento->evento_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $this->evento->codigo_invalidacion . '.json')
            ->withMime('application/json');

        // 2. PDF (representación gráfica) del evento de invalidación
        try {
            $pdfEvento = Pdf::loadView('invalidaciones.pdf', ['evento' => $this->evento]);
            $attachments[] = Attachment::fromData(function () use ($pdfEvento) {
                return $pdfEvento->output();
            }, 'invalidacion_' . $factura->numero_control . '.pdf')
                ->withMime('application/pdf');
        } catch (\Exception $e) {
            $attachments[] = Attachment::fromData(function () {
                return view('invalidaciones.pdf', ['evento' => $this->evento])->render();
            }, 'invalidacion_' . $factura->numero_control . '.html')
                ->withMime('text/html');
        }

        // 3. PDF (representación gráfica) del DTE original invalidado
        try {
            $pdfFactura = Pdf::loadView('facturas.pdf', ['factura' => $factura]);
            $attachments[] = Attachment::fromData(function () use ($pdfFactura) {
                return $pdfFactura->output();
            }, $factura->numero_control . '.pdf')
                ->withMime('application/pdf');
        } catch (\Exception $e) {
            $attachments[] = Attachment::fromData(function () use ($factura) {
                return view('facturas.pdf', ['factura' => $factura])->render();
            }, $factura->numero_control . '.html')
                ->withMime('text/html');
        }

        return $attachments;
    }
}
