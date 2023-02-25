# Boleto Cloud PHP SDK
SDK de integraçao com a API BoletoCloud 

Agradecimentos ao usuário @millerp/boleto-cloud-sdk por ter compartilhado o código inicial

- Boleto Cloud - https://boletocloud.com/
- Documentação da API - https://boletocloud.com/app/dev/api

### Instalação
```
composer require marksamp/boleto-cloud
```

### Exemplo de Emissão de um boleto individual
```php
<?php

use BoletoCloud\Api\Boleto;
use BoletoCloud\Api\Boleto\Beneficiario;
use BoletoCloud\Api\Boleto\Conta;
use BoletoCloud\Api\Boleto\Pagador;
use BoletoCloud\Api\Client;

require_once __DIR__."/vendor/autoload.php";

$client = new Client([
    'env'   => 'sandbox',
    'token' => 'api_key',
]);

$conta = new Conta();
$conta->setBanco("237")
    ->setAgencia("1234-5")
    ->setNumero("123456-0")
    ->setCarteira(12);

$beneficiarioEndereco = new Boleto\Endereco("beneficiario");
$beneficiarioEndereco->setCep("59020-000")
    ->setLogradouro("Avenida Hermes da Fonseca")
    ->setNumero("384")
    ->setBairro("Petrópolis")
    ->setLocalidade("Natal")
    ->setUf("RN")
    ->setComplemento("Sala 2A, segundo andar");

$beneficiario = new Beneficiario();
$beneficiario->setNome("DevAware Solutions")
    ->setCprf("15.719.277/0001-46")
    ->setEndereco($beneficiarioEndereco);

$pagadorEndereco = new Boleto\Endereco("pagador");
$pagadorEndereco->setCep("36240-000")
    ->setLogradouro("BR-499")
    ->setNumero("s/n")
    ->setBairro("Casa Natal")
    ->setLocalidade("Santos Dumont")
    ->setUf("MG")
    ->setComplemento("Sítio - Subindo a serra da Mantiqueira");

$pagador = new Pagador();
$pagador->setNome("Alberto Santos Dumont")
    ->setCprf("111.111.111-11")
    ->setEndereco($pagadorEndereco);

$boleto = new Boleto();
$boleto->setConta($conta)
    ->setBeneficiario($beneficiario)
    ->setPagador($pagador)
    ->setEmissao(new \DateTime('2017-01-31'))
    ->setVencimento(new \DateTime('2017-02-05'))
    ->setDocumento('EX1')
    ->setNumero(rand(10000000000, 99999999999) . '-P')
    ->setTitulo('DM')
    ->setValor(121.53)
    ->setInstrucao([
        'Atenção! NÃO RECEBER ESTE BOLETO.' . date('d-m-y H:i:s'),
        'Este é apenas um teste utilizando a API Boleto Cloud' . date('d-m-y H:i:s'),
        'Mais info em http://www.boletocloud.com/app/dev/api' . date('d-m-y H:i:s'),
    ]);

$retorno = $client->gerarBoleto($boleto);
```


