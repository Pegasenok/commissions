<?php

namespace App\Validator;

use App\Exception\ValidationException;

interface ValidatorInterface
{
    /**
     * @throws ValidationException
     */
    public function validate(?array $line);
}
