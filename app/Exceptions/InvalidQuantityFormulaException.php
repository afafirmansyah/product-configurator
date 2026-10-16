<?php

namespace App\Exceptions;

use InvalidArgumentException;

/**
 * Raised when a BOM quantity formula cannot be parsed or evaluated.
 */
class InvalidQuantityFormulaException extends InvalidArgumentException {}
