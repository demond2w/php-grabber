<?php

/**
 * Класс граббера.
 *
 * @author    Dmitriy V. Ibragimov
 * @version   version.txt
 */
class classes_Loader
{
    private $_mhandle = null;
    private $_stop = false;
    private $_taskQueue = array();
    private $_proxyList = array();

    public function __construct()
    {
        $this->_initTaskQueue();
        $this->_initProxyList();

        $this->_mhandle = curl_multi_init();
    }

    public function __destruct()
    {
        curl_multi_close($this->_mhandle);
    }

    public function setTaskQueue($links)
    {
        $this->_taskQueue = $links;
    }

    public function add2log($type, $message)
    {
        echo $type . ": " . $message . PHP_EOL;
        flush();
    }

    public function run($parallel = 10)
    {
        $this->add2log('info', 'Start...');

        while (!$this->_stop)
        {
            $countActive = 0;

            while ($countActive < $parallel)
            {
                if ($task = $this->_getTask())
                {
                    $thread = &$this->_initThread( $task );
                    $countActive++;
                    $this->add2log('info', 'Thread #: ' . $thread);
                }
                else
                {
                    break;
                }
            }

            if (0 == $countActive)
            {
                break;
            }

            $running = null;

            while (($mrc = curl_multi_exec($this->_mhandle, $running)) == CURLM_CALL_MULTI_PERFORM);

            while($running && $mrc == CURLM_OK)
            {
               if ($running && curl_multi_select($this->_mhandle) != -1)
               {
                    do
                    {
                        $mrc = curl_multi_exec($this->_mhandle, $running);

                        if (($info = curl_multi_info_read($this->_mhandle)) && $info['msg'] == CURLMSG_DONE)
                        {
                            $handle = $info['handle'];
                            $code = curl_getinfo($handle, CURLINFO_HTTP_CODE);

                            if (0 == $code)
                            {
                                $this->add2log('error', 'Thread #: ' . $handle . ', error: ' . curl_error($handle));
                            }
                            else
                            {
                                $this->_perform($code, curl_multi_getcontent($handle));
                                $this->add2log('success', 'Thread #: ' . $handle . ', code: ' . $code);
                            }

                            $this->_terminateThread($handle);
                        }
                    }
                    while ($mrc == CURLM_CALL_MULTI_PERFORM);
                }

                usleep(100);
            }
        }

        $this->add2log('success', 'Termanated');
    }

    protected function &_initThread($url)
    {
        $handle = curl_init();
        $proxy = $this->_getProxy();

        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($handle, CURLINFO_HEADER_OUT, true);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($handle, CURLOPT_HTTPPROXYTUNNEL, true);
        curl_setopt($handle, CURLOPT_PROXY, $proxy['ip']);
        curl_setopt($handle, CURLOPT_PROXYPORT, $proxy['port']);

        curl_multi_add_handle($this->_mhandle, $handle);

        return $handle;
    }

    protected function _terminateThread($handle)
    {
        curl_multi_remove_handle($this->_mhandle, $handle);
        curl_close($handle);
    }

    protected function _initTaskQueue()
    {
        $this->_taskQueue = array
        (
            // insert urls here...
        );
    }

    protected function _initProxyList()
    {
        $this->_proxyList = array
        (
            // insert proxy servers list here...
            // array('ip' => '{ip}', 'port' => '{port}'),

        );
    }

    protected function _getTask()
    {
        $task = array_shift($this->_taskQueue);

        return $task;
    }

    protected function _getProxy()
    {
        return $this->_proxyList[rand(0, count($this->_proxyList)-1)];
    }

    protected function _perform($codeAnswer, $response)
    {
        file_put_contents(DIR_DATA . $this->_getFileName($response), $response);
    }

    protected function _getFileName($response)
    {
        return md5(time() . uniqid(mt_rand(), true));
    }
}