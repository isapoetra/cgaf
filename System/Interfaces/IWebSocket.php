<?php
/**
 * Still prototype... chat controller will updated ASAP
 */
interface IWebSocketServer {

}
interface IWebSocketConnection {
	function log($message, $type = 'info');
	function send($payload, $type = 'text', $masked = true);
}
interface IWebSocketService {
	function handleService(IWebSocketConnection $client,$data);
}
interface IWebSocketApplicationHandler {
	function initWebSocket(IWebSocketServer $server);
	function onWebSocket(IWebSocketConnection $client,$type,$args=null);
}