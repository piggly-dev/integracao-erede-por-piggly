<?php

namespace Piggly\WooERedeGateway\Vendor\Rede;

interface RedeUnserializable
{
    /**
     * @param string $serialized
     *
     * @return mixed
     */
    public function jsonUnserialize($serialized);
}
