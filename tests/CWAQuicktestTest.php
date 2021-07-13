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
    
    public function testConstructorSkipPasstest() {
        $this->assertInstanceOf( 'FortyeightDesign\\CWAQuicktest', new CWAQuicktest( __DIR__ . '/data/test.cer', __DIR__ . '/data/test.key', null, true ) );
        $this->assertInstanceOf( 'FortyeightDesign\\CWAQuicktest', new CWAQuicktest( __DIR__ . '/data/test.cer', __DIR__ . '/data/test.key', 'xxx', true ) );
    }

    public function testConstructorSucceeds() {
        $CWAQuicktest = self::getWorkingTestObject();
        $this->assertTrue(gettype($CWAQuicktest) === 'object');
    }

    public function testSendResultsFailingWithEmptyResults() {
        $CWAQuicktest = $this->getWorkingRealObject();
        $result = $CWAQuicktest->sendResults(
            array()
        );
        $this->assertEquals( (object)array( 'status' => 400, 'response' => null ), $result );
    }

    public function testSendResultsFailingWithInvalidID() {
        $CWAQuicktest = $this->getWorkingRealObject();
        $result = $CWAQuicktest->sendResults(
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
        $CWAQuicktest = $this->getWorkingRealObject();
        $result = $CWAQuicktest->sendResults(
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
        $CWAQuicktest = $this->getWorkingRealObject();
        $result = $CWAQuicktest->sendResults(
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

    public function testGetDataURLAnonymous() {
        $CWAQuicktest = self::getWorkingTestObject();
        $this->assertEquals(
            $CWAQuicktest->getDataURL( CWAQuicktestDataTest::$dummyDataAnonymous ),
            'https://s.coronawarn.app?v=1#eyJ0aW1lc3RhbXAiOjE2MTgzODY1NDgsInNhbHQiOiI3NTlGOEZGMzU1NEYwRTFCQkY2RUZGOERFMjk4RDlFOSIsImhhc2giOiI4MDIzMjgzODA0NmQyYTY1YWIxYjdhMWJlM2RkMTI1MGJhOWM5MWM5Njk0NzZjMDkzYmMzNDAwMWVmNDYwYWY4In0='
        );
    }
    
    public function testGetDataURLPersonal() {
        $CWAQuicktest = self::getWorkingTestObject();
        $this->assertEquals(
            $CWAQuicktest->getDataURL( CWAQuicktestDataTest::$dummyDataPersonal ),
            'https://s.coronawarn.app?v=1#eyJkb2IiOiIxOTkwLTEyLTIzIiwiZm4iOiJFcmlrYSIsImxuIjoiTXVzdGVybWFubiIsInRpbWVzdGFtcCI6MTYxODM4NjU0OCwidGVzdGlkIjoiNTJjZGRkOGUtZmYzMi00NDc4LWFmNjQtY2I4NjdjZWExZGI1Iiwic2FsdCI6Ijc1OUY4RkYzNTU0RjBFMUJCRjZFRkY4REUyOThEOUU5IiwiaGFzaCI6IjY3YTUwY2JhNTk1MmJmNGY2YzdlY2E4OTZjMDAzMDUxNmFiMmYyMjhmMTU3MjM3NzEyZTUyZDY2NDg5ZDk5NjAifQ=='
        );
    }

    public function testGetDataURLWithObject() {
        $CWAQuicktest = self::getWorkingTestObject();
        $this->assertEquals(
            $CWAQuicktest->getDataURL( new \FortyeightDesign\CWAQuicktestData( CWAQuicktestDataTest::$dummyDataPersonal ) ),
            'https://s.coronawarn.app?v=1#eyJkb2IiOiIxOTkwLTEyLTIzIiwiZm4iOiJFcmlrYSIsImxuIjoiTXVzdGVybWFubiIsInRpbWVzdGFtcCI6MTYxODM4NjU0OCwidGVzdGlkIjoiNTJjZGRkOGUtZmYzMi00NDc4LWFmNjQtY2I4NjdjZWExZGI1Iiwic2FsdCI6Ijc1OUY4RkYzNTU0RjBFMUJCRjZFRkY4REUyOThEOUU5IiwiaGFzaCI6IjY3YTUwY2JhNTk1MmJmNGY2YzdlY2E4OTZjMDAzMDUxNmFiMmYyMjhmMTU3MjM3NzEyZTUyZDY2NDg5ZDk5NjAifQ=='
        );
    }
}