<?php

namespace App;

trait ErrorHolderTrait
{
    private $errors = [];

    public function getErrors(): array
    {
        return $this->errors;
    }

    protected function addError($error)
    {
        $this->errors[] = $error;
    }
}
