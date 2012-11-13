<?php
/**
* Configuration file reader
* Supports multidimensional arrays
*
* @author Mark Rolich <mark.rolich@gmail.com>
*/
class Config
{
    /**
    * @var mixed - configuration settings array
    */
    private $data;

    /**
    * @var int - parse mode (0|1|2)
    */
    private $mode;

    /**
    * @var int - default parsing mode
    */
    const PARSE_DOTS    = 0;

    /**
    * @var int - parse as array
    */
    const PARSE_ARRAY   = 1;

    /**
    * @var int - parse as object
    */
    const PARSE_OBJECT  = 2;

    /**
    * Constructor
    *
    * Determines environment, mixes global configuration settings
    * and some server variables
    * with determined environment settings
    *
    * @param $config mixed - array of predefined options
    */
    public function __construct($config, $mode = self::PARSE_DOTS)
    {
        extract($_SERVER);
        $this->mode = $mode;

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

        switch ($mode) {
            case self::PARSE_ARRAY:
                $this->data = $this->parseArray($data);
                break;
            case self::PARSE_OBJECT:
                $this->data = $this->parseObject($data);
                break;
            default:
                $this->data = $data;
        }
    }

    /**
    * Converts array with dotted keys to multidimensional array
    *
    * @param $data mixed - array with dotted keys
    */
    private function parseArray($data)
    {
        $result = array();

        foreach ($data as $k => $v) {
            $keys = explode('.', $k);

            $tmp = &$result;

            foreach ($keys as $key) {
                if (!array_key_exists($key, $tmp)) {
                    $tmp[$key] = array();
                }

                $tmp = &$tmp[$key];
            }

            $tmp = $v;
        }

        return $result;
    }

    /**
    * Converts array with dotted keys to "multidimensional" chained object
    *
    * @param $data mixed - array with dotted keys
    */
    private function parseObject($data)
    {
        $result = new StdClass();

        foreach ($data as $k => $v) {
            $properties = explode('.', $k);

            $tmp = &$result;

            foreach ($properties as $property) {
                if (!property_exists($tmp, $property)) {
                    $tmp->$property = new StdClass();
                }

                $tmp = &$tmp->$property;
            }

            $tmp = $v;
        }

        return $result;
    }

    /**
    * Getter for properties from global settings array
    * Supports getting single option or group of options
    *
    * In default mode dots contained in keys of groupped options
    * are replaced with underscores.
    *
    * Example:
    * $config = array(
    * 'db.host' => 'localhost',
    * 'db.mysql.user' => 'root',
    * 'db.mysql.pwd' => ''
    * );
    *
    * $conf = new Config($config);
    * $conf->db will return array with following keys:
    * host, mysql_user and mysql_pwd
    *
    * In modes 1 and 2 returns corresponding nested array
    * or nested object respectively if exists, else value of key/property
    *
    * @param $name string - name of property (key of global settings array)
    * @return mixed - value of property / array of properties / chained objects
    */
    public function __get($name)
    {
        $value = null;

        if ($this->mode === self::PARSE_DOTS
            || $this->mode === self::PARSE_ARRAY) {

            if (isset($this->data[$name])) {
                $value = $this->data[$name];
            } else {
                if ($this->mode === self::PARSE_DOTS) {
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
            }
        } elseif ($this->mode === self::PARSE_OBJECT) {
            if (isset($this->data->$name)) {
                $value = $this->data->$name;
            }
        }

        if ($value === null) {
            throw new Exception('Unknown option [' . $name . ']');
        }

        return $value;
    }
}
?>