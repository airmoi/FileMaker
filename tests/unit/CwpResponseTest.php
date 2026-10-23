<?php

namespace airmoi\FileMaker\Tests\Unit;

use airmoi\FileMaker\FileMakerException;
use airmoi\FileMaker\Tests\Support\FakeFileMaker;
use airmoi\FileMaker\Tests\Support\Fixtures;
use PHPUnit\Framework\TestCase;

/**
 * Non-XML responses used to end with "XML error: Not well-formed (invalid token) at line 1"
 * @see https://github.com/airmoi/FileMaker/issues/50
 */
class CwpResponseTest extends TestCase
{
    private function fm(array $options = []): FakeFileMaker
    {
        return new class ('test', 'http://fms.example/', 'admin', 'secret', $options) extends FakeFileMaker {
            public function check($body, $httpCode, $contentType = '', $headers = '')
            {
                return $this->checkCwpResponse($body, $httpCode, $contentType, $headers);
            }
        };
    }

    public function testValidXmlIsReturned(): void
    {
        $xml = Fixtures::fmResultSet();
        $this->assertSame($xml, $this->fm()->check($xml, 200, 'text/xml;charset=UTF-8'));
    }

    public function testBomAndLeadingWhitespacesAreRemoved(): void
    {
        $xml = Fixtures::fmResultSet();
        $this->assertSame($xml, $this->fm()->check("\xEF\xBB\xBF\r\n  " . $xml, 200));
    }

    public function testRedirectIsReported(): void
    {
        $headers = "HTTP/1.1 301 Moved Permanently\r\nLocation: https://fms.example/fmi/xml/fmresultset.xml\r\n\r\n";
        try {
            $this->fm()->check('<html><body>Moved</body></html>', 301, 'text/html', $headers);
            $this->fail('Exception expected');
        } catch (FileMakerException $e) {
            $this->assertStringContainsString('redirected the request (HTTP 301) to https://fms.example/fmi/xml/fmresultset.xml', $e->getMessage());
            $this->assertStringContainsString('hostspec', $e->getMessage());
        }
    }

    public function testHtmlPageIsReported(): void
    {
        $html = "<!DOCTYPE html>\n<html><head><title>FileMaker Server</title></head><body><h1>XML Web Publishing is disabled</h1></body></html>";
        try {
            $this->fm()->check($html, 200, 'text/html');
            $this->fail('Exception expected');
        } catch (FileMakerException $e) {
            $this->assertStringContainsString('did not return an XML document (HTTP 200, text/html)', $e->getMessage());
            $this->assertStringContainsString('XML Web Publishing is disabled', $e->getMessage());
            $this->assertStringContainsString('http://fms.example/fmi/xml/fmresultset.xml', $e->getMessage());
            $this->assertStringNotContainsString('XML error', $e->getMessage());
        }
    }

    public function testEmptyResponseWithDefaultErrorHandling(): void
    {
        $result = $this->fm(['errorHandling' => 'default'])->check('', 200);
        $this->assertInstanceOf(FileMakerException::class, $result);
        $this->assertStringContainsString('did not return an XML document (HTTP 200)', $result->getMessage());
    }

    public function testLayoutGrammarIsAccepted(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><FMPXMLLAYOUT xmlns="http://www.filemaker.com/fmpxmllayout"></FMPXMLLAYOUT>';
        $this->assertSame($xml, $this->fm()->check($xml, 200));
        $this->assertSame('<FMPXMLLAYOUT/>', $this->fm()->check('<FMPXMLLAYOUT/>', 200));
    }
}
