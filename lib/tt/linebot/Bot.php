<?php
namespace tt\linebot;

class Bot{
	private $bot;
	private $message;
	
	public static function client(){
		$access_token = \ebi\Conf::get('access_token');
		$secret = \ebi\Conf::get('secret');
		
		return new self($access_token,$secret);		
	}
	
	public function __construct($access_token,$secret){
		$this->bot = new \LINE\LINEBot(
			new \LINE\LINEBot\HTTPClient\CurlHTTPClient($access_token),
			['channelSecret'=>$secret]
		);
		
		$this->message = new \LINE\LINEBot\MessageBuilder\MultiMessageBuilder();
	}
	
	/**
	 * 
	 * @return \tt\linebot\Event
	 */
	public function get_events(){
		$post_input = file_get_contents('php://input');
		
		$events = $this->bot->parseEventRequest(
			$post_input,
			(new \ebi\Env())->get('HTTP_'.\LINE\LINEBot\Constant\HTTPHeader::LINE_SIGNATURE)
		);
		
		foreach($events as $event){
			yield new \tt\linebot\Event($event);
		}
	}
	
	
	public function get_content($message_id){
		return $this->bot->getMessageContent($message_id)->getRawBody();
	}
	
	
	public function add_text($text){
		new \LINE\LINEBot\QuickReplyBuilder\QuickReplyMessageBuilder();
		$this->message->add(new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($text));
	}
	
	/**
	 * 
	 * @param integer $package_id
	 * @param integer $sticker_id
	 * @see https://developers.line.me/media/messaging-api/messages/sticker_list.pdf
	 */
	public function add_sticker($package_id,$sticker_id){
		$this->message->add(new \LINE\LINEBot\MessageBuilder\StickerMessageBuilder($package_id,$sticker_id));
	}
	
	public function add_image($original_url,$preview_url=null){
		if(empty($preview_url)){
			$preview_url = $original_url;
		}
		$this->message->add(new \LINE\LINEBot\MessageBuilder\ImageMessageBuilder($original_url,$preview_url));
	}
	
	public function add_confirm($alt,$text,array $vars){
		$buttons = [];
		foreach($vars as $k => $v){
			$buttons[] = $this->parse_action($k,$v);
		}
		
		$this->message->add(new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder(
			($alt ?? $text),
			new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ConfirmTemplateBuilder(
				$text,
				$buttons
			)
		));
	}
	
	public function add_button($alt,$column_var){
		$buttons = [];
		
		foreach(($column_var['buttons'] ?? []) as $bk => $bv){
			$buttons[] = $this->parse_action($bk,$bv);
		}
		$this->message->add(new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder(
			$alt,
			new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder(
				$column_var['title'] ?? null,
				$column_var['text'] ?? null,
				$column_var['image'] ?? null,
				$buttons
			)
		));
	}
	
	public function add_carousel($alt,$column_vars){
		$columns = [];
		foreach($column_vars as $column_var){
			$buttons = [];
			
			foreach(($column_var['buttons'] ?? []) as $bk => $bv){
				$buttons[] = $this->parse_action($bk,$bv);
			}
			$columns[] = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselColumnTemplateBuilder(
				$column_var['title'] ?? null,
				$column_var['text'] ?? null,
				$column_var['image'] ?? null,
				$buttons
			);
		}
		$this->message->add(new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder(
			$alt,
			new \LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselTemplateBuilder($columns)
		));
	}
		
	private function parse_action($title,$vars_or_url){
		if(is_array($vars_or_url)){
			return new \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder(
				$title,
				http_build_query($vars_or_url)
			);
		}
		return new \LINE\LINEBot\TemplateActionBuilder\UriTemplateActionBuilder($title,$vars_or_url);
	}
	
	public function reply(\tt\linebot\Event $event){
		$this->bot->replyMessage($event->reply_token(),$this->message);
	}
}