### Exemplo de Emissão de um Carnê (em lote)
```php
<?php

use BoletoCloud\Api\Boleto;
use BoletoCloud\Api\Boleto\Beneficiario;
use BoletoCloud\Api\Boleto\Conta;
use BoletoCloud\Api\Boleto\Pagador;
use BoletoCloud\Api\Client;

require_once __DIR__."/vendor/autoload.php";

$client = new Client([
    'env'   => 'sandbox',
    'token' => 'api_key',
]);

$conta = new Conta();
$conta->setBanco("237")
    ->setAgencia("1234-5")
    ->setNumero("123456-0")
    ->setCarteira(12);

$beneficiarioEndereco = new Boleto\Endereco("beneficiario");
$beneficiarioEndereco->setCep("59020-000")
    ->setLogradouro("Avenida Hermes da Fonseca")
    ->setNumero("384")
    ->setBairro("Petrópolis")
    ->setLocalidade("Natal")
    ->setUf("RN")
    ->setComplemento("Sala 2A, segundo andar");

$beneficiario = new Beneficiario();
$beneficiario->setNome("DevAware Solutions")
    ->setCprf("15.719.277/0001-46")
    ->setEndereco($beneficiarioEndereco);

$pagadorEndereco = new Boleto\Endereco("pagador");
$pagadorEndereco->setCep("36240-000")
    ->setLogradouro("BR-499")
    ->setNumero("s/n")
    ->setBairro("Casa Natal")
    ->setLocalidade("Santos Dumont")
    ->setUf("MG")
    ->setComplemento("Sítio - Subindo a serra da Mantiqueira");

$pagador = new Pagador();
$pagador->setNome("Alberto Santos Dumont")
    ->setCprf("111.111.111-11")
    ->setEndereco($pagadorEndereco);

$arrayBoletos = array();
for($i = 0; $i <= 1; $i++) {
    $int= mt_rand(1262055681,2000000000);
    $dateRandom = date('Y-m-d', $int);
    $boleto = new Boleto();
    $boleto->setConta($conta)
        ->setBeneficiario($beneficiario) // Se informar o token da conta, não precisa informar os dados do beneficiário
        ->setPagador($pagador)
        ->setEmissao(new \DateTime(date('Y-m-d')))
        ->setVencimento(new \DateTime($dateRandom))
        ->setDocumento('EX1')
        ->setSequencial(1) // Usando o token da conta ele gera automaticamente o nosso número
        ->setNumero(rand(10000000000, 99999999999))
        ->setTitulo('DM')
        ->setValor(121.53)
        ->setInstrucao([
            'Atenção! NÃO RECEBER ESTE BOLETO. ' . date('d-m-y H:i:s'),
            'Este é apenas um teste utilizando a API Boleto Cloud ' . date('d-m-y H:i:s'),
            'Mais info em http://www.boletocloud.com/app/dev/api ' . date('d-m-y H:i:s'),
        ]);

    $arrayBoletos[] = $boleto;
}
// A função abaixo aceita dois parâmetros => ['carne', 'boleto']. Assim utilizando o endpoint correto
$retorno = $client->gerarBoletosEmLote($arrayBoletos, 'carne');
```

### Exemplo de alteração de vencimento de um boleto:
```php
<?php
use BoletoCloud\Api\Boleto\Conta;
use BoletoCloud\Api\Client;

include_once(__DIR__ .'/include/vendor/autoload.php');

$client = new Client([
    'env'   => 'sandbox',
    'token' => 'api_key',
]);

$conta = new Conta();
$conta->setToken('INFORME_O_TOKEN_DA_CONTA');

$tokenBoleto = 'INFORME_O_TOKEN_DO_BOLETO';
$novoVencimento = '2023-03-01';

$retorno = $client->alterarVencimentoBoleto($tokenBoleto, $novoVencimento);
```

### Exemplo de como resgatar o pdf de um Boleto ou Carnê:
```php
<?php

use BoletoCloud\Api\Client;

include_once(__DIR__ .'/include/vendor/autoload.php');

$config = new Config();
$client = new Client([
    'env'   => 'sandbox',
    'token' => 'api_key',
]);

//Informe o token do boleto ou do carnê (lote)
$tokenBoleto = 'INFORME_O_TOKEN_DO_BOLETO';

// Use essa função caso esteja buscando um carnê(lote)
$boleto = $client->resgatarCarne($tokenBoleto);

// Use essa função caso esteja buscando um boleto individual
$boleto = $client->resgatarBoleto($tokenBoleto);

if(!array_key_exists('erro', $boleto)) {
    header('Content-Type: application/pdf; charset=utf-8');
    header('Content-Length: ' . $boleto['content-length']);
    header("Content-Disposition: inline; filename=details.pdf");
    echo $boleto['pdf'];
} else {
    echo('Boleto não encontrado');
}
?>
```
