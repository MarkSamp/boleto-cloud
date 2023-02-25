<?php

namespace BoletoCloud\Api\Boleto;

interface ParserInteface
{
    public function parser(string $raiz): array;
    public function parserCarne(): array;
}
