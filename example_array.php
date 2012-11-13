<?php
include 'Config.php';
$config = parse_ini_file('config.ini', true);
$config = new Config($config, Config::PARSE_ARRAY);

try {
    var_dump($config->options) . '<br>';

    echo $config->env . '<br>';

    var_dump($config->db);

    echo $config->db['host'] . '<br>';

    var_dump($config->db['mysql']);

    var_dump($config->log['path']);

    echo $config->db['mysql']['host'] . '<br>';

    echo $config->some['not']['existing']['option'];
} catch (Exception $e) {
    echo $e->getMessage();
}
?>