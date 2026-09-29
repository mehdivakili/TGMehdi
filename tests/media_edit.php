<?php

// Run with: php tests/media_edit.php (after composer install).
require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/src/Helpers/caller.php';

use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;
use Illuminate\Bus\Dispatcher;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Bus\Dispatcher as DispatcherContract;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Queue\SyncQueue;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use TGMehdi\Facades\StateFacade;
use TGMehdi\StateSaver;
use TGMehdi\TelegramBot;
use TGMehdi\Types\InlineKeyboard;
use TGMehdi\Types\InlineMessage;
use TGMehdi\Types\Media;
use TGMehdi\Types\TelegramFile;

$app = new Container();
Container::setInstance($app);
Facade::setFacadeApplication($app);
$app->instance('config', new Repository(['tgmehdi' => [
    'parse_mode' => 'HTML',
    'bots' => ['test' => ['token' => 'test-token']],
]]));
$app->instance(DispatcherContract::class, new class($app) extends Dispatcher {
    public function dispatchSync($command, $handler = null)
    {
        // Exercise Laravel's JSON payload as well as PHP serialization.
        $queue = new SyncQueue();
        $queue->setContainer(Container::getInstance());
        $json = (new ReflectionMethod($queue, 'createPayload'))->invoke($queue, $command, 'test');
        $payload = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        return parent::dispatchSync(unserialize($payload['data']['command']), $handler);
    }
});
StateFacade::swap(new StateSaver());
Storage::swap(new class {
    public function get($path) { return 'stored bytes for ' . $path; }
});

$requests = [];
$handler = function ($request, $options) use (&$requests) {
    $requests[] = $request;
    return Create::promiseFor(new Response(200, ['Content-Type' => 'application/json'], '{"ok":true,"result":{"message_id":42}}'));
};
Http::swap(new class($handler) {
    public function __construct(private $handler) {}
    public function connectTimeout($timeout)
    {
        // Build real HTTP bodies, but never open a network connection.
        return (new PendingRequest())->setHandler($this->handler)->connectTimeout($timeout);
    }
});

function check($condition, $message)
{
    if (!$condition) throw new RuntimeException($message);
}

function keyboard()
{
    $keyboard = new InlineKeyboard();
    $keyboard->newButton('برگشت', 'back');
    return $keyboard;
}

function requestFor($value, $updateType = 'callback_query', $message = [])
{
    global $requests;
    $bot = new TelegramBot();
    $bot->bot = ['name' => 'test', 'message_queue' => null, 'update_types' => ['message', 'callback_query']];
    $message += ['message_id' => 42, 'chat' => ['id' => 123]];
    $bot->data = [$updateType => $updateType === 'callback_query' ? ['message' => $message] : $message];
    $bot->chat_id = 123;
    $bot->input = (object) ['message_id' => 42];
    $before = count($requests);
    general_call($bot, $value);
    $bot->send_reply('', []); // Flush the library's buffered reply.
    check(count($requests) === $before + 1, 'Expected exactly one request');
    return end($requests);
}

function multipartFields($request)
{
    preg_match('/boundary="?([^";]+)"?/', $request->getHeaderLine('Content-Type'), $matches);
    check(isset($matches[1]), 'Expected multipart content');
    $fields = [];
    foreach (explode('--' . $matches[1], (string) $request->getBody()) as $part) {
        if (!str_contains($part, "\r\n\r\n")) continue;
        [$headers, $body] = explode("\r\n\r\n", $part, 2);
        preg_match('/name="([^"]+)"/', $headers, $name);
        $fields[$name[1]] = substr($body, 0, -2);
    }
    return $fields;
}

