<?php

namespace SirMendax\SmsApiClient;

/**
 * Class SmsSmsc
 * @package SirMendax\SmppApiClient
 */
class SmsSmsc
{
    private ?array $config = null;
    private ?string $login = null;
    private ?string $password = null;
    private ?int $post = null;
    private ?int $https = null;
    private ?int $debug = null;
    private ?string $charset = null;
    private ?string $from = null;

    /**
     * SmsSmsc constructor.
     * @param array|null $config
     */
    public function __construct(?array $config)
    {
        $this->config = $config;
        $this->login = $config['SMSC_LOGIN'];
        $this->password = $config['SMSC_PASSWORD'];
        $this->post = $config['SMSC_POST'];
        $this->https = $config['SMSC_HTTPS'];
        $this->debug = $config['SMSC_DEBUG'];
        $this->charset = $config['SMSC_CHARSET'];
        $this->from = $config['SMTP_FROM'];
    }

    /**
     * @param string $phones
     * @param string $message
     * @param int $translit
     * @param int $time
     * @param int $id
     * @param int $format
     * @param bool $sender
     * @param string $query
     * @param array $files
     * @return array
     */
    public function sendSms(string $phones, string $message, int $translit = 0, int $time = 0, int $id = 0, int $format = 0, bool $sender = false, string $query = "", array $files = array())
    {
        static $formats = array(1 => "flash=1", "push=1", "hlr=1", "bin=1", "bin=2", "ping=1", "mms=1", "mail=1", "call=1", "viber=1", "soc=1");

        $m = $this->smscSendCmd("send", "cost=3&phones=" . urlencode($phones) . "&mes=" . urlencode($message) .
            "&translit=$translit&id=$id" . ($format > 0 ? "&" . $formats[$format] : "") .
            ($sender === false ? "" : "&sender=" . urlencode($sender)) .
            ($time ? "&time=" . urlencode($time) : "") . ($query ? "&$query" : ""), $files);

        if ($this->debug) {
            if ($m[1] > 0)
                echo "Сообщение отправлено успешно. ID: $m[0], всего SMS: $m[1], стоимость: $m[2], баланс: $m[3].\n";
            else
                echo "Ошибка №", -$m[1], $m[0] ? ", ID: " . $m[0] : "", "\n";
        }

        return $m;
    }

    /**
     * @param $phones
     * @param $message
     * @param int $translit
     * @param int $time
     * @param int $id
     * @param int $format
     * @param string $sender
     * @return bool
     */
    public function sendSmsMail($phones, $message, $translit = 0, $time = 0, $id = 0, $format = 0, $sender = "")
    {
        return mail("send@send.smsc.ru", "", $this->login.":".$this->password.":$id:$time:$translit,$format,$sender:$phones:$message", "From: ".$this->from."\nContent-Type: text/plain; charset=".$this->charset."\n");
    }

    /**
     * @param $phones
     * @param $message
     * @param int $translit
     * @param int $format
     * @param bool $sender
     * @param string $query
     * @return array
     */
    public function getSmsCost($phones, $message, $translit = 0, $format = 0, $sender = false, $query = "")
    {
        static $formats = array(1 => "flash=1", "push=1", "hlr=1", "bin=1", "bin=2", "ping=1", "mms=1", "mail=1", "call=1", "viber=1", "soc=1");

        $m = $this->smscSendCmd("send", "cost=1&phones=".urlencode($phones)."&mes=".urlencode($message).
            ($sender === false ? "" : "&sender=".urlencode($sender)).
            "&translit=$translit".($format > 0 ? "&".$formats[$format] : "").($query ? "&$query" : ""));

        if ($this->debug) {
            if ($m[1] > 0)
                echo "Стоимость рассылки: $m[0]. Всего SMS: $m[1]\n";
            else
                echo "Ошибка №", -$m[1], "\n";
        }

        return $m;
    }

