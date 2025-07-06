<?php


namespace TGMehdi\Types;


class InlineKeyboard extends TelegramBaseKeyboard
{
    public mixed $state = null;
    public mixed $temp = null;
    protected $keyboard = array(
        'inline_keyboard' => array(
            array()
        )
    );

    public function newLine()
    {
        if (count($this->keyboard['inline_keyboard'][count($this->keyboard['inline_keyboard']) - 1]))
            $this->keyboard['inline_keyboard'][] = array();
    }

    public function newButton($text, $data = 'trash', $v2 = false, $options = [], $line = -1)
    {
        $line = ($line < 0) ? count($this->keyboard['inline_keyboard']) - $line - 2 : $line;
        $options['text'] = $text;
        if ($v2) {
            $options['has_state'] = true;
        }
        if (!isset($options['web_app']))
            $options['callback_data'] = $data;
        $this->keyboard['inline_keyboard'][$line][] = $options;
    }

    public function render()
    {
        foreach ($this->keyboard['inline_keyboard'] as $line => $row) {
            foreach ($row as $key => $options) {

                if (isset($options['has_state']) and $options['has_state']) {
                    $this->keyboard["inline_keyboard"][$line][$key]['callback_data'] = "-\n" . $options['callback_data'];
                    if (!is_null($this->state)) {
                        $s = $this->state;
                        if ($s != '.') $s = substr($s, 1, strlen($s) - 2);
                        $this->keyboard["inline_keyboard"][$line][$key]['callback_data'] .= "\n" . $s;
                        if (!is_null($this->temp)) {
                            $this->keyboard["inline_keyboard"][$line][$key]['callback_data'] .= "\n" . http_build_query($this->temp);
                        }
                    }
                    unset($this->keyboard["inline_keyboard"][$line][$key]['has_state']);
                }
            }
        }
        return $this->keyboard;
    }

}
