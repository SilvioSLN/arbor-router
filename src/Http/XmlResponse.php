<?php

declare(strict_types=1);

namespace Arbor\Router\Http;

/**
 * Response HTTP para conteúdo XML.
 *
 * Converte automaticamente arrays associativos para XML ou
 * aceita XML como string direta.
 *
 * @package Arbor\Router\Http
 */
class XmlResponse extends Response
{
    /**
     * @param mixed $data Dados a converter para XML (array ou string XML)
     * @param int $statusCode Código HTTP (default: 200)
     * @param array<string, string> $headers Headers adicionais
     * @param string $rootElement Nome do elemento raiz para arrays
     */
    public function __construct(
        mixed $data = null,
        int $statusCode = 200,
        array $headers = [],
        string $rootElement = 'response',
    ) {
        if (is_string($data)) {
            $body = $data;
        } else {
            $body = self::arrayToXml($data, $rootElement);
        }

        $headers['content-type'] = 'application/xml; charset=utf-8';
        parent::__construct($body, $statusCode, $headers);
    }

    /**
     * Converte um array associativo para XML.
     *
     * Suporta arrays aninhados e listas. Para listas, usa o nome
     * da chave pai no singular como tag do item.
     *
     * @param mixed $data Dados a converter
     * @param string $rootElement Nome do elemento raiz
     * @param \SimpleXMLElement|null $xml Elemento XML atual (para recursão)
     * @return string XML formatado
     */
    public static function arrayToXml(
        mixed $data,
        string $rootElement = 'response',
        ?\SimpleXMLElement $xml = null,
    ): string {
        if ($xml === null) {
            $xml = new \SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?><{$rootElement}/>");
        }

        if (!is_array($data)) {
            return $xml->asXML() ?: '';
        }

        foreach ($data as $key => $value) {
            if (is_int($key)) {
                $key = 'item';
            }

            if (is_array($value)) {
                // Verifica se é uma lista (array sequencial)
                if (array_is_list($value)) {
                    foreach ($value as $item) {
                        if (is_array($item)) {
                            $child = $xml->addChild($key);
                            self::arrayToXml($item, $rootElement, $child);
                        } else {
                            $xml->addChild($key, htmlspecialchars((string) $item, ENT_XML1, 'UTF-8'));
                        }
                    }
                } else {
                    $child = $xml->addChild($key);
                    self::arrayToXml($value, $rootElement, $child);
                }
            } else {
                $xml->addChild($key, htmlspecialchars((string) ($value ?? ''), ENT_XML1, 'UTF-8'));
            }
        }

        return $xml->asXML() ?: '';
    }
}
