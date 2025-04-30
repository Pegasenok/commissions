<?php

namespace App\Validator;

use JsonSchema\Validator;

class CommissionValidatorFactory
{
    public static function getValidator(): ValidatorInterface
    {
        return new CommissionLineValidator(
            new Validator,
            getcwd().'/src/Resources/Schema/line_schema.json'
        );
    }
}
