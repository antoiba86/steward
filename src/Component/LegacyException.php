<?php
namespace Lmc\Steward\Component;

use Exception;

/**
 * Exception raised by Legacy
 */
class LegacyException extends \Exception
{
    public function __construct($message = "", $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}