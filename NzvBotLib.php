<?php


/**
 * Содержит классы для обработки команд
 *
 * @author artem
 */

namespace Nazarov {

class MessageAction {
    private $telegram;
    private $command;
    private $chat_id;
    private $content;
    private $response_text_f;
            
    function __construct($telegram, $chat_id, $command, $response_text_f) {
        $this->setTelegram($telegram);
        $this->setCommand($command);
        $this->setChatId($chat_id);
        $this->setResponseTextF($response_text_f);
    }
    
    function getCommand(){
        return $this->command;
    }
    
    function setResponseTextF($response_text_f){
        $this->response_text_f = $response_text_f;
    }
    
       
    function getResponseTextF(){
        return $this->response_text_f;
    }
    
    function getChatId(){
        return $this->chat_id;
    }


    function setContent($content){
        $this->content = $content;
    }
    
    function getContent(){
        return $this->content;
    }
    
    function setChatId($chat_id){
        $this->chat_id = $chat_id;
    }

    function setTelegram($telegram){
        $this->telegram = $telegram;
    }
    
    function setCommand($command){
        $this->command = $command;
    }
     
    
    function executeAction(){
        $response = $this->getResponseTextF(); 
        $computed = $response();
        $default_map = ['text'=>$computed,
        'chat_id'=>$this->getChatId(), 'parse_mode'=>'HTML'];
        if (is_array($computed)) {

            $result_map = $default_map;
            foreach ($computed as $key => $value){
                $result_map[$key] = $value;
            }

        } else {
            $result_map = $default_map;
        }
        $content = $result_map;
        $this->setContent($content);
        $this->telegram->sendMessage($content);
        return $computed;
    }
    
    function process($text){
        if ($text == $this->getCommand()){
            return $this->executeAction();
        };
        return "";
    }
}

 
class MessagesRouting {
    private $actions;
    private $telegram;
    private $chat_id;
    private $result;
    function __construct($telegram) {
        $this->actions = [];
        $this->telegram = $telegram;

        $this->result = $this->telegram->getData();
        @$this->text = $this->result['message']['text'];
        @$this->chat_id = $this->result['message']['chat']['id'];
    }
    function getMessageText(){
        return $this->text;
    }
    function addAction($text, $responce_text_f){
        $action = new MessageAction($this->telegram, $this->chat_id, $text, $responce_text_f );
        array_push($this->actions, $action);        
    }
    function preprocess_callbacks(){

        if (isset($this->result['callback_query'])) {
            $callback = $this->result['callback_query'];
            $this->chat_id = $callback['message']['chat']['id'];
            $text = $callback['data'];
            $this->process($text);
            // Ответ на callback
            $this->telegram->answerCallbackQuery([
                'callback_query_id' => $callback['id'],
                'text' => 'Запрос обработан'
            ]);
        }
    }
    function process($text){
        foreach  ($this->actions as $action ) {
            $res = $action->process($text);
            if (  $res !== "" ) { return $res; };
        }
        return "Неизвестная команда, смотри помощь /pom";
    }
    
} 


}
