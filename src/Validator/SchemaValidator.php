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
        if (!file_exists($this->schemaPath)) {
            throw new \Exception('Schema not found');
        }
        $try = json_decode(file_get_contents($this->schemaPath));
        if (!$try) {
            throw new \Exception('Schema not found');
        }
        $this->schema = $try;
    }

    /**
     * @throws ValidationException
     */
    public function validate(mixed $line): void
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
