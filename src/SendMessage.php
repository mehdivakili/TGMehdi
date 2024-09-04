<?php


namespace TGMehdi;


use TGMehdi\Types\InlineKeyboard;
use TGMehdi\Types\ReplyKeyboard;
use TGMehdi\Types\TelegramBaseKeyboard;
use TGMehdi\Types\TelegramFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;


trait SendMessage
{
    private $old_reply;

    public function send_reply($url, $post_params, $immediately = false)
    {
        if ($url != '') {
            /*["chat_id" => $chat_id, "text" => $text, 'reply_markup' => $kb, "reply_to_message_id" => $reply];*/
            $post_params['chat_id'] = isset($post_params['chat_id']) ? $post_params['chat_id'] : $this->chat_id;

            if ($this->reply_message_id) {
                $post_params['reply_to_message_id'] = $this->reply_message_id;
            }
            $request = null;
            $has_file = false;
            foreach ($post_params as $k => $p) {
                if ($p instanceof TelegramFile) {
                    if (!$has_file) {
                        $request = Http::attach($k, $p->data, $p->name);
                        $has_file = true;
                    } else {
                        $request = $request->attach($k, $p->data, $p->name);
                    }
                    //Storage::put('file.jpg', $p->data);
                    unset($post_params[$k]);
                } elseif ($p instanceof View) {
                    $post_params[$k] = $p->render();
                }
            }
            if (!isset($post_params['parse_mode'])) {
                $post_params['parse_mode'] = config('tgmehdi.parse_mode');
            }
        }
        if (!$immediately) {
            $old_reply = $this->old_reply;
            if ($url != '')
                $this->old_reply = ['has_file' => $has_file, 'request' => $request ?? null, 'post_params' => $post_params, 'url' => $url];
            if ($old_reply == null) return false;
            $has_file = $old_reply['has_file'];
            $request = $old_reply['request'];
            $post_params = $old_reply['post_params'];
            $url = $old_reply['url'];

        }
        if ($this->keyboard) {
            if (is_array($this->keyboard))
                $post_params['reply_markup'] = json_encode($this->keyboard);
            elseif (is_string($this->keyboard))
                $post_params['reply_markup'] = $this->keyboard;
            elseif ($this->keyboard instanceof InlineKeyboard and str_starts_with($url, "edit")) {
                $post_params['reply_markup'] = $this->keyboard->render();
                $this->keyboard = null;
            } elseif ($this->keyboard instanceof ReplyKeyboard and str_starts_with($url, "send")) {
                $post_params['reply_markup'] = $this->keyboard->render();
                $this->keyboard = null;
            }
        }
        if ($has_file)
            $res = $request->post($this->bot_url . '/' . $url, $post_params);
        else
            $res = Http::connectTimeout(20)->withOptions(['proxy' => config('tgmehdi.proxy', null), 'verify' => false
            ])->post($this->bot_url . '/' . $url, $post_params);
        BotController::add_res($res->json());
        return $res->json();
    }

    public function send_text($text, $immediately = false)
    {
        $rs = $this->send_reply('sendMessage', ['text' => $text], $immediately);
        if (!isset($rs['result']))
            return false;

        return $this->message_init($rs["result"], "sent");
    }

    public function send_photo($photo, $caption)
    {

        return $this->message_init($this->send_reply('sendPhoto', ["photo" => $photo, "caption" => $caption])['result'], "sent");
    }

    public function send_audio($audio, $caption)
    {
        return $this->message_init($this->send_reply('sendAudio', ["audio" => $audio, "caption" => $caption])['result'], "sent");
    }

    public function send_document($document, $caption)
    {
        return $this->message_init($this->send_reply('sendDocument', ["document" => $document, "caption" => $caption])['result'], "sent");
    }

    public function send_video($video, $caption)
    {
        return $this->message_init($this->send_reply('sendVideo', ["video" => $video, "caption" => $caption])['result'], "sent");
    }

    public function send_video_note($video, $caption)
    {
        return $this->message_init($this->send_reply('sendVideoNote', ["videoNote" => $video, "caption" => $caption])['result'], "sent");
    }

    public function send_chat_action($action)
    {
        return $this->send_reply('sendChatAction', ["action" => $action])['result'];
    }

    public function create_chat_invite_link($chat_id, $name = null)
    {
        $options = ["chat_id" => $chat_id];
        if (!empty($name))
            $options['name'] = $name;
        return $this->send_reply('createChatInviteLink', $options, true)['result'];
    }

    public function send_poll($question, $options, $type = 'regular')
    {
        $this->send_reply('sendPoll', ["question" => $question, 'options' => $options, 'type' => $type])['result'];
    }

    public function delete_message($message_id)
    {
        $this->send_reply('deleteMessage', ["message_id" => $message_id], true)['result'];
    }

    public function answer_callback($text, $options = [])
    {
        $options['callback_query_id'] = $this->callback_model->id;
        $options['text'] = $text;
        $options['show_alert'] = true;
        return Http::connectTimeout(20)->withOptions(['proxy' => config('tgmehdi.proxy', null), 'verify' => false
        ])->post($this->bot_url . '/' . 'answerCallbackQuery', $options);
    }

    public function get_chat_member($chat_id, $user_id)
    {
        return $this->send_reply('getChatMember', ['user_id' => $user_id, 'chat_id' => $chat_id], true);
    }

    public function get_chat($chat_id)
    {
        return $this->send_reply('getChat', ['chat_id' => $chat_id], true);
    }

    public function edit_message_text($text, $message = null, $options = [])
    {
        if ($message != null) {
            $options['message_id'] = $message;
        } else {
            $options['message_id'] = $this->input->message_id;
        }
        $options['text'] = $text;
        return $this->send_reply('editMessageText', $options, true);

    }

}
