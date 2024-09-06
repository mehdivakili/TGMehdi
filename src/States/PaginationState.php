<?php

namespace TGMehdi\States;

use TGMehdi\Routing\BotRout;
use TGMehdi\Types\InlineKeyboard;
use TGMehdi\Types\InlineMessage;

class PaginationState extends StateBase
{
    protected $state = ".";
    protected $output_state = ".same.";
    protected $query;
    protected $page_limit = 10;
    protected $page = 0;
    protected $prefix = "page";
    protected $func;
    protected $next_text = 'Next';
    protected $prev_text = 'Previous';

    protected $page_text = 'Page ';
    protected $startKeyboard;
    protected $endKeyboard;
    protected $afterKeyboard;

    public function setProps($prefix, $page_limit = 10)
    {
        $this->prefix = $prefix;
        $this->page_limit = $page_limit;
        return $this;
    }

    public function setQuery($query)
    {
        $this->query = $query;
        return $this;
    }

    public function setFunc($func)
    {
        $this->func = $func;
        return $this;
    }

    public function setStartKeyboard($func)
    {
        $this->startKeyboard = $func;
        return $this;
    }

    public function setTranslations($next, $page, $prev)
    {
        $this->next_text = $next;
        $this->prev_text = $prev;
        $this->page_text = $page . ' ';
        return $this;
    }

    public function setEndKeyboard($func)
    {
        $this->endKeyboard = $func;
        return $this;
    }

    public function setAfterKeyboard($func)
    {
        $this->afterKeyboard = $func;
        return $this;
    }

    public function startKeyboard()
    {
        $c = $this->startKeyboard;
        if (is_callable($c))
            $c($this);
    }

    public function endKeyboard()
    {
        $c = $this->endKeyboard;
        if (is_callable($c))
            $c($this);
    }

    public function afterKeyboard()
    {
        $this->exec(new InlineMessage($this->keyboard, $this->afterKeyboard));
    }

    public function func($item, $index)
    {
        if (is_callable($this->func)) {
            $c = $this->func;
            $arr = $c($item, $index);
            if (is_array($arr[0])) {
                foreach ($arr as $v) {
                    $this->keyboard->newButton(...$v);
                }
                $this->keyboard->newLine();
            } else {
                $this->keyboard->newButton(...$arr);
                $this->keyboard->newLine();

            }
        }
    }

    public function afterEnter()
    {
        $this->make_keyboard(0);
        $this->afterKeyboard();
        return parent::afterEnter();
    }

    public function registerRoutes()
    {
        $p = $this->prefix;
        BotRout::callback("$p{{page|number?0}}", [$this, "handle"])->set_state_class($this)->name($p);
        parent::registerRoutes();
    }

    public function make_keyboard($page)
    {
        $this->page = $page;
        if (is_callable($this->query)) {
            $c = $this->query;
            $query = $c($this);
        } else
            $query = $this->query;
        $items = $query->offset($page * $this->page_limit)->limit($this->page_limit + 1)->get();
        $i = 0;
        $this->keyboard = new InlineKeyboard();
        $this->startKeyboard();
        $this->keyboard->newLine();
        foreach ($items as $item) {
            if ($i++ >= $this->page_limit) break;
            $this->func($item, $i);
            $this->keyboard->newLine();
        }
        if ($page != 0) {
            $this->keyboard->newButton($this->prev_text, $this->prefix . ($page - 1));
        }
        if (!($page == 0 and count($items) <= $this->page_limit)) {
            $this->keyboard->newButton($this->page_text . ($page + 1));
        }

        if (count($items) > $this->page_limit) {
            $this->keyboard->newButton($this->next_text, $this->prefix . ($page + 1));
        }
        $this->keyboard->newLine();
        $this->endKeyboard();
    }

    public function handle($page = 0)
    {
        $this->make_keyboard($page);

        $this->afterKeyboard();
    }
}