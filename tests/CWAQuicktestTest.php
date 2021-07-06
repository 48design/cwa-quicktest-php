<?php
namespace FortyeightDesign\CWAQuicktest\Test;

use FortyeightDesign\CWAQuicktest;

class CWAQuicktestTest extends \PHPUnit\Framework\TestCase
{
    private static function getWorkingTestObject() {
        return new CWAQuicktest( __DIR__ . '/data/test.cer', __DIR__ . '/data/test.key', 'CWAQuicktestPassphrase' );
    }

    private static function getWorkingRealObject() {
        return new CWAQuicktest(
            __DIR__ . '/../../working_wru.cer',
            __DIR__ . '/../../working_wru.key',
            file_get_contents( __DIR__ . '/../../working_wru.pass' )
        );
    }

    public function testConstructorFailsWithMissingCertificateFile() {
        $this->expectExceptionMessageMatches("/^The specified path to the .cer file is invalid$/");
        new CWAQuicktest( __DIR__ . '/data/testx.cer', __DIR__ . '/data/test.key' );
    }

    public function testConstructorFailsWithMissingKeyFile() {
        $this->expectExceptionMessageMatches("/^The specified path to the .key file is invalid$/");
        new CWAQuicktest( __DIR__ . '/data/test.cer', __DIR__ . '/data/testx.key' );
    }

    public function testConstructorFailsWithMissingPassphrase() {
        $this->expectExceptionMessageMatches("/^The specified key file requires a passphrase$/");
        new CWAQuicktest( __DIR__ . '/data/test.cer', __DIR__ . '/data/test.key' );
    }

    public function testConstructorFailsWithIncorrectPassphrase() {
        $this->expectExceptionMessageMatches("/^The password provided for the key file is not valid$/");
        new CWAQuicktest( __DIR__ . '/data/test.cer', __DIR__ . '/data/test.key', 'xxx' );
    }

    public function testConstructorSucceeds() {
        $test = self::getWorkingTestObject();
        $this->assertTrue(gettype($test) === 'object');
    }

    public function testSendResultsFailingWithEmptyResults() {
        $test = $this->getWorkingRealObject();
        $result = $test->sendResults(
            array()
        );
        $this->assertEquals( (object)array( 'status' => 400, 'response' => null ), $result );
    }

    public function testSendResultsFailingWithInvalidID() {
        $test = $this->getWorkingRealObject();
        $result = $test->sendResults(
            (object)array(
              "id" => "x",
              "result" => 6,
              "sc"  => time()
            )
        );
        $this->assertEquals( 400, $result->status );
        $this->assertEquals( 'Bad Request', $result->error );
    }

    public function testSendResultsFailingWithInvalidResultCode() {
        $test = $this->getWorkingRealObject();
        $result = $test->sendResults(
            (object)array(
              "id" => "484848484852bf4f6c7eca896c0030516ab2f228f157237712e52d66489d996f",
              "result" => 1,
              "sc"  => time()
            )
        );
        $this->assertEquals( 400, $result->status );
        $this->assertEquals( 'Bad Request', $result->error );
    }

    public function testSendResults() {
        $test = $this->getWorkingRealObject();
        $result = $test->sendResults(
            array(
                (object)array(
                  "id" => "484848484852bf4f6c7eca896c0030516ab2f228f157237712e52d66489d996f",
                  "result" => 6,
                  "sc"  => time()
                )
            )
        );
        $this->assertTrue( $result );
    }
}