<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class IaeSoapAuditService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(
            env('IAE_CLOUD_URL', 'https://iae-sso.virtualfri.id'),
            '/'
        );
    }

    public function sendAudit(
        string $token,
        array $payload
    ): array {

        $xml =
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:iae="http://iae.central/audit">'
            . '<soap:Body>'
            . '<iae:AuditRequest>'
            . '<iae:TeamID>' . env('TEAM_ID', 'TEAM-04') . '</iae:TeamID>'
            . '<iae:ActivityName>LoanApproved</iae:ActivityName>'
            . '<iae:LogContent><![CDATA['
            . json_encode($payload)
            . ']]></iae:LogContent>'
            . '</iae:AuditRequest>'
            . '</soap:Body>'
            . '</soap:Envelope>';

        $response = Http::withToken($token)
            ->withHeaders([
                'Content-Type' => 'text/xml; charset=UTF-8',
                'Accept' => 'application/xml',
            ])
            ->send('POST', $this->baseUrl . '/soap/v1/audit', [
                'body' => $xml
            ]);

        return [
            'success' => $response->successful(),
            'status_code' => $response->status(),
            'raw_response' => $response->body(),
            'receipt_number' => $this->extractTagValue(
                $response->body(),
                'ReceiptNumber'
            ),
            'soap_status' => $this->extractTagValue(
                $response->body(),
                'Status'
            ),
        ];
    }

    private function extractTagValue(
        string $xml,
        string $tagName
    ): ?string {

        if (
            preg_match(
                '/<(?:[^:>]+:)?' . $tagName . '>(.*?)<\/(?:[^:>]+:)?' . $tagName . '>/',
                $xml,
                $matches
            )
        ) {
            return $matches[1];
        }

        return null;
    }
}