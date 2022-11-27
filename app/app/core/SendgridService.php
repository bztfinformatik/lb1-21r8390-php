<?php

use Monolog\Logger;
use \SendGrid\Mail\TemplateId;
use \SendGrid\Mail\Mail;

class SendGridServiceException extends Exception
{
}

/**
 * Service for sending emails via SendGrid
 * 
 * @link [SendGrid](https://sendgrid.com/)
 * @link [SendGrid PHP Library](https://github.com/sendgrid/sendgrid-php)
 */
class SendgridService
{

    private SendGrid $sg;
    private LogManager $logger;

    private string $verificationTemplateId = 'd-9a0bbff0f3a54c6ea9e1c449e690eb88';
    private string $statusTemplateId = 'd-4ddbea2de78d40ea88260162df4846e1';

    public function __construct()
    {
        $this->logger = new LogManager('php-sendgrid');
        $this->sg = new SendGrid(SENDGRID_API_KEY);
        $this->logger->log('The SendGrid service has been initialized', Logger::DEBUG);
    }

    /**
     * Throws an exception and logs it
     *
     * @param string $error The error message
     * @throws Exception The exception that was thrown
     */
    protected function throwError(string $error)
    {
        $this->logger->log($error, Logger::ERROR);
        throw new SendGridServiceException($error);
    }

    /**
     * Sends a verification email to the given email address
     *
     * @param string $name
     * @param string $emailTo
     * @param string $token
     * @throws SendGridServiceException If the email could not be sent or the email address is invalid
     */
    public function sendVerification(string $name, string $emailTo, string $token)
    {
        if (empty($name) || empty($emailTo) || empty($token)) {
            $this->throwError('The name, email, and token are required to send a verification email');
        }
        if (!filter_var($emailTo, FILTER_VALIDATE_EMAIL)) {
            $this->throwError('The email address is not valid');
        }

        $email = new Mail();
        $email->setFrom(EMAIL_FROM, 'MkSimple');
        $email->addTo($emailTo, $name);
        $email->setTemplateId(new TemplateId($this->verificationTemplateId));

        $email->addDynamicTemplateDatas([
            'name'     => $name,
            'verification_url' => URLROOT . "/UserController/verify/$token",
        ]);

        $this->logger->log('Sending verification email to ' . $emailTo, Logger::DEBUG);
        $response = $this->sg->send($email);

        if ($this->resolveStatusCode($response->statusCode())) {
            $this->logger->log('Verification email sent successfully to ' . $emailTo, Logger::INFO);
        }
    }

    /**
     * Resolves the status code of the response
     *
     * @param integer $statusCode The status code
     * @return boolean True if the status code is 2xx
     * @throws SendGridServiceException If the status code is not 2xx
     */
    private function resolveStatusCode(int $statusCode): bool
    {
        if ($statusCode >= 200 && $statusCode < 300) {
            return true;
        }
        if ($statusCode == 429) {
            $this->throwError('Too many requests');
            return false;
        }
        $this->throwError('An unknown error occurred while sending the email');
    }
}
