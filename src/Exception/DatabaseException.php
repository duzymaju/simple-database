<?php

namespace SimpleDatabase\Exception;

use SimpleStructure\Exception\ExceptionInterface;
use SimpleStructure\Exception\RuntimeException;

/**
 * Database exception
 */
class DatabaseException extends RuntimeException implements ExceptionInterface
{
}
