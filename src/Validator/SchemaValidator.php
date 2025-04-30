<?php

namespace App\Validator;

use App\Exception\ValidationException;
use JsonSchema\Validator;

class SchemaValidator implements ValidatorInterface
{
    private object $schema;

    public function __construct(
        private Validator $validator,
        private string $schemaPath
    ) {
        $this->schema = json_decode(file_get_contents($this->schemaPath));
        if (!$this->schema) {
            throw new \Exception('Schema not found');
        }
    }

    public function validate(mixed $line)
    {
        $this->validator->validate($line, $this->getSchema());

        if (!$this->validator->isValid()) {
            throw new ValidationException($this->getErrorMessage());
        }
    }

    protected function getSchema(): object
    {
        return $this->schema;
    }

    private function getErrorMessage(): string
    {
        $message = '';
        foreach ($this->validator->getErrors() as $error) {
            $message .= sprintf("[%s] %s\n", $error['property'], $error['message']);
        }
        return $message;
    }
}
