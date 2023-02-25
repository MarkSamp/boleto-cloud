<?php

namespace BoletoCloud\Api;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;

/**
 * Class Boleto.
 */
class Client
{
    private const HOSTNAME_PROD = 'https://app.boletocloud.com/api/v1/';

    private const HOSTNAME_SANDBOX = 'https://sandbox.boletocloud.com/api/v1/';

    /**
     * @var string
     */
    private $env = 'sandbox';

    /**
     * @var string
     */
    private $token;

    /**
     * @var GuzzleClient
     */
    private $httpClient;

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * Boleto constructor.
     *
     * @param array $params
     *
     * @throws \Exception
     */
    public function __construct(array $params = [])
    {
        if (!empty($params['env'])) {
            $this->env = $params['env'];
        }
        if (!empty($params['token'])) {
            $this->token = $params['token'];
        } elseif (!empty(getenv('BOLETO_CLOUD_TOKEN'))) {
            $this->token = getenv('BOLETO_CLOUD_TOKEN');
        } else {
            throw new \Exception('Token n&atilde;o informado.');
        }

        $this->buildClient();
    }

    /**
     * Set default options for Guzzle\Client.
     */
    public function buildClient(): void
    {
        $this->baseUrl = (($this->env == 'sandbox') ? self::HOSTNAME_SANDBOX : self::HOSTNAME_PROD);
        $this->httpClient = new GuzzleClient([
            'base_uri' => $this->baseUrl,
            'auth'     => [$this->token, 'token'],
        ]);
    }

