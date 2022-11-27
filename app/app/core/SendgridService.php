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
        // Validate the parameters
        if (empty($name) || empty($emailTo) || empty($token)) {
            $this->throwError('The name, email, and token are required to send a verification email');
        }
        if (!filter_var($emailTo, FILTER_VALIDATE_EMAIL)) {
            $this->throwError('The email address is not valid');
        }

        $this->logger->log("Sending verification email to $emailTo", Logger::DEBUG);

        // Create the email
        $email = new Mail();
        $email->setFrom(EMAIL_FROM, 'MkSimple');
        $email->addTo($emailTo, $name);
        $email->setTemplateId(new TemplateId($this->verificationTemplateId));

        // Fill in the template variables
        $email->addDynamicTemplateDatas([
            'name' => $name,
            'verification_url' => URLROOT . "/UserController/verify/$token",
        ]);

        // Send the email
        $response = $this->sg->send($email);

        // Check the response
        if ($this->resolveStatusCode($response->statusCode())) {
            $this->logger->log('Verification email sent successfully to ' . $emailTo, Logger::INFO);
        }
    }

    /**
     * Sends a status email to the given email address
     *
     * @param string $name The name of the user
     * @param string $emailTo The email address of the user
     * @param string $projectName The name of the project
     * @param string $projectUrl The URL of the project
     * @param string $status The status of the project
     */
    public function sendStatusChanged(string $name, string $emailTo, string $projectName, string $projectUrl, string $status)
    {
        // Validate the parameters
        if (empty($name) || empty($emailTo) || empty($projectName) || empty($projectUrl) || empty($status)) {
            $this->throwError('The name, email, project name, project URL, and status are required to send a status email');
        }
        if (!filter_var($emailTo, FILTER_VALIDATE_EMAIL)) {
            $this->throwError('The email address is not valid');
        }

        $this->logger->log('Sending status change email to ' . $emailTo, Logger::DEBUG);

        // Create the email
        $email = new Mail();
        $email->setFrom(EMAIL_FROM, 'MkSimple');
        $email->addTo($emailTo, $name);
        $email->setTemplateId(new TemplateId($this->statusTemplateId));

        // Fill in the template variables
        $email->addDynamicTemplateDatas([
            'name' => $name,
            'project_name' => $projectName,
            'status' => $status,
            'project_url' => $projectUrl,
        ]);

        // Send the email
        $response = $this->sg->send($email);

        // Check the response
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
            // Success
            return true;
        }

        if ($statusCode == 429) {
            // Too many requests (rate limit)
            $this->throwError('Too many requests to SendGrid (max: 100 per day)');
            return false;
        }

        // Other error
        $this->throwError("An unknown error ($statusCode) occurred while sending the email");
    }
}
