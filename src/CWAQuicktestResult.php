<?php
namespace FortyeightDesign;

/**
 * Class for creating objects holding the test result information
 * 
 * Data format according to https://github.com/corona-warn-app/cwa-quicktest-onboarding/wiki/Anbindung-der-Partnersysteme#erstellung-von-qr-codes-f%C3%BCr-corona-warn-app
 * 
 * @package    FortyeightDesign\CWAQuicktest
 * @author     48DESIGN GmbH, Constantin Groß <info@48design.com>
 * @copyright  (c) 2021 48DESIGN
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 * @version    0.1.0
 * @link       https://github.com/48design/cwa-quicktest-php
 */
class CWAQuicktestResult {

    /**
     * @var string the hash of the quick test (also called CWA Test ID)
     */
    public $id = null;
    /**
     * @var int 6 = negative, 7 = positive, 8 = invalid (~ CWAQuicktest::RESULT_NEGATIVE, CWAQuicktest::RESULT_POSITIVE, CWAQuicktest::RESULT_INVALID );
     */
    public $result = null;
    /**
     * @var int unix timestamp of the time when the result was obtained
     */
    public $sc = null;

    /**
     * @param array $data associative array holding the test result information {id: (string) test hash, result: (int) status, sc: (int) timestamp}
     * 
     * @link https://github.com/corona-warn-app/cwa-quicktest-onboarding/wiki/Anbindung-der-Partnersysteme#erstellung-von-qr-codes-f%C3%BCr-corona-warn-app
     */
    function __construct( $data = null ) {
        
        if ( empty( $data ) || !is_array( $data ) ) {
            throw new \Exception( 'The $data parameter needs to be provided as an array' );
        }

        $requiredProperties = array( 'id', 'result', 'sc' );
        foreach ( $requiredProperties as $prop ) {
            if ( !isset( $data['id'] ) ) {
                throw new \Exception( "Missing property '$prop'" );
            }
        }
        foreach ( $requiredProperties as $prop ) {
            if ( empty( $data['id'] ) ) {
                throw new \Exception( "Property '$prop' must not be empty" );
            }
        }

        $allowedStatuses = array( CWAQuicktest::RESULT_NEGATIVE, CWAQuicktest::RESULT_POSITIVE, CWAQuicktest::RESULT_INVALID );
        if ( ! in_array( $data['result'], $allowedStatuses ) ) {
            throw new \Exception( "Invalid value for property 'result' (possible values: " . implode( ',', $allowedStatuses ) . ")" );
        }
        
        if ( !is_int( $data['sc'] ) ) {
            throw new \Exception( "Property 'sc' must be a unix timestamp integer" );
        }

        $this->id = $data['id'];
        $this->result = $data['result'];
        $this->sc = $data['sc'];
    }
}
