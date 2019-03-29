<?php
namespace tt\linebot;

class Bot{
	private $bot;
	private $access_token;
	private $vars = [];
	
	const EVENT_TYPE_TEXT = 1;
	const EVENT_TYPE_STICKER = 2;
	const EVENT_TYPE_IMAGE = 3;
	const EVENT_TYPE_POSTBACK = 4;
	
	const EVENT_TYPE_FOLLOW = 11;
	const EVENT_TYPE_UNFOLLOW = 11;
	
	
	public static function client(){
		$access_token = \ebi\Conf::get('access_token');
		$secret = \ebi\Conf::get('secret');
		
		return new self($access_token,$secret);
	}
	
	public function __construct($access_token,$secret){
		$this->access_token = $access_token;
		$this->bot = new \LINE\LINEBot(
				new \LINE\LINEBot\HTTPClient\CurlHTTPClient($access_token),
				['channelSecret'=>$secret]
				);
	}
	
	/**
	 *
	 * @return \LINE\LINEBot\Event\BaseEvent
	 */
	public function get_events(){
		$post_input = file_get_contents('php://input');
		
		return $this->bot->parseEventRequest(
				$post_input,
				(new \ebi\Env())->get('HTTP_'.\LINE\LINEBot\Constant\HTTPHeader::LINE_SIGNATURE)
				);
	}
	
	
	public function get_type(\LINE\LINEBot\Event\BaseEvent $event){
		if($event instanceof \LINE\LINEBot\Event\MessageEvent\TextMessage){
			return self::EVENT_TYPE_TEXT;
		}
		if($event instanceof \LINE\LINEBot\Event\MessageEvent\StickerMessage){
			return self::EVENT_TYPE_STICKER;
		}
		if($event instanceof \LINE\LINEBot\Event\MessageEvent\ImageMessage){
			return self::EVENT_TYPE_IMAGE;
		}
		if($event instanceof \LINE\LINEBot\Event\PostbackEvent){
			return self::EVENT_TYPE_POSTBACK;
		}
		if($event instanceof \LINE\LINEBot\Event\FollowEvent){
			return self::EVENT_TYPE_FOLLOW;
		}
		if($event instanceof \LINE\LINEBot\Event\UnfollowEvent){
			return self::EVENT_TYPE_UNFOLLOW;
		}
		return null;
	}
	
	public function get_content($message_id){
		return $this->bot->getMessageContent($message_id)->getRawBody();
	}
	
	public function get_text(\LINE\LINEBot\Event\BaseEvent $event){
		return trim($event->getText());
	}
	public function in_vars(\LINE\LINEBot\Event\BaseEvent $event,$key,$default=null){
		if(!isset($this->vars[$event->getReplyToken()])){
			parse_str($event->getPostbackData(),$this->vars[$event->getReplyToken()]);
		}
		return $this->vars[$key] ?? $default;
	}
	public function get_sticker_id(\LINE\LINEBot\Event\BaseEvent $event){
		return [$event->getPackageId(),$event->getStickerId()];
	}
	public function get_message_id(\LINE\LINEBot\Event\BaseEvent $event){
		return $event->getMessageId();
	}
	
	public function reply(\tt\linebot\Event $event,$messages){
		$this->send('https://api.line.me/v2/bot/message/reply',[
			'replyToken'=>$event->reply_token(),
			'messages'=>$this->get_message_vars($messages),
		]);
	}
	
	public function multicast($tos,$messages){
		if(!is_array($tos)){
			$tos = [$tos];
		}
		if(sizeof($tos) > 150){
			throw new \ebi\exception\MaxSizeExceededException('max 150, input '.sizeof($tos));
		}
		
		$this->send('https://api.line.me/v2/bot/message/multicast',[
			'to'=>$tos,
			'messages'=>$this->get_message_vars($messages),
		]);
	}
	
	public function push($to,$messages){
		$this->send('https://api.line.me/v2/bot/message/push',[
			'to'=>$to,
			'messages'=>$this->get_message_vars($messages),
		]);
	}
	
	private function get_message_vars($messages){
		if(is_string($messages)){
			$messages = \ebi\Json::decode(\ebi\Util::plain_text($messages));
		}
		return $messages;
	}
	private function send($url,array $request){
		$b = new \ebi\Browser();
		$b->bearer_token($this->access_token);
		$b->header('Content-Type','application/json');
		$b->do_raw($url,json_encode($request));
		
		if($b->status() !== 200){
			throw new \ebi\exception\InvalidArgumentException($b->status().': '.$b->body());
		}
	}
}