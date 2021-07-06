<?php
namespace FortyeightDesign\CWAQuicktest\Test;

use FortyeightDesign\CWAQuicktestData;

class CWAQuicktestDataTest extends \PHPUnit\Framework\TestCase
{
    public static $dummyDataAnonymous = array(
      'timestamp' => 1618386548,
      'salt' => '759F8FF3554F0E1BBF6EFF8DE298D9E9'
    );

    public static $dummyDataPersonal = array(
        'timestamp' => 1618386548,
        'fn' => 'Erika',
        'ln' => 'Mustermann',
        'dob' => '1990-12-23',
        'testid' => '52cddd8e-ff32-4478-af64-cb867cea1db5',
        'salt' => '759F8FF3554F0E1BBF6EFF8DE298D9E9'
    );

    public function testGetHashAnonymous() {
        $testObject = new CWAQuicktestData( self::$dummyDataAnonymous );
        $this->assertEquals( $testObject->getHash(), '80232838046d2a65ab1b7a1be3dd1250ba9c91c969476c093bc34001ef460af8' );
    }

    public function testGetHashPersonal() {
        $testObject = new CWAQuicktestData( self::$dummyDataPersonal );
        $this->assertEquals( $testObject->getHash(), '67a50cba5952bf4f6c7eca896c0030516ab2f228f157237712e52d66489d9960' );
    }

    public function testToJSONBase64Anonymous() {
        $testObject = new CWAQuicktestData( self::$dummyDataAnonymous );

        $decodedData = (array)json_decode( base64_decode( $testObject->toJSONBase64() ) );
        unset( $decodedData['hash'] );
        $this->assertEquals( $decodedData, self::$dummyDataAnonymous );
    }

    public function testToJSONBase64Personal() {
        $testObject = new CWAQuicktestData( self::$dummyDataPersonal );

        $decodedData = (array)json_decode( base64_decode( $testObject->toJSONBase64() ) );
        unset( $decodedData['hash'] );
        $this->assertEquals( $decodedData, self::$dummyDataPersonal );
    }
}