    /**
     * @param Boleto $boleto
     *
     * @return array|mixed
     */
    public function gerarBoleto(Boleto $boleto)
    {
        try {
            $response = $this->httpClient->post('boletos', [
                'form_params' => $boleto->parser('boleto'),
                'query'       => $boleto->getInstrucao(),
            ]);

            $boletoUrl = str_replace('/api/v1/', '', $this->baseUrl);
            $boletoUrl = $boletoUrl.$response->getHeader('Location')[0];

            return [
                'boleto_url'   => $boletoUrl,
                'boleto_token' => $response->getHeader('X-BoletoCloud-Token')[0],
                'boleto_nosso_numero' => $response->getHeader('X-BoletoCloud-NIB-Nosso-Numero')[0],
                'pdf'          => $response->getBody(),
                'request'      => $response,
            ];
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
    }

    /**
     * @param string $token
     *
     * @return array|mixed
     */
    public function resgatarBoleto(string $token)
    {
        try {

            $response = $this->httpClient->get('boletos/'.$token);

            return [
                'pdf'          => $response->getBody(),
                'request'      => $response,
            ];
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
    }

    /**
     * @param string $token
     *
     * @return array|mixed
     */
    public function resgatarCarne(string $token)
    {
        try {

            $response = $this->httpClient->get('batch/boletos/'.$token);

            return [
                'content-length' => $response->getHeader('Content-Length')[0],
                'pdf'          => $response->getBody()->getContents(),
                'request'      => $response,
            ];
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
    }

    /**
     * @param Boleto\Conta $conta
     *
     * @return array|mixed
     */
    public function exportarArquivoRemessa(Boleto\Conta $conta)
    {
        try {
            $response = $this->httpClient->post('arquivos/cnab/remessas', [
                'form_params' => $conta->parser('remessa'),
            ]);

            if ($response->getStatusCode() != 201) {
                // Nenhum boleto para remessa ou algum outro erro ocorreu
                return [
                    'arquivo_url'  => null,
                    'arquivo_nome' => null,
                    'arquivo'      => null,
                    'request'      => $response,
                ];
            }

            $arquivoUrl = str_replace('/api/v1/', '', $this->baseUrl);
            $arquivoUrl = $arquivoUrl.$response->getHeader('Location')[0];

            $contentDisposition = $response->getHeader('Content-Disposition');
            if (!empty($contentDisposition[0])) {
                $parts = explode('filename=', $contentDisposition[0]);
                $arquivoNome = (!empty($parts[1])) ? $parts[1] : null;
            } else {
                $arquivoNome = null;
            }

            return [
                'arquivo_url'  => $arquivoUrl,
                'arquivo_nome' => $arquivoNome,
                'arquivo'      => $response->getBody(),
                'token'        => $response->getHeader('X-BoletoCloud-Token')[0],
                'request'      => $response,
            ];
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
    }

    /**
     * @param string $arquivo
     *
     * @return array|mixed
     */
    public function processarArquivoRetorno(string $arquivo)
    {
        try {
            $response = $this->httpClient->post('arquivos/cnab/retornos', [
                'multipart' => [
                    [
                        'name'     => 'arquivo',
                        'contents' => fopen($arquivo, 'r'),
                    ],
                ],
            ]);

            if ($response->getStatusCode() != 201) {
                // Arquivo nao processado ou ja processado anteriormente
                return [
                    'arquivo'      => null,
                    'json'         => null,
                    'request'      => $response,
                ];
            }

            return [
                'arquivo'      => $response->getBody(),
                'json'         => json_decode($response->getBody()->getContents(), true),
                'token'        => $response->getHeader('X-BoletoCloud-Token')[0],
                'request'      => $response,
            ];
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
    }

    /**
     * @param string $token_boleto
     * @param string $vencimento (Y/m/d)
     *
     * @return array|mixed
     */
    public function alterarVencimentoBoleto(string $token_boleto, string $vencimento)
    {
        try {
            $response = $this->httpClient->put('boletos/' . $token_boleto . '/vencimento', [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'body' => json_encode([
                    'boleto' => [
                        'vencimento' => $vencimento
                    ]
                ])
            ]);

            return [
                'arquivo'      => $response->getBody(),
                'token'        => $response->getHeader('X-BoletoCloud-Token')[0],
                'request'      => $response,
            ];

        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
    }

    /**
     * @param string $token_boleto
     * @param string $motivo
     *
     * @return array|mixed
     */
    public function cancelarBoleto(string $token_boleto, string $motivo = null): bool
    {
        try {
            $motivo = ($motivo !== '' or !is_null($motivo)) ? $motivo: null;
            $response = $this->httpClient->put('boletos/' . $token_boleto . '/baixa', [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'body' => json_encode([
                    'boleto' => [
                        'baixa' => [
                            'motivo' => $motivo
                        ]
                    ]
                ])
            ]);

            if ($response->getStatusCode() == 409) {
                // Arquivo já baixado/cancelado
                return false;
            }

            return true;
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
    }

    /**
     * @param string $data
     * @param Boleto\Conta $conta
     *
     * @return array|mixed
     */
    public function getTokensArquivos(string $data, Boleto\Conta $conta): bool|array
    {
        try {

            $response = $this->httpClient->get('arquivos/cnab/retornos', [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'query' => [
                    'data' => $data,
                    'conta' => $conta->getToken()
                ]
            ]);

            if ($response->getStatusCode() == 200) {
                return [
                    'arquivos' => json_decode($response->getBody()->getContents(), true)['retornos']['arquivos'],
                    //'request' => $response
                ];
            }
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
    }

    /**
     * @param array Boleto $boletos
     * @param string $tipo
     *
     * @return array|mixed
     */
    public function gerarBoletosEmLote(array $boletos, $tipo = 'carne'):array
    {
        try {

            $uri = ($tipo == 'carne') ? 'carnes' : 'batch/boletos';


            foreach ($boletos as $key => $value) {
                $dadosBoleto[] = $value->parserCarne();
            }

            //$batch = json_encode(array('batch' => array('boletos' => $dadosBoleto)), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $batch = array('batch' => array('boletos' => $dadosBoleto));

            $response = $this->httpClient->post($uri, [
                'headers' => [
                    'Content-Type' => 'application/json; charset=utf-8'
                ],
                'json' => $batch
            ]);

            return [
                'body' => json_decode($response->getBody()->getContents()),
                'location' => $response->getHeader('Location')[0],
                //'request' => $response
            ];
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
    }
}
