<?php
/**
 * Wsdebug for Hyperf
 *
 * Author: 狂奔的螞蟻 <www.firstphp.com>
 * Date: 2019/11/6
 * Time: 11:11 AM
 */

namespace Firstphp\Wsdebug;

use Hyperf\Contract\OnCloseInterface;
use Hyperf\Contract\OnMessageInterface;
use Hyperf\Contract\OnOpenInterface;
use Swoole\Http\Request;
use Swoole\Server;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server as WebSocketServer;



class Wsdebug implements OnMessageInterface, OnOpenInterface, OnCloseInterface
{

    /**
     * @var \swoole_websocket_server
     */
    private $server;


    /**
     * @var string
     */
    private $host;


    /**
     * Wsdebug constructor.
     */
    public function __construct()
    {
        $config = config('wsdebug') ?? '';
        $this->host = isset($config['wshost']) && $config['wshost'] ? $config['wshost'] : 'ws://127.0.0.1:9505';
        $container = \Hyperf\Utils\ApplicationContext::getContainer();
        $wsFactory = $container->get(\Hyperf\WebSocketServer\Server::class);
        $this->server = $wsFactory->getServer();
    }


    /**
     * @param WebSocketServer $server
     * @param Frame $frame
     */
    public function onMessage(WebSocketServer $server, Frame $frame): void
    {
        $type = 'info';
        if ($frame->data == 'pong') {
            $type = 'pong';
        }
        $server->push($frame->fd, json_encode(['type' => $type, 'content' => $frame->data]));
    }


    /**
     * @param Server $server
     * @param int $fd
     * @param int $reactorId
     */
    public function onClose(Server $server, int $fd, int $reactorId): void
    {
        echo "ws server $fd closed\n";
    }


    /**
     * @param WebSocketServer $server
     * @param Request $request
     */
    public function onOpen(WebSocketServer $server, Request $request): void
    {
        $server->push($request->fd, json_encode(['type' => 'open']));
    }


    /**
     * @param mixed $message
     * @param string $type 用于前端标记
     * @return bool
     */
    public function send($message, string $type = 'info')
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 4);
        array_shift($trace);
        $server = $this->server;
        if( !empty( $server->connections ) ){
            if(is_array($message)){
                $content_type = 'Array';
            }elseif(is_string($message) && Str::endsWith($message,'}',false)){
                $content_type = 'Json';
                $message = json_decode($message,true);
            }elseif(is_string($message)){
                $content_type = 'String';
            }elseif(is_int($message)) {
                $content_type = 'Int';
            }elseif(is_float($message)){
                $content_type = 'Float';
            }elseif(is_object($message)){
                $content_type = 'Object';
                $message = $this->object2array( $message ); //兼容打印对象
            }else{
                $content_type = '未捕捉到的数据类型';
            }
            $jsonMessage = json_encode( [
                'time'    => date( "Y-m-d H:i:s" ),
                'type'    => $type,
                'content_type'=>$content_type,
                'content' => $message,
                'debug_backtrace' => $trace,
            ]);
            foreach( $server->connections as $fd ){
                $info = $server->connection_info( $fd );
                if( isset( $info['websocket_status'] ) && $info['websocket_status'] === 3 ){

                    $server->push( $fd, $jsonMessage );
                }
            }
            return true;
        } else{
            return false;
        }
    }


    /**
     * @param $object
     * @return array
     */
    private function object2array($object)
    {
        if (is_object($object)) {
            $object = (array)$object;
        }
        if (is_array($object)) {
            foreach ($object as $key => $value) {
                $object[$key] = $this->object2array($value);
            }
        }
        return $object;
    }


    /**
     * Convert encoding.
     *
     * @param array $array
     * @param string $to_encoding
     * @param string $from_encoding
     *
     * @return array
     */
    private function encoding($array, $to_encoding = 'UTF-8', $from_encoding = 'GBK'): array
    {
        $encoded = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $encoded[$key] = $this->encoding($value, $to_encoding, $from_encoding);
            } elseif (is_bool($value)) {
                $encoded[$key] = $value;
            } elseif (is_string($value) && mb_detect_encoding($value, 'UTF-8', true)) {
                $encoded[$key] = $value;
            } else {
                $encoded[$key] = mb_convert_encoding($value, $to_encoding, $from_encoding);
            }
        }
        return $encoded;
    }


    /**
     * @return string
     */
    public function getHtml() : string
    {
        $file =  @file_get_contents(__DIR__ . '/../view/Wsdebug.html');
        $file = str_replace('{{wshost}}',$this->host, $file);
        return $file;
    }


}