$bytes = "\x89PNG\r\n\x1a\n\x00\xff\xfe binary config bytes";
$captionCalls = 0;
$request = requestFor(function () use ($bytes, &$captionCalls) {
    return new InlineMessage(keyboard(), Media::withDocumentData('config.txt', $bytes, function () use (&$captionCalls) {
        $captionCalls++;
        return '<b>Config</b>';
    }));
});
check(str_ends_with($request->getUri()->getPath(), '/editMessageMedia'), 'Callback must edit media');
$fields = multipartFields($request);
check($fields['message_id'] === '42' && $fields['chat_id'] === '123', 'Original message must be targeted');
check(json_decode($fields['media'], true) === [
    'type' => 'document', 'media' => 'attach://media_file', 'caption' => '<b>Config</b>', 'parse_mode' => 'HTML',
], 'Invalid InputMedia payload');
check($fields['media_file'] === $bytes, 'Uploaded bytes changed');
check(str_contains((string) $request->getBody(), 'filename="config.txt"'), 'Filename missing');
check(json_decode($fields['reply_markup'], true) === keyboard()->render(), 'Keyboard missing or incorrectly encoded');
check(!isset($fields['parse_mode']), 'Caption parse mode must be nested in media');
check($captionCalls === 1, 'Caption must be evaluated once');

foreach (['document', 'photo', 'video', 'audio', 'animation'] as $type) {
    $request = requestFor(new InlineMessage(keyboard(), Media::withFileID('existing-file-id', $type)));
    check(str_ends_with($request->getUri()->getPath(), '/editMessageMedia'), 'Wrong media edit endpoint');
    $params = json_decode((string) $request->getBody(), true);
    check($params['media']['type'] === $type && $params['media']['media'] === 'existing-file-id', 'File ID or type lost');
    check($params['message_id'] === 42 && $params['chat_id'] === 123, 'Wrong message target');
    check(!isset($params['media_file']) && !isset($params['parse_mode']), 'Unexpected upload or top-level parse mode');
}

$request = requestFor(Media::withDocumentPath('config.txt'));
$fields = multipartFields($request);
check($fields['media_file'] === 'stored bytes for config.txt', 'Storage upload failed');
check($fields['message_id'] === '42', 'Direct media return lost message ID');

$request = requestFor(['edit', Media::withImageFileID('https://example.com/photo.jpg', 'Photo')], 'message');
$params = json_decode((string) $request->getBody(), true);
check($params['media']['media'] === 'https://example.com/photo.jpg' && $params['message_id'] === 42, 'Explicit edit or URL failed');

$request = requestFor(['send', new InlineMessage(keyboard(), Media::withDocumentData('config.txt', $bytes, 'Caption'))]);
$fields = multipartFields($request);
check(str_ends_with($request->getUri()->getPath(), '/sendDocument'), 'Explicit send changed');
check(!isset($fields['message_id']) && $fields['document'] === $bytes, 'Invalid send payload');
check(json_decode($fields['reply_markup'], true) === keyboard()->render(), 'Send keyboard changed');

foreach (['send', 'edit'] as $action) {
    $request = requestFor([$action, new InlineMessage(keyboard(), Media::withData('config.png', $bytes, 'photo', 'QR caption'))]);
    $fields = multipartFields($request);
    $endpoint = $action === 'send' ? 'sendPhoto' : 'editMessageMedia';
    $fileField = $action === 'send' ? 'photo' : 'media_file';
    check(str_ends_with($request->getUri()->getPath(), '/' . $endpoint), 'Wrong photo endpoint');
    check($fields[$fileField] === $bytes, 'Queued photo bytes changed');
    check(str_contains((string) $request->getBody(), 'filename="config.png"'), 'Photo filename missing');
}

foreach ([null, '', '0', 'plain text', $bytes] as $data) {
    $file = new TelegramFile('config.bin', $data);
    $json = json_encode(['file' => serialize($file)], JSON_THROW_ON_ERROR);
    $restored = unserialize(json_decode($json, true, 512, JSON_THROW_ON_ERROR)['file']);
    check($restored->data === $data, 'File data changed during serialization');
    check($restored->path === $file->path && $restored->name === $file->name, 'File metadata changed');

    // An old job contains public properties directly, without a base64 marker.
    $properties = serialize('path') . serialize($file->path)
        . serialize('name') . serialize($file->name)
        . serialize('data') . serialize($data);
    $legacy = sprintf('O:%d:"%s":3:{%s}', strlen(TelegramFile::class), TelegramFile::class, $properties);
    $restored = unserialize($legacy);
    check($restored->data === $data, 'Previously queued attachment is no longer readable');
}

