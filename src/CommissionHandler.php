<?php

namespace App;

use App\Commission\Calculator\CommissionCalculatorInterface;
use App\Commission\MoneyAmount;
use App\Exception\ValidationException;
use App\Services\BinInfo\BinLookupInterface;
use App\Validator\ValidatorInterface;

class CommissionHandler
{
    use ErrorHolderTrait;

    /**
     * @var CommissionCalculatorInterface[]
     */
    private array $commissionCalculators = [];

    public function __construct(
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
                $amount = new MoneyAmount($line['amount']);
                foreach ($this->commissionCalculators as $commissionCalculator) {
                    if ($commissionCalculator->isSuitable($amount, $line['bin'], $line['currency'])) {
                        $amount->addModifier(
                            $commissionCalculator->getMoneyAmountAdjustCallback($amount, $line['bin'], $line['currency'])
                        );
                    }
                }
                $results[] = $amount->getAmount();
            } catch (ValidationException $e) {
                $this->addError(sprintf("line:%d %s", $i, $e->getMessage()));
            };
        }

        return $results;
    }

    public function addCommissionCalculator(CommissionCalculatorInterface $commissionCalculator): void
    {
        $this->commissionCalculators[] = $commissionCalculator;
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
