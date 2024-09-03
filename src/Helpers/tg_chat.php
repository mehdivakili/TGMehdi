<?php

use TGMehdi\Facades\TGFacade;
use TGMehdi\States\StateBase;

if (!function_exists("tg_chat")) {

    function tg_chat($chat_id = null)
    {
        if ($chat_id) {
            TGFacade::set_chat_id($chat_id);
            return TGFacade::chat(true);
        }
        return TGFacade::chat();
    }

    function tg_goto($tg, $state, $data = [])
    {
        $class = new StateBase(add_to_states: false);
        $class->init($tg);
        $class->goto($state, $data);
    }


    function keyboardPagination($prefix, $query, $keyboard, $func, $page, $per_page = 10)
    {
        $query = clone $query;
        $items = $query->offset($page * $per_page)->limit($per_page + 1)->get();
        $i = 0;
        $keyboard->newLine();
        foreach ($items as $item) {
            if ($i++ >= $per_page) break;
            $func($item, $keyboard, $page, $i);
            $keyboard->newLine();
        }

        if ($page != 0) {
            $keyboard->newButton(__("tgmehdi.buttons.previous"), $prefix . ($page - 1));
        }
        if (!($page == 0 and count($items) <= $per_page)) {
            $keyboard->newButton(__('tgmehdi.buttons.page', ['page' => $page + 1]));
        }

        if (count($items) > $per_page) {
            $keyboard->newButton(__('tgmehdi.buttons.next'), $prefix . ($page + 1));
        }
        $keyboard->newLine();

    }


}
