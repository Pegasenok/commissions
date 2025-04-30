<?php

namespace App;

use App\Exception\ValidationException;
use App\Services\BinInfo\BinLookupInterface;
use App\Validator\ValidatorInterface;

class CommissionHandler
{
    use ErrorHolderTrait;

    public function __construct(
        private BinLookupInterface $binInfo,
    ) {
    }

    public function processCommissions(JsonStreamer $streamer): array
    {
        $results = [];
        $i = 0;

        foreach ($streamer->iterate() as $line) {
            $i++;
            try {
                $this->validate($line);
                $code = $this->binInfo->getCountryCodeByBin($line['bin']);
                $results[] = $code;
            } catch (ValidationException $e) {
                $this->addError(sprintf("line:%d %s", $i, $e->getMessage()));
            };
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