    /**
     * @param $cmd
     * @param string $arg
     * @param array $files
     * @return array
     */
    private function smscSendCmd($cmd, $arg = "", $files = array())
    {
        $url = $_url = ($this->https ? "https" : "http") . "://smsc.ru/sys/$cmd.php?login=" . urlencode($this->login) . "&psw=" . urlencode($this->password) . "&fmt=1&charset=" . $this->charset . "&" . $arg;

        $i = 0;
        do {
            if ($i++)
                $url = str_replace('://smsc.ru/', '://www' . $i . '.smsc.ru/', $_url);

            $ret = $this->smscReadUrl($url, $files, 3 + $i);
        } while ($ret == "" && $i < 5);

        if ($ret == "") {
            if ($this->debug)
                echo "Ошибка чтения адреса: $url\n";

            $ret = ","; // фиктивный ответ
        }

        $delim = ",";

        if ($cmd == "status") {
            parse_str($arg, $m);

            if (strpos($m["id"], ","))
                $delim = "\n";
        }

        return explode($delim, $ret);
    }

    /**
     * @param $url
     * @param $files
     * @param int $tm
     * @return bool|false|mixed|string
     */
    private function smscReadUrl($url, $files, $tm = 5)
    {
        $ret = "";
        $post = $this->post || strlen($url) > 2000 || $files;

        if (function_exists("curl_init"))
        {
            static $c = 0;
            if (!$c) {
                $c = curl_init();
                curl_setopt_array($c, array(
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_CONNECTTIMEOUT => $tm,
                    CURLOPT_TIMEOUT => 60,
                    CURLOPT_SSL_VERIFYPEER => 0,
                    CURLOPT_HTTPHEADER => array("Expect:")
                ));
            }
            curl_setopt($c, CURLOPT_POST, $post);
            if ($post)
            {
                list($url, $post) = explode("?", $url, 2);

                if ($files) {
                    parse_str($post, $m);

                    foreach ($m as $k => $v)
                        $m[$k] = isset($v[0]) && $v[0] == "@" ? sprintf("\0%s", $v) : $v;

                    $post = $m;
                    foreach ($files as $i => $path)
                        if (file_exists($path))
                            $post["file".$i] = function_exists("curl_file_create") ? curl_file_create($path) : "@".$path;
                }

                curl_setopt($c, CURLOPT_POSTFIELDS, $post);
            }

            curl_setopt($c, CURLOPT_URL, $url);

            $ret = curl_exec($c);
        }
        elseif ($files) {
            if ($this->debug)
                echo "Не установлен модуль curl для передачи файлов\n";
        }
        else {
            if (!$this->https && function_exists("fsockopen"))
            {
                $m = parse_url($url);

                if (!$fp = fsockopen($m["host"], 80, $errno, $errstr, $tm))
                    $fp = fsockopen("212.24.33.196", 80, $errno, $errstr, $tm);

                if ($fp) {
                    stream_set_timeout($fp, 60);

                    fwrite($fp, ($post ? "POST $m[path]" : "GET $m[path]?$m[query]")." HTTP/1.1\r\nHost: smsc.ru\r\nUser-Agent: PHP".($post ? "\r\nContent-Type: application/x-www-form-urlencoded\r\nContent-Length: ".strlen($m['query']) : "")."\r\nConnection: Close\r\n\r\n".($post ? $m['query'] : ""));

                    while (!feof($fp))
                        $ret .= fgets($fp, 1024);
                    list(, $ret) = explode("\r\n\r\n", $ret, 2);

                    fclose($fp);
                }
            }
            else
                $ret = file_get_contents($url);
        }

        return $ret;
    }

    /**
     * @return bool|mixed
     */
    private function getBalance()
    {
        $m = $this->smscSendCmd("balance"); // (balance) или (0, -error)

        if ($this->debug) {
            if (!isset($m[1]))
                echo "Сумма на счете: ", $m[0], "\n";
            else
                echo "Ошибка №", -$m[1], "\n";
        }

        return isset($m[1]) ? false : $m[0];
    }
}
