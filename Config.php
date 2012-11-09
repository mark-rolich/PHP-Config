<?php
/**
* Configuration file reader
*
*/
class Config
{
    /**
    * @var mixed - configuration settings array
    */
    private $data;

	/**
	* Constructor
	*
    * Determines environment, mixes global configuration settings
    * and some server variables
    * with determined environment settings
    *
	* @param $config mixed - array of predefined options
	*/
    public function __construct($config) {
        extract($_SERVER);

        $env = (isset($config[$HTTP_HOST]))
                ? $config[$HTTP_HOST]
                : 'DEV';

        $data = array_merge($config['GLOBAL'], $config[$env]);

        $data['env']                = $env;
        $data['request.host']       = $HTTP_HOST;
        $data['request.ip']         = $REMOTE_ADDR;
        $data['request.uri']        = $REQUEST_URI;
        $data['request.ua']         = isset($HTTP_USER_AGENT)
                                    ? $HTTP_USER_AGENT
                                    : 'Unknown';
        $data['request.method']     = $REQUEST_METHOD;
        $data['request.query']      = $QUERY_STRING;
        $data['request.referer']    = isset($HTTP_REFERER)
                                    ? $HTTP_REFERER
                                    : '/';

        ksort($data);
        $this->data = $data;
    }

	/**
	* Getter for properties from global settings array
	* Supports getting single option or group of options
    * Dots contained in keys of groupped options
    * are replaced with underscores.
    *
    * Example:
    * $config = array(
    *    'db.host'       => 'localhost',
    *    'db.mysql.user' => 'root',
    *    'db.mysql.pwd'  => ''
    * );
    *
    * $conf = new Config($config);
    * $conf->db will return array with following keys:
    * host, mysql_user and mysql_pwd
    *
	* @param $name string - name of property (key of global settings array)
	* @return mixed - value of property / array of properties
	*/
    public function __get($name) {
        $value = null;

        if (isset($this->data[$name])) {
            $value = $this->data[$name];
        } else {
            $keys = array_keys($this->data);

            foreach ($keys as $key) {
                $pos = strpos($key, $name . '.');

                if ($pos === 0) {
                    $key1 = substr($key, strlen($name . '.'));
                    $key1 = str_replace('.', '_', $key1);
                    $value[$key1] = $this->data[$key];
                }
            }
        }

        if ($value === null) {
            throw new Exception('Unknown option [' . $name . ']');
        }

        return $value;
    }
}
?>