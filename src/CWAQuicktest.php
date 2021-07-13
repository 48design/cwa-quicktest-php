<?php
namespace FortyeightDesign;

/**
 * A PHP class that simplifies the integration of the Corona Warn Aapp (CWA; Germany's official Corona virus contact tracing app)
 * by providing the data for QR code generation and handling the communication with the cwa-testresult-server API.
 * @package    FortyeightDesign\CWAQuicktest
 * @author     48DESIGN GmbH, Constantin Groß <info@48design.com>
 * @copyright  (c) 2021 48DESIGN
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 * @version    0.1.0
 * @link       https://github.com/48design/cwa-quicktest-php
 */
class CWAQuicktest {

    /**
     * @var string API URL of the INT stage
     */
    private const STAGE_INT = 'https://quicktest-result-dfe4f5c711db.coronawarn.app';
    /**
     * @var string API URL of the WRU stage
     */
    private const STAGE_WRU = 'https://quicktest-result-cff4f7147260.coronawarn.app';
    /**
     * @var string API URL of the PRODUCTION stage
     */
    private const STAGE_PRODUCTION = 'https://quicktest-result.coronawarn.app';

    /**
     * @var string API endpoint for setting the results, appended to the stage URL
     */
    private const API_ENDPOINT_RESULTS = '/api/v1/quicktest/results';

    /**
     * @var string base URL for the data exchange with the app
     */
    private const APP_BASE_URL = 'https://s.coronawarn.app?v=1';

    /**
     * @var string path to the .cer file as provided via the constructor
     */
    private static $certFile = null;
    /**
     * @var string path to the .key file as provided via the constructor
     */
    private static $keyFile = null;
    /**
     * @var string passphrase for the .key file as provided via the constructor
     */
    private static $keyPass = null;

    /**
     * @var int integer status value for a negative test result
     */
    public const RESULT_NEGATIVE = 6;
    /**
     * @var int integer status value for a positive test result
     */
    public const RESULT_POSITIVE = 7;
    /**
     * @var int integer status value for an invalid test result
     */
    public const RESULT_INVALID = 8;

    /**
     * @var string Which stage to use. Possible values: PRODUCTION|WRU|INT (default: WRU)
     */
    public static $stage = 'WRU';

    /**
     * @param string $certFile path to the .cer file you obtained from T-Systems
     * @param string $keyFile path to the .key file you generated for your certificate signing request to T-Systems
     * @param string $keyPass (optional) the passphrase needed to use the .key file, used when creating the certificate signing request
     * @param bool $skipKeyPassTest (optional) whether to skip checking for key passphrase validity
     */
    function __construct( $certFile, $keyFile, $keyPass = null, $skipKeyPassTest = false ) {
        $bt =  debug_backtrace();

        if ( !is_file( $certFile ) ) {
            throw new \Exception( "The specified path to the .cer file is invalid" );
        } else {
            $certFile = realpath( $certFile );
            if ( !is_file( $certFile ) ) {
                throw new \Exception( "Please provide the .cer file path as an absolute path" );
            }
        }
        
        if ( !is_file( $keyFile ) ) {
           throw new \Exception( "The specified path to the .key file is invalid" );
        } else {
            $keyFile = realpath( $keyFile );
            if ( !is_file( $keyFile ) ) {
                throw new \Exception( "Please provide the .key file path as an absolute path" );
            }
        }

        if ( $skipKeyPassTest !== true ) {
            // the $passphrase parameter was not nullable prior to PHP 8
            $keyPassCheck = ( null === $keyPass
                                ? openssl_pkey_get_private( file_get_contents( $keyFile ) )
                                : openssl_pkey_get_private( file_get_contents( $keyFile ), $keyPass )
                            );

            if ( false === $keyPassCheck ) {
                if ( empty( $keyPass ) ) {
                    throw new \Exception( "The specified key file requires a passphrase" );
                } else {
                    throw new \Exception( "The password provided for the key file is not valid" );
                }
            }
        }

        self::$certFile = $certFile;
        self::$keyFile = $keyFile;
        self::$keyPass = $keyPass;
    }

