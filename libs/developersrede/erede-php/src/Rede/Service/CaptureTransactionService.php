<?php

namespace Piggly\WooERedeGateway\Vendor\Rede\Service;

use InvalidArgumentException;
use Piggly\WooERedeGateway\Vendor\Rede\Exception\RedeException;
use Piggly\WooERedeGateway\Vendor\Rede\Transaction;
use RuntimeException;
class CaptureTransactionService extends AbstractTransactionsService
{
    /**
     * @return Transaction
     * @throws InvalidArgumentException
     * @throws RuntimeException
     * @throws RedeException
     */
    public function execute()
    {
        return $this->sendRequest(\json_encode($this->transaction), AbstractService::PUT);
    }
    /**
     * @return string
     */
    protected function getService()
    {
        return \sprintf('%s/%s', parent::getService(), $this->transaction->getTid());
    }
}
