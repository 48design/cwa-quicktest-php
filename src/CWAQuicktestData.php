<?php
namespace FortyeightDesign;

/**
 * Class for creating objects holding the test information and auto-computing the hashes
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

        $this->timestamp = $data['timestamp'];
        $this->salt = $data['salt'];

        if ( ! $this->isAnononymous ) {
            $this->fn = $data['fn'];
            $this->ln = $data['ln'];
            $this->dob = $data['dob'];
            $this->testid = $data['testid'];
        }
    }

    /**
     * validates the data input to the constructor
     * 
     * @param array the same $data array as supplied to the constructor
    */
    private function validateData( $data ) {
        // validate that required fields (depending on anonymous or personal transfer) are present
        foreach ( array_merge( array( 'salt', 'timestamp' ), $this->isAnononymous ? array() : self::PERSONAL_DATA_FIELDS ) as $fieldName ) {
            if ( ! isset( $data[$fieldName] ) ) {
                throw new \Exception( sprintf( "Required field '%s' is missing", $fieldName ) );
            }
        }

        if ( ! ( is_int ( $data['timestamp'] ) && $data['timestamp'] > 0 && $data['timestamp'] <= PHP_INT_MAX ) ) {
            throw new \Exception( "Invalid format for 'timestamp': This must be a unix timestamp" );
        }

        if ( empty( $data['salt'] ) ) {
            throw new \Exception( "Field 'salt' must not be empty" );
        }

        if ( !preg_match( '/^[ABCDEF0-9]{32}$/', $data['salt'] ) ) {
            throw new \Exception( "Invalid format for 'salt': This must be an uppercase 128-bit hex string with a fixed width of 32 chars" );
        }
    }

    /**
     * @param bool $includeHash Whether to include the hash in the returned value
     * 
     * @return array associative array of the fields and values
     */
    public function getData( $includeHash = true ) {
        // IMPORTANT: key order matters, because these arrays are also imploded to build the data string for hash generation
        $dataArray = $this->isAnononymous
            ? array(
                'timestamp' => $this->timestamp,
                'salt' => $this->salt
            )
            : array(
                'dob' => $this->dob,
                'fn' => $this->fn,
                'ln' => $this->ln,
                'timestamp' => $this->timestamp,
                'testid' => $this->testid,
                'salt' => $this->salt
            );

        if ( $includeHash ) {
            $dataArray['hash'] = $this->getHash();
        }

        return $dataArray;
    }

    /**
     * @return object The data from getData() as stdObject
     */
    public function getDataObject() {
        return (object)$this->getData( true );
    }

    /**
     * @return string the SHA256 hash of the data
     */
    public function getHash() {
        $dataString = implode( '#', $this->getData( false ) );

        return hash( 'sha256', $dataString );
    }

    /**
     * @return string JSON representation of the data, including the hash
     */
    public function toJSON() {
        // in order to use the same representation with no linebreaks but single spaces
        // as in the CWA documentation for consistency when testing with the dummy data
        // we could use the following line:
        // 
        // return preg_replace( "/\n(( ){4}|(}$))/", " $3", json_encode( $this->getDataObject(), JSON_PRETTY_PRINT ) );
        // 
        // however, the examples don't use consistent ordering of the keys in all places,
        // so we might as well use as little space as necessary

        return json_encode( $this->getDataObject() );
    }

    /**
     * @return string base64 encoded JSON representation of the data, as needed for building the URL for the app
     */
    public function toJSONBase64() {
        return base64_encode( $this->toJSON() );
    }

}
