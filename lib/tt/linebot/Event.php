<?php
namespace tt\linebot;

class Event{
	private $plain;
	private $vars;
	
	public function __construct(\LINE\LINEBot\Event\BaseEvent $event){
		$this->plain = $event;
	}
	
	public function is_text(){
		return ($this->plain instanceof \LINE\LINEBot\Event\MessageEvent\TextMessage);
	}
	public function is_sticker(){
		return ($this->plain instanceof \LINE\LINEBot\Event\MessageEvent\StickerMessage);
	}
	public function is_image(){
		return ($this->plain instanceof \LINE\LINEBot\Event\MessageEvent\ImageMessage);
	}
	public function is_postback(){
		return ($this->plain instanceof \LINE\LINEBot\Event\PostbackEvent);
	}
	
	public function text(){
		if($this->is_text()){
			return trim($this->plain->getText());
		}
		return null;
	}
	
	public function sticker_id(){
		if($this->is_sticker()){
			return [$this->plain->getPackageId(),$this->plain->getStickerId()];
		}
		return [1,100];
	}
		
	public function message_id(){
		return $this->plain->getMessageId();
	}
	
	public function in_vars($key,$default=null){
		if($this->vars === null){
			parse_str($this->plain->getPostbackData(),$this->vars);			
		}
		return $this->vars[$key] ?? $default;
	}
	
	
	
	public function reply_token(){
		return $this->plain->getReplyToken();
	}
}