    /**
     * Returns the API URL depending on the stage configured in self::$stage 
     */
    private static function getStageURL() {
        $allowedStages = array( 'PRODUCTION', 'WRU', 'INT' );

        if ( !in_array( self::$stage, $allowedStages ) ) {
            throw new \Exception( "Invalid stage '" . self::$stage . "'. Supported values are: " . implode(',', $allowedStages) );
        }
        
        switch ( self::$stage ) {
            case 'PRODUCTION':
                return self::STAGE_PRODUCTION;
            case 'INT':
                return self::STAGE_INT;
            case 'WRU':
            default:
                return self::STAGE_WRU;
        }
    }

    /**
     * @param int $width character count of the resulting string
     * 
     * @return string an uppercase 128-bit hex string with a fixed width of 32 (or $width) chars
     */
    public static function getSalt( $width = 32 ) {
        $byteLength = 16;

        if ( ! function_exists( 'random_bytes' ) && ! function_exists( 'openssl_random_pseudo_bytes' ) ) {
            throw new \Exception( 'This system does neither support random_bytes() nor openssl_random_pseudo_bytes(), either of which is required to generate a cryptographically random salt' );
        }
        
        $randomBytes = function_exists( 'random_bytes' ) ? random_bytes( $byteLength ) : openssl_random_pseudo_bytes( $byteLength );

        $hexValue = strtoupper( bin2hex( $randomBytes ) );

        // this should never happen, but better be safe than sorry
        if ( strlen( $hexValue ) !== $width ) {
            throw new \Exception( 'The generated salt did not have the expected length' );
        }

        return $hexValue;
    }

    /**
     * Sends an array of results to the cwa-testresult-server API
     * 
     * @param CWAQuicktestResult[] $results an array of one or more CWAQuicktestResult objects
     * 
     * @return bool|object boolean true on success, otherwise the JSON decoded response object if available, or an object containing the HTTP status code as property "status" and the response body as property "response"
     */
    public function sendResults( $results = array() ) {

        // dummy data:
        // $results = array(
        //     (object)array(
        //         "id" => "484848484852bf4f6c7eca896c0030516ab2f228f157237712e52d66489d9960",
        //         "result" => 6,
        //         "sc"  => 1625125748
        //     )
        // );

        $postdata = json_encode(
            (object)array(
                'testResults' => $results
            )
        );

        $ch = curl_init();

        $curlOpts = array(
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postdata,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSLKEY => self::$keyFile,
            CURLOPT_SSLCERT => self::$certFile,
            CURLOPT_KEYPASSWD => self::$keyPass,
            CURLOPT_URL => self::getStageURL() . self::API_ENDPOINT_RESULTS,
            CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
        );
        
        curl_setopt_array($ch , $curlOpts);
        
        $output = curl_exec( $ch ) ;
        
        if ( curl_errno( $ch ) ) {
          $error_msg = curl_error( $ch );
          print "curl_error: $error_msg <br>";
        } else {
            $status = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
            if ( 204 === $status ) {
                return true;
            } else {
                $response = json_decode($output);
                if ( is_object( $response ) ) {
                    return $response;
                } else {
                    return (object)array(
                        'status' => $status,
                        'response' => $response
                    );
                }
            }
        }
    }

    /**
     * Returns the URL for use with a QR code generation library (or for redirecting the user directly to the Corona Warn App in a mobile environment)
     * 
     * @param array|CWAQuicktestData An array or CWAQuicktestData object holding the test data
     * 
     * @return string The URL containing the data needed by the Corona Warn App
     */
    public function getDataURL( $data = array() ) {
        $testObject =
            is_object( $data ) && get_class( $data ) === 'FortyeightDesign\CWAQuicktestData'
            ? $data
            : new CWAQuicktestData( $data );

        return self::APP_BASE_URL . '#' . $testObject->toJSONBase64();
    }
}
