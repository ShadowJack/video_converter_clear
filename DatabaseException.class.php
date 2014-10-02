<?php
/**
 * Wraps exceptions in db
 */
class DatabaseException extends Exception
{
    /**
     * Constructor
     *
     * @param string $message 
     * @param int $code 
     * @param Exception $previous 
     */
    public function __construct($message = '', $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
?>