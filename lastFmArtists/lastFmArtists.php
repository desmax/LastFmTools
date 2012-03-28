<?php
/**
 * Interacts with last fm api to get list of artists from user library
 * Uses command line params
 */
class lastFmArtists {
    /**
     * LastFm username to fetch artists.
     * @var string $_username
     * Add param "--user=username" to command line to specify this
     * This is a required param
     */
    protected $_username = null;

    /**
     * Fetch all artist that have more than $_playsLimit plays
     * @var int $_playsLimit
     * Add param "--plays-limit=1" to command line to override this
     */
    protected $_playsLimit = 1;

    /**
     * Maximum artists that can be processed
     * @var int $_artistsLimit
     * Add param "--artists-limit=9999" to command line to override this
     */
    protected $_artistsLimit = 9999;

    /**
     * @var null|string $_outputFile
     * File to write down script output. By default outputs to stdout
     * Add param "--file=filepath" to command line to override this
     */
    protected $_outputFile = null;

    /**
     * Used to interact with lastFm api
     * @var string API_KEY
     */
    CONST API_KEY = 'b25b959554ed76058ac220b7b2e0a026';

    /**
     * Command line key to specify lastFm user name. Short and long options.
     * @var string USER_KEY
     * @var string USER_SHORT_KEY
     */
    CONST USER_KEY = 'user';
    CONST USER_SHORT_KEY = 'u';

    /**
     * Command line key to specify plays limit. Short and long options.
     * @var string PLAYS_LIMIT_KEY
     * @var string PLAYS_LIMIT_SHORT_KEY
     */
    CONST PLAYS_LIMIT_KEY = 'plays-limit';
    CONST PLAYS_LIMIT_SHORT_KEY = 'p';

    /**
     * Command line key to specify artists limit. Short and long options.
     * @var string ARTISTS_LIMIT_KEY
     * @var string ARTISTS_LIMIT_SHORT_KEY
     */
    CONST ARTISTS_LIMIT_KEY = 'artists-limit';
    CONST ARTISTS_LIMIT_SHORT_KEY = 'a';

    /**
     * Command line key to specify output file name. Short and long options.
     * @var string FILE_KEY
     * @var string FILE_SHORT_KEY
     */
    CONST FILE_KEY = 'file';
    CONST FILE_SHORT_KEY = 'f';

    /**
     * Initializes class config with params from command line
     * @return void
     */
    protected function _initConfigFromCli() {
        $parameters = array(self::USER_SHORT_KEY . ':' => self::USER_KEY . ':',
            self::PLAYS_LIMIT_SHORT_KEY . '::'         => self::PLAYS_LIMIT_KEY . '::',
            self::ARTISTS_LIMIT_SHORT_KEY . '::'       => self::ARTISTS_LIMIT_KEY . '::',
            self::FILE_SHORT_KEY . '::'                => self::FILE_KEY . '::'
        );
        $options = getopt(implode('', array_keys($parameters)), $parameters);
        foreach($parameters as $shortKey => $key) {
            $shortKey = rtrim($shortKey, ':');
            $key = rtrim($key, ':');
            isset($options[$shortKey]) && $options[$key] = $options[$shortKey];
        }

        if(isset($options[self::USER_KEY])) {
            $this->_username = (string) $options[self::USER_KEY];
        } else {
            throw new Exception('Please specify user name');
        }
        isset($options[self::FILE_KEY]) && $this->_outputFile = (string) $options[self::FILE_KEY];
        isset($options[self::PLAYS_LIMIT_KEY]) && $this->_playsLimit = (int) $options[self::PLAYS_LIMIT_KEY];
        isset($options[self::ARTISTS_LIMIT_KEY]) && $this->_artistsLimit = (int) $options[self::ARTISTS_LIMIT_KEY];
    }

    /**
     * Fetches artist list from lastFm
     * @param string $username
     * @param integer $limit
     * @return array
     */
    protected function _getArtists() {
        $result = array();

        $params = array('method' => 'library.getartists',
            'api_key' => self::API_KEY,
            'user' => $this->_username,
            'limit' => $this->_artistsLimit);
        $query = http_build_query($params);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'http://ws.audioscrobbler.com/2.0/?' . $query);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $xmlString = curl_exec($curl);
        curl_close($curl);

        $xml = simplexml_load_string($xmlString);
        if($xml) {
            foreach($xml->xpath('/lfm[@status="ok"]/artists/artist') as $artist) {
                if($artist->playcount < $this->_playsLimit) {
                    break;
                }
                $result[] = $artist->name;
            }
        }

        return $result;
    }

    /**
     * Puts artists list separated by comma to specific output
     * @return void
     */
    public static function start() {
        try {
            $lastFmArtists = new self;
            $lastFmArtists->_initConfigFromCli();
            $artistsList = $lastFmArtists->_getArtists();
            if(empty($artistsList)) {
                return;
            }
            $artistsList = implode(', ', $artistsList);
            if($lastFmArtists->_outputFile) {
                file_put_contents($lastFmArtists->_outputFile, $artistsList);
            } else {
                echo $artistsList . PHP_EOL;
            }
        } catch(Exception $ex) {
            echo $ex->getMessage() . PHP_EOL;
        }
    }
}

lastFmArtists::start();