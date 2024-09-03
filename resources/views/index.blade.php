@foreach(config('tgmehdi.bots') as $bot_name => $config)
    <h1>{{$bot_name}}</h1>
    <a href="{{route('tgmehdi.bot',['bot_name'=>$bot_name])}}">bot url for telegram</a><br>
    <a href="{{route('tgmehdi.local_bot',['bot_name'=>$bot_name])}}">run but locally</a><br>
    <a href="{{route('tgmehdi.set_webhook',['bot_name'=>$bot_name])}}">set webhook</a><br>
    <a href="{{route('tgmehdi.delete_webhook',['bot_name'=>$bot_name])}}">delete webhook</a><br>
    <a href="{{route('tgmehdi.restart_webhook',['bot_name'=>$bot_name])}}">restart webhook</a>
@endforeach