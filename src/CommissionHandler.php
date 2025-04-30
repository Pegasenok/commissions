<?php

namespace App;

use App\Exception\ValidationException;
use App\Validator\ValidatorInterface;

class CommissionHandler
{
    use ErrorHolderTrait;

    private ValidatorInterface $validator;

    public function processCommissions(JsonStreamer $streamer): array
    {
        $results = [];
        $i = 0;

        foreach ($streamer->iterate() as $line) {
            $i++;
            try {
                $this->validate($line);
            } catch (ValidationException $e) {
                $this->addError(sprintf("line:%d %s", $i, $e->getMessage()));
            }
            $results[] = 0;
        }

        return $results;
    }

    /**
     * @throws ValidationException
     */
    protected function validate(array $line)
    {
        $this->validator->validate($line);
    }

    public function setValidator(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }
}
