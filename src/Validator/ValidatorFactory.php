<?php

namespace App\Validator;

use JsonSchema\Validator;

class ValidatorFactory
{
    public static function getCommissionLineValidator(): ValidatorInterface
    {
        return new SchemaValidator(
            new Validator,
            getcwd().'/src/Resources/Schema/line_schema.json'
        );
    }

    public static function getBinInfoValidator(): ValidatorInterface
    {
        return new SchemaValidator(
            new Validator,
            getcwd().'/src/Resources/Schema/bin_schema.json'
        );
    }
}
