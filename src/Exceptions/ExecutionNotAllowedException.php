<?php

namespace Surgiie\Transformer\Exceptions;

use Exception;

/**
 * Exception thrown when a transformation function is blocked by the guard callback.
 */
class ExecutionNotAllowedException extends Exception {}
