<?php

namespace Surgiie\Transformer\Exceptions;

use Exception;

/**
 * Exception thrown to abort a transformation chain early.
 *
 * This exception is caught internally by the transformer to stop processing.
 */
class AbortedTransformationException extends Exception {}
