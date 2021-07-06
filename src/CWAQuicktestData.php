<?php
namespace FortyeightDesign;

/**
 * Class for creating objets holding the test information and auto-computing the hashes
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
class CWAQuicktestData {

    // fields used for both anonymous and personal data transfer
    private $timestamp = null;
    private $salt = null;
    private $hash = null;

    public const PERSONAL_DATA_FIELDS = array( 'fn', 'ln', 'dob', 'testid' );
    
    // fields used only for personal data transfer
    private $fn = null; // first name
    private $ln = null; // last name
    private $dob = null; // date of birth
    private $testid = null;

    private $isAnononymous = true;

    /**
     * @param array $data associative array holding the test information, with the keys corresponding to the fields defined by the API URL.
     * @link https://github.com/corona-warn-app/cwa-quicktest-onboarding/wiki/Anbindung-der-Partnersysteme#erstellung-von-qr-codes-f%C3%BCr-corona-warn-app
     */
    function __construct( $data ) {
        // check if we want to transfer data anonymously or including personal data
        $personalDataFields = self::PERSONAL_DATA_FIELDS;
        $personalDataFieldsDiff = array_diff_key( array_flip( $personalDataFields ), $data );
        $this->isAnononymous = count( $personalDataFieldsDiff ) === count( $personalDataFields );

        // if one personal data field is set, but not all of them, throw an error
        if (! $this->isAnononymous && !! $personalDataFieldsDiff) {
            throw new \Exception( 'Either all of the personal data fields have to be set, or none of them. Required personal data fields are: ' . implode( ', ', $personalDataFields )  );
        }

        $this->validateData( $data );
    }

    /**
     * validates the data input to the constructor
     * 
     * @param array the same $data array as supplied to the constructor
    */
    private function validateData( $data ) {
        if ( ! isset( $data['timestamp'] ) ) {
            throw new \Exception( "Required field 'timestamp' is missing" );
        }

        if ( ! ( is_int ( $data['timestamp'] ) && $data['timestamp'] > 0 && $data['timestamp'] <= PHP_INT_MAX ) ) {
            throw new \Exception( "Invalid format for 'timestamp': This should be a unix timestamp" );
        }

        if ( !isset( $data['salt'] ) ) {
            throw new \Exception( "Field 'salt' should not be empty" );
        }

        if ( !preg_match( '/^[ABCDEF0-9]{32}$/', $data['salt'] ) ) {
            throw new \Exception( "Invalid format for 'salt': This must be an uppercase 128-bit hex string with a fixed width of 32 chars" );
        }

        // validate personal data if not anonymous
        if ( ! $this->isAnononymous ) {
            foreach ( self::PERSONAL_DATA_FIELDS as $fieldName ) {
                if ( empty( $data[$fieldName] ) ) {
                    throw new \Exception( sprintf( "Field '%s' should not be empty", $fieldName ) );
                }
            }
        }
    }

}
