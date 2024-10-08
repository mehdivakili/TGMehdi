<?php

namespace TGMehdi\Test;

use Illuminate\Support\Facades\Http;
use TGMehdi\Facades\TGFacade;
use TGMehdi\Routing\BotRout;

abstract class Tester
{
    protected $input_prefix;

    protected $bot_name;
    protected $bot_config = null;

    protected $chat_id;

    public function __construct($bot_name, $chat_id, $input_prefix = "TEST ")
    {
        $this->bot_name = $bot_name;
        $this->chat_id = $chat_id;
        $this->input_prefix = $input_prefix;
        $this->bot_config = config('tgmehdi.bots.' . $this->bot_name);

    }

    public function routes()
    {
        BotRout::any("/^" . $this->input_prefix . ".*$/", function () {

        }, priority: 100);
        BotRout::callback("/^" . $this->input_prefix . ".*$/", function () {

        }, priority: 100);
    }

    abstract public function run();

    public function send_request($request)
    {
        TGFacade::switch_bot($this->bot_name);
        TGFacade::set_chat_id($this->chat_id);
        $payload = [];
        switch ($request[0]) {
            case 'text':
                TGFacade::send_text($this->input_prefix . $request[1], true);
                $payload = $this->create_text_payload($request[1]);
                break;
            case 'callback':
                TGFacade::send_text($this->input_prefix . $request[1], true);
                $payload = $this->create_callback_payload($request[1], $request[2]);
                break;
            case "member":
                TGFacade::send_text($this->input_prefix . ' member ' . $request[2], true);
                $payload = $this->create_member_payload($request[1], $request[2], $request[3]);
                break;
            case "raw":
                TGFacade::send_text($this->input_prefix . ' raw ' . json_encode($request[1]), true);
                $payload = $request[1];
        }
        return Http::withHeader('X-Telegram-Bot-Api-Secret-Token', $this->bot_config['secret_token'])->post(route('tgmehdi.bot', ['bot_name' => $this->bot_name]), $payload)->body();

    }

    public function create_text_payload($text)
    {
        return [
            "update_id" => 174638372,
            "message" => [
                "message_id" => 983,
                "from" => [
                    "id" => $this->chat_id,
                    "is_bot" => true,
                    "first_name" => "bot",
                    "last_name" => "bot",
                    "username" => "bot",
                    "language_code" => "en"
                ],
                "chat" => [
                    "id" => $this->chat_id,
                    "first_name" => "bot",
                    "last_name" => "bot",
                    "username" => "bot",
                    "type" => "private"
                ],
                "date" => 1724947599,
                "text" => $text,
            ]
        ];
    }

    public function create_callback_payload($text, $message_id)
    {
        return [
            "update_id" => 174638375,
            "callback_query" => [
                "id" => "1086873459091455165",
                "from" => [
                    "id" => $this->chat_id,
                    "is_bot" => true,
                    "first_name" => "bot",
                    "last_name" => "bot",
                    "username" => "bot",
                    "language_code" => "en"
                ],
                "message" => [
                    "message_id" => $message_id,
                    "from" => [
                        "id" => $this->chat_id,
                        "is_bot" => true,
                        "first_name" => "bot",
                        "last_name" => "bot",
                        "username" => "bot",
                        "language_code" => "en"
                    ],
                    "chat" => [
                        "id" => $this->chat_id,
                        "first_name" => "bot",
                        "last_name" => "bot",
                        "username" => "bot",
                        "type" => "private"
                    ],
                    "date" => 1724947859,
                    "text" => $this->input_prefix,
                ],
                "chat_instance" => "2762021276901289237",
                "data" => $text,
            ]
        ];
    }

    public function get_message_ids($payload)
    {
        $m_ids = [];
        foreach ($payload as $message) {
            if (isset($message["result"]["message_id"]))
                $m_ids[] = $message["result"]["message_id"];
            elseif (isset($message["result"]["message"])) {
                $m_ids[] = $message["result"]["message"]["message_id"];
            }
        }
        return $m_ids;
    }

    public function get_last_message_id($payload)
    {
        $r = $this->get_message_ids($payload);
        return $r[array_key_last($r)];

    }

    private function create_member_payload($group_chat_id, string $new_status, string $old_status, string|null $invite_link = null)
    {

        return [
            "update_id" => 174638372,
            "chat_member" => [
                "from" => [
                    "id" => $this->chat_id,
                    "is_bot" => true,
                    "first_name" => "bot",
                    "last_name" => "bot",
                    "username" => "bot",
                    "language_code" => "en"
                ],
                "chat" => [
                    "id" => $group_chat_id,
                    "first_name" => "test",
                    "last_name" => "test",
                    "username" => "test",
                    "type" => "group"
                ],
                "old_chat_member" => [
                    "status" => $old_status,
                    "user" => [
                        "id" => $this->chat_id,
                        "is_bot" => true,
                        "first_name" => "bot",
                        "last_name" => "bot",
                        "username" => "bot",
                        "language_code" => "en"
                    ],
                    "is_anonymous" => true
                ],
                "new_chat_member" => [
                    "status" => $new_status,
                    "user" => [
                        "id" => $this->chat_id,
                        "is_bot" => true,
                        "first_name" => "bot",
                        "last_name" => "bot",
                        "username" => "bot",
                        "language_code" => "en"
                    ],
                    "is_anonymous" => true
                ],
                "invite_link" => null,
                "via_join_request" => false,
                "via_chat_folder_invite_link" => false,
                "date" => 1724947599,
            ]
        ];

    }
}