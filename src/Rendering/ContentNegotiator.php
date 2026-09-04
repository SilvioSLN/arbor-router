<?php

declare(strict_types=1);

namespace Arbor\Router\Rendering;

/**
 * Negociador de conteúdo baseado no header Accept.
 *
 * Parseia o header Accept HTTP e determina o formato de saída
 * ideal baseado nos tipos de mídia aceitos e suas qualidades (q-values).
 *
 * Suporta os seguintes formatos:
 * - JSON: application/json
 * - XML: application/xml, text/xml
 * - HTML: text/html
 * - TEXT: text/plain
 *
 * @package Arbor\Router\Rendering
 */
class ContentNegotiator
{
    /**
     * Formato JSON.
     */
    public const FORMAT_JSON = 'json';

    /**
     * Formato XML.
     */
    public const FORMAT_XML = 'xml';

    /**
     * Formato HTML.
     */
    public const FORMAT_HTML = 'html';

    /**
     * Formato texto plano.
     */
    public const FORMAT_TEXT = 'text';

    /**
     * Mapeamento de MIME types para formatos internos.
     */
    private const MIME_MAP = [
        'application/json' => self::FORMAT_JSON,
        'text/json' => self::FORMAT_JSON,
        'application/xml' => self::FORMAT_XML,
        'text/xml' => self::FORMAT_XML,
        'text/html' => self::FORMAT_HTML,
        'application/xhtml+xml' => self::FORMAT_HTML,
        'text/plain' => self::FORMAT_TEXT,
    ];

    /**
     * Mapeamento de formatos para Content-Types.
     */
    private const FORMAT_CONTENT_TYPES = [
        self::FORMAT_JSON => 'application/json; charset=utf-8',
        self::FORMAT_XML => 'application/xml; charset=utf-8',
        self::FORMAT_HTML => 'text/html; charset=utf-8',
        self::FORMAT_TEXT => 'text/plain; charset=utf-8',
    ];

    /**
     * Determina o formato ideal baseado no header Accept.
     *
     * Algoritmo:
     * 1. Parseia o Accept header em entries com q-values
     * 2. Ordena por qualidade (maior primeiro)
     * 3. Para cada entry, verifica se corresponde a um formato suportado
     * 4. Retorna o primeiro formato correspondente
     * 5. Se nenhum corresponder, retorna o formato padrão
     *
     * @param string|null $acceptHeader Valor do header Accept
     * @param string $defaultFormat Formato padrão (default: json)
     * @return string Formato negociado (json|xml|html|text)
     */
    public function negotiate(?string $acceptHeader, string $defaultFormat = self::FORMAT_JSON): string
    {
        if ($acceptHeader === null || $acceptHeader === '' || $acceptHeader === '*/*') {
            return $defaultFormat;
        }

        $entries = $this->parseAcceptHeader($acceptHeader);

        foreach ($entries as $entry) {
            $mimeType = $entry['type'];

            // Wildcard — retorna default
            if ($mimeType === '*/*') {
                return $defaultFormat;
            }

            // Match exato
            if (isset(self::MIME_MAP[$mimeType])) {
                return self::MIME_MAP[$mimeType];
            }

            // Match por tipo genérico (ex: application/* → json)
            if (str_ends_with($mimeType, '/*')) {
                $baseType = explode('/', $mimeType)[0];
                foreach (self::MIME_MAP as $mime => $format) {
                    if (str_starts_with($mime, $baseType . '/')) {
                        return $format;
                    }
                }
            }
        }

        return $defaultFormat;
    }

    /**
     * Retorna o Content-Type correspondente a um formato.
     *
     * @param string $format Formato (json|xml|html|text)
     * @return string Content-Type header value
     */
    public function getContentType(string $format): string
    {
        return self::FORMAT_CONTENT_TYPES[$format] ?? self::FORMAT_CONTENT_TYPES[self::FORMAT_JSON];
    }

    /**
     * Parseia o header Accept em entries ordenadas por qualidade.
     *
     * Exemplo de input: "text/html, application/json;q=0.9, *\/*;q=0.1"
     *
     * @param string $acceptHeader Valor do header Accept
     * @return array<int, array{type: string, quality: float}> Entries ordenadas
     */
    private function parseAcceptHeader(string $acceptHeader): array
    {
        $entries = [];
        $parts = explode(',', $acceptHeader);

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            $segments = explode(';', $part);
            $mimeType = strtolower(trim($segments[0]));
            $quality = 1.0;

            // Extrai q-value
            for ($i = 1; $i < count($segments); $i++) {
                $param = trim($segments[$i]);
                if (str_starts_with($param, 'q=')) {
                    $quality = (float) substr($param, 2);
                    break;
                }
            }

            $entries[] = [
                'type' => $mimeType,
                'quality' => $quality,
            ];
        }

        // Ordena por qualidade decrescente, mantendo ordem original para empates
        usort($entries, function (array $a, array $b): int {
            if ($a['quality'] === $b['quality']) {
                return 0;
            }
            return $b['quality'] <=> $a['quality'];
        });

        return $entries;
    }
}
