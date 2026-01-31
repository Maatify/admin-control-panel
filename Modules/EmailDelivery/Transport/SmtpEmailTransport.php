<?php

declare(strict_types=1);

namespace Maatify\EmailDelivery\Transport;

use Maatify\EmailDelivery\Config\EmailTransportConfigDTO;
use Maatify\EmailDelivery\DTO\RenderedEmailDTO;
use Maatify\EmailDelivery\Exception\EmailTransportException;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;
use Throwable;

class SmtpEmailTransport implements EmailTransportInterface
{
    public function __construct(
        private readonly EmailTransportConfigDTO $config
    ) {
    }

    public function send(
        string $recipientEmail,
        RenderedEmailDTO $email
    ): void {
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->SMTPDebug = $this->config->debugLevel;
            $mail->isSMTP();
            $mail->Host       = $this->config->host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->config->username;
            $mail->Password   = $this->config->password;

            if ($this->config->encryption === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($this->config->encryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = ''; // Explicitly disable if not set
                $mail->SMTPAutoTLS = false; // Disable AutoTLS to respect config strictly
            }

            $mail->Port       = $this->config->port;
            $mail->Timeout    = $this->config->timeoutSeconds;
            $mail->CharSet    = $this->config->charset;

            // Recipients
            $mail->setFrom($this->config->fromAddress, $this->config->fromName);
            $mail->addAddress($recipientEmail);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $email->subject;
            $mail->Body    = $email->htmlBody;

            $mail->send();

        } catch (PHPMailerException $e) {
            throw new EmailTransportException(
                "PHPMailer Error: " . $e->getMessage(),
                0,
                $e
            );
        } catch (Throwable $e) {
            throw new EmailTransportException(
                "Email Transport Error: " . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
