<?php

namespace App;

use App\Commission\Calculator\CommissionCalculatorInterface;
use App\Commission\MoneyAmount;
use App\Exception\BrokenInputException;
use App\Exception\CommissionFailureInterface;
use App\Exception\ValidationException;
use App\Validator\ValidatorInterface;

class CommissionHandler
{
    use ErrorHolderTrait;

    /**
     * @var CommissionCalculatorInterface[]
     */
    private array $commissionCalculators = [];
    private ValidatorInterface $validator;

    public function __construct()
    {
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
                            $commissionCalculator->getMoneyAmountAdjustCallback(
                                $amount,
                                $line['bin'],
                                $line['currency']
                            )
                        );
                    }
                }
                $results[] = $amount->getAmount();
            } catch (BrokenInputException $e) {
                $this->addError(sprintf("broken_line:\t%d\t%s", $i, $e->getMessage()));
            } catch (ValidationException|CommissionFailureInterface $e) {
                $this->addError(sprintf("unprocessed_line:\t%d\t%s", $i, $e->getMessage()));
            }
        }

        return $results;
    }

    public function addCommissionCalculator(CommissionCalculatorInterface $commissionCalculator): void
    {
        $this->commissionCalculators[] = $commissionCalculator;
    }

    /**
     * @throws BrokenInputException
     */
    protected function validate(array $line)
    {
        try {
            $this->validator->validate($line);
        } catch (ValidationException $exception) {
            throw new BrokenInputException($exception->getMessage(), previous: $exception);
        }
    }

    public function setValidator(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }
}
