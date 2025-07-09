<?php


/**
 * Содержит классы для обработки команд
 *
 * @author artem
 */

namespace Nazarov {

class ChatMessageWriter {
    private $dbHost;
    private $dbPort;
    private $dbUser;
    private $dbPass;
    private $dbType;
    private $dbName;
    private $connection;

    public function __construct(
        string $dbHost,
        int $dbPort,
        string $dbUser,
        string $dbPass,
        string $dbType,
        string $dbName
    ) {
        $this->dbHost = $dbHost;
        $this->dbPort = $dbPort;
        $this->dbUser = $dbUser;
        $this->dbPass = $dbPass;
        $this->dbType = strtolower($dbType);
        $this->dbName = $dbName;
    }

    private function connect() {
        try {
            if ($this->dbType === 'mysql') {
                $dsn = "mysql:host={$this->dbHost};port={$this->dbPort};dbname={$this->dbName};charset=utf8mb4";
                $this->connection = new \PDO($dsn, $this->dbUser, $this->dbPass);
            } elseif ($this->dbType === 'postgres') {
                $dsn = "pgsql:host={$this->dbHost};port={$this->dbPort};dbname={$this->dbName}";
                $this->connection = new \PDO($dsn, $this->dbUser, $this->dbPass);
            } else {
                throw new \InvalidArgumentException("Unsupported database type: {$this->dbType}");
            }
            
            $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            return true;
        } catch (\PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            return false;
        }
    }

    private function disconnect() {
        $this->connection = null;
    }

    public function saveMessage(int $chatId, string $messageText, string $command = null): bool {
        try {
            // Establish new connection
            if (!$this->connect()) {
                return false;
            }

            $tableName = 'chat_messages'; // Consistent table name across DB types
            
            // Create table if not exists
            $createTableQuery = "
                CREATE TABLE IF NOT EXISTS {$tableName} (
                    id SERIAL PRIMARY KEY,
                    chat_id BIGINT NOT NULL,
                    message_text TEXT NOT NULL,
                    command VARCHAR(255),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ";
            
            if ($this->dbType === 'mysql') {
                $createTableQuery = str_replace('SERIAL', 'INT AUTO_INCREMENT', $createTableQuery);
            }
            
            $this->connection->exec($createTableQuery);
            
            // Insert message
            $stmt = $this->connection->prepare(
                "INSERT INTO {$tableName} (chat_id, message_text, command) VALUES (:chat_id, :message_text, :command)"
            );
            
            $stmt->bindParam(':chat_id', $chatId, \PDO::PARAM_INT);
            $stmt->bindParam(':message_text', $messageText, \PDO::PARAM_STR);
            $stmt->bindParam(':command', $command, \PDO::PARAM_STR);
            
            $result = $stmt->execute();
            
            // Explicitly close the connection
            $this->disconnect();
            
            return $result;
        } catch (\PDOException $e) {
            error_log("Failed to save message: " . $e->getMessage());
            // Ensure connection is closed even if error occurs
            $this->disconnect();
            return false;
        }
    }
}
 
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


// класс MessagesRouting с зависимостью 
// ChatMessageWriter 
class MessagesRouting {
    private $actions;
    private $telegram;
    private $chat_id;
    private $result;
    private $text;
    private $messageWriter;
    
    function __construct($telegram, ChatMessageWriter $messageWriter = null) {
        $this->actions = [];
        $this->telegram = $telegram;
        $this->messageWriter = $messageWriter;

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
        // Save message to database if writer is available
        if ($this->messageWriter !== null && $this->chat_id !== null) {
            $command = null;
            foreach ($this->actions as $action) {
                if ($text === $action->getCommand()) {
                    $command = $text;
                    break;
                }
            }
            
            $this->messageWriter->saveMessage($this->chat_id, $text, $command);
        }
        
        foreach ($this->actions as $action) {
            $res = $action->process($text);
            if ($res !== "") {
                return $res;
            }
        }
        
        return "Неизвестная команда, смотри помощь /pom";
    }
}    


}
