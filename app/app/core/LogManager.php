<?php

use Monolog\Logger;
use Monolog\Handler\SocketHandler;

/**
 * The log manager class that handles logging
 */
class LogManager
{
    private Logger $logger;

    /**
     * Initialize the logger
     * If `IS_LOGGING` is set to true, the logger will send the logs to the ELK stack
     *
     * @param string $loggerName The name of the logger
     */
    public function __construct(string $loggerName)
    {
        if (empty($loggerName)) {
            throw new InvalidArgumentException('The logger name cannot be empty');
        }

        // Create the logger
        $this->logger = new Logger($loggerName);

        // Now add some handlers
        if (IS_LOGGING) {
            // "logstash" is a host defined by docker-compose
            $handler = new SocketHandler(LOGSTASH, Logger::DEBUG);
            $this->logger->pushHandler($handler);
        }

        $this->log('The logger has been initialized', Logger::NOTICE);
    }

    /**
     * Gets the metadata for the log
     *
     * @return array The log metadata
     */
    private function getMetaInfo(): array
    {
        return [
            'user_id' => $_SESSION['user_id'] ?? 'Unknown',
            'user_email' => $_SESSION['user_email'] ?? 'Unknown',
            'ip' => $_SERVER['REMOTE_ADDR'],
            'method' => $_SERVER['REQUEST_METHOD'],
            'uri' => $_SERVER['REQUEST_URI'],
            'agent' => $_SERVER['HTTP_USER_AGENT'],
            'referer' => $_SERVER['HTTP_REFERER'] ?? 'not set'
        ];
    }

    /**
     * Log a message using the logger
     *
     * @param string $message The message to log
     * @param Logger $level The level of the message (default: DEBUG)
     */
    public function log(string $message, $level = Logger::DEBUG)
    {
        $visitDetails = $this->getMetaInfo();

        switch ($level) {
            case Logger::INFO:
                $this->logger->info($message, $visitDetails);
                break;
            case Logger::NOTICE:
                $this->logger->notice($message, $visitDetails);
                break;
            case Logger::WARNING:
                $this->logger->warning($message, $visitDetails);
                break;
            case Logger::ERROR:
                $this->logger->error($message, $visitDetails);
                break;
            case Logger::CRITICAL:
                $this->logger->critical($message, $visitDetails);
                break;
            case Logger::ALERT:
                $this->logger->alert($message, $visitDetails);
                break;
            case Logger::EMERGENCY:
                $this->logger->emergency($message, $visitDetails);
                break;
            default:
                $this->logger->debug($message, $visitDetails);
                break;
        }
    }
}
