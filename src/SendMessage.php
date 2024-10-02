<?php


namespace TGMehdi;


use TGMehdi\Jobs\SendRequest;
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
        $files = [];
        if ($url != '') {
            $post_params['chat_id'] = isset($post_params['chat_id']) ? $post_params['chat_id'] : $this->chat_id;

            if ($this->reply_message_id) {
                $post_params['reply_to_message_id'] = $this->reply_message_id;
            }
            foreach ($post_params as $k => $p) {
                if ($p instanceof TelegramFile) {
                    $files[$k] = $p;
                    unset($post_params[$k]);
                } elseif ($p instanceof View) {
                    $post_params[$k] = $p->render();
                }
            }
            if (!isset($post_params['parse_mode'])) {
                $post_params['parse_mode'] = config('tgmehdi.parse_mode');
            }

            if (!$immediately) {
                $old_reply = $this->old_reply;
                $this->old_reply = ['post_params' => $post_params, 'url' => $url, 'files' => $files];
                if (!$old_reply) return false;
                $post_params = $old_reply['post_params'];
                $url = $old_reply['url'];
                $files = $old_reply['files'];
            }
        } else if ($this->old_reply) {
            $post_params = $this->old_reply['post_params'];
            $url = $this->old_reply['url'];
            $files = $this->old_reply['files'];
            $this->old_reply = null;
        }
        if ($this->keyboard and !$this->keyboard->is_sended and $url and empty($post_params['reply_markup'])) {
            $post_params['reply_markup'] = $this->keyboard->render();
        }
        $res = false;
        if ($url != "") {
            if ($this->bot['message_queue'])
                SendRequest::dispatch($this->bot['name'], $url, $post_params, $files)->onQueue('message_answers');
            else {
                $n = count(BotController::$results);
                SendRequest::dispatchSync($this->bot['name'], $url, $post_params, $files);
                if ($n != count(BotController::$results))
                    $res = BotController::$results[array_key_last(BotController::$results)];
            }
            return $res;
        }
        return false;
    }

    public
    function send_text($text, $immediately = false, $options = [])
    {
        $rs = $this->send_reply('sendMessage', array_merge($options, ['text' => $text]), $immediately);
        if (!isset($rs['result']))
            return false;

        return $this->message_init($rs["result"], "sent");
    }

    public
    function send_photo($photo, $caption)
    {

        return $this->message_init($this->send_reply('sendPhoto', ["photo" => $photo, "caption" => $caption])['result'], "sent");
    }

    public
    function send_audio($audio, $caption)
    {
        return $this->message_init($this->send_reply('sendAudio', ["audio" => $audio, "caption" => $caption])['result'], "sent");
    }

    public
    function send_document($document, $caption)
    {
        return $this->message_init($this->send_reply('sendDocument', ["document" => $document, "caption" => $caption])['result'], "sent");
    }

    public
    function send_video($video, $caption)
    {
        return $this->message_init($this->send_reply('sendVideo', ["video" => $video, "caption" => $caption])['result'], "sent");
    }

    public
    function send_video_note($video, $caption)
    {
        return $this->message_init($this->send_reply('sendVideoNote', ["videoNote" => $video, "caption" => $caption])['result'], "sent");
    }

    public
    function send_chat_action($action)
    {
        return $this->send_reply('sendChatAction', ["action" => $action])['result'];
    }

    public
    function create_chat_invite_link($chat_id, $name = null)
    {
        $options = ["chat_id" => $chat_id];
        if (!empty($name))
            $options['name'] = $name;
        return $this->send_reply('createChatInviteLink', $options, true)['result'];
    }

    public
    function send_poll($question, $options, $type = 'regular')
    {
        $this->send_reply('sendPoll', ["question" => $question, 'options' => $options, 'type' => $type])['result'];
    }

    public
    function delete_message($message_id)
    {
        $this->send_reply('deleteMessage', ["message_id" => $message_id], true)['result'];
    }

    public
    function answer_callback($text, $options = [])
    {
        $options['callback_query_id'] = $this->data['callback_query']['id'];
        $options['text'] = $text;
        $options['show_alert'] = true;
        return Http::connectTimeout(20)->withOptions(['proxy' => config('tgmehdi.proxy', null), 'verify' => false
        ])->post($this->bot_url . '/' . 'answerCallbackQuery', $options);
    }

    public
    function get_chat_member($chat_id, $user_id)
    {
        return $this->send_reply('getChatMember', ['user_id' => $user_id, 'chat_id' => $chat_id], true);
    }

    public
    function get_chat($chat_id)
    {
        return $this->send_reply('getChat', ['chat_id' => $chat_id], true);
    }

    public
    function edit_message_text($text, $message = null, $options = [])
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
