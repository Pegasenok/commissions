<?php

namespace App;

use App\Exception\ValidationException;
use App\Validator\ValidatorInterface;

class CommissionHandler
{
    private ValidatorInterface $validator;
    private $errors = [];

    public function processCommissions(JsonStreamer $streamer): array
    {
        $results = [];

        foreach ($streamer->iterate() as $line) {
            try {
                $this->validate($line);
            } catch (ValidationException $e) {
                $this->addError($e->getMessage());
            }
            $results[] = 0;
        }

        return $results;
    }

    private function validate(array $line)
    {
        $this->validator->validate($line);
    }

    public function setValidator(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    public function addError($error)
    {
        $this->errors[] = $error;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