$request = requestFor(new InlineMessage(keyboard(), 'Updated text'));
check(str_ends_with($request->getUri()->getPath(), '/editMessageText'), 'Text editing changed');
check(json_decode((string) $request->getBody(), true)['text'] === 'Updated text', 'Text changed');

foreach ([Media::withFileID('voice-id', 'voice'), Media::withDocumentFileID(null)] as $invalid) {
    $before = count($requests);
    try {
        requestFor($invalid);
        throw new RuntimeException('Expected invalid media to be rejected');
    } catch (InvalidArgumentException $e) {
        check(count($requests) === $before, 'Invalid edit sent a request');
    }
}

foreach (['document', 'photo', 'video', 'audio', 'animation', 'voice', 'paid_media', 'live_photo'] as $type) {
    // No existing caption: detection must use the media, not the caption field.
    $request = requestFor(new InlineMessage(keyboard(), '<b>Back to menu</b>'), 'callback_query', [$type => ['file_id' => 'original']]);
    check(str_ends_with($request->getUri()->getPath(), '/editMessageCaption'), 'Media text must edit the caption');
    $params = json_decode((string) $request->getBody(), true);
    check($params === [
        'reply_markup' => keyboard()->render(), 'message_id' => 42, 'chat_id' => 123,
        'caption' => '<b>Back to menu</b>', 'parse_mode' => 'HTML',
    ], 'Caption edit must preserve the target, formatting, and keyboard without replacing media');
}

$mediaMessage = ['document' => ['file_id' => 'original'], 'caption' => 'Old caption'];
foreach (['', '0', 'New caption'] as $text) {
    $request = requestFor(new InlineMessage(keyboard(), $text), 'callback_query', $mediaMessage);
    check(str_ends_with($request->getUri()->getPath(), '/editMessageCaption'), 'Wrong caption endpoint');
    check(json_decode((string) $request->getBody(), true)['caption'] === $text, 'Caption text changed');
}

$request = requestFor('Direct text', 'callback_query', $mediaMessage);
check(str_ends_with($request->getUri()->getPath(), '/editMessageCaption'), 'Direct text return must edit caption');
check(json_decode((string) $request->getBody(), true)['caption'] === 'Direct text', 'Direct caption missing');

$entities = [['type' => 'bold', 'offset' => 0, 'length' => 4]];
$request = requestFor(new InlineMessage(keyboard(), [
    'text' => 'Menu', 'entities' => $entities, 'parse_mode' => '',
    'link_preview_options' => ['is_disabled' => true], 'disable_web_page_preview' => true,
]), 'callback_query', $mediaMessage);
$params = json_decode((string) $request->getBody(), true);
check($params['caption_entities'] === $entities && $params['parse_mode'] === '', 'Caption formatting options lost');
check(!isset($params['text']) && !isset($params['entities']) && !isset($params['link_preview_options'])
    && !isset($params['disable_web_page_preview']), 'Text-only fields leaked into caption request');

$request = requestFor(['edit', new InlineMessage(keyboard(), 'Explicit edit')], 'message', $mediaMessage);
check(str_ends_with($request->getUri()->getPath(), '/editMessageCaption'), 'Explicit edit failed');

$request = requestFor(['send', new InlineMessage(keyboard(), 'Separate text')], 'callback_query', $mediaMessage);
check(str_ends_with($request->getUri()->getPath(), '/sendMessage'), 'Explicit send must still send text');

foreach ([['text' => 'Original text'], ['sticker' => ['file_id' => 'sticker']]] as $source) {
    $request = requestFor(new InlineMessage(keyboard(), 'New text'), 'callback_query', $source);
    check(str_ends_with($request->getUri()->getPath(), '/editMessageText'), 'Non-caption message misclassified');
}

foreach ([[99, 123], [42, 456]] as [$messageId, $chatId]) {
    $request = requestFor(function (TelegramBot $tg) use ($messageId, $chatId) {
        $tg->edit_message_text('Other target', $messageId, ['chat_id' => $chatId]);
        return false;
    }, 'callback_query', $mediaMessage);
    check(str_ends_with($request->getUri()->getPath(), '/editMessageText'), 'Type inferred from a different message or chat');
}

echo "Media and caption edit regression checks passed.\n";
