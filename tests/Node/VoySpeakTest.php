<?php

namespace PlanetTeamSpeak\TeamSpeak3Framework\Tests\Node;

use PHPUnit\Framework\TestCase;
use PlanetTeamSpeak\TeamSpeak3Framework\Adapter\ServerQuery;
use PlanetTeamSpeak\TeamSpeak3Framework\Adapter\ServerQuery\Reply;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\ServerQueryException;
use PlanetTeamSpeak\TeamSpeak3Framework\Helper\Signal;
use PlanetTeamSpeak\TeamSpeak3Framework\Helper\StringHelper;
use PlanetTeamSpeak\TeamSpeak3Framework\Node\Host;
use PlanetTeamSpeak\TeamSpeak3Framework\Node\Server;
use PlanetTeamSpeak\TeamSpeak3Framework\TeamSpeak3;

class VoySpeakTest extends TestCase
{
    private VoySpeakFixtureQuery $query;

    private Host $host;

    private Server $server;

    protected function setUp(): void
    {
        Signal::getInstance()->clearHandlers('notifyEvent');

        $this->query = new VoySpeakFixtureQuery();
        $this->host = new Host($this->query);
        $this->server = new Server($this->host, ['virtualserver_id' => 1]);
    }

    public function testMusicBotCommands(): void
    {
        $this->query->replies['musicbotcreate cid=5 type=2'] = 'bot_id=8';
        $this->query->replies['clientlist -uid'] = 'clid=1 client_database_id=8 client_type=4|clid=2 client_database_id=1 client_type=1';

        $this->assertSame(8, $this->server->musicBotCreate(5, TeamSpeak3::MUSICBOT_TYPE_TEMPORARY));
        $this->assertSame([8], array_keys($this->server->musicBotList()));

        $this->server->musicBotPlay(8);
        $this->assertSame('musicbotplayeraction bot_id=8 action=1', $this->query->last());
        $this->server->musicBotForward(8, 1500);
        $this->assertSame('musicbotplayeraction bot_id=8 action=5 units=1500', $this->query->last());
        $this->server->musicBotPlaylistAssign(8, 0);
        $this->assertSame('musicbotplaylistassign bot_id=8 playlist_id=0', $this->query->last());
    }

    public function testPlaylistCommands(): void
    {
        $this->query->replies['playlistcreate'] = 'playlist_id=3';
        $this->query->replies['playlistsonglist playlist_id=3 -extract-metadata'] = 'version=2 playlist_id=3 song_id=1 song_url=a|song_id=2 song_url=b';

        $this->assertSame(3, $this->server->playlistCreate(['playlist_title' => 'My list']));
        $this->assertSame('playlistedit playlist_id=3 playlist_title=My\slist', $this->query->last());
        $this->assertSame([1, 2], array_keys($this->server->playlistSongList(3, true)));

        $this->server->playlistSongAdd(3, 'x', 'ffmpeg');
        $this->assertSame('playlistsongadd playlist_id=3 url=x type=ffmpeg', $this->query->last());
        $this->server->playlistPermAssign(3, ['i_a', 'i_b'], [1, 2]);
        $this->assertSame('playlistaddperm playlist_id=3 permsid=i_a permvalue=1 permnegated=0 permskip=0|permsid=i_b permvalue=2 permnegated=0 permskip=0', $this->query->last());
        $this->server->playlistClientPermRemove(3, 7, 99);
        $this->assertSame('playlistclientdelperm playlist_id=3 cldbid=7 permid=99', $this->query->last());
    }

    public function testConversationHistoryReportsMoreData(): void
    {
        $this->query->replies['conversationhistory -merge cid=7 message_count=2'] = "cid=7 flag_volatile=0 timestamp=10 msg=a\ntimestamp=20 msg=b";
        $this->query->errors['conversationhistory -merge cid=7 message_count=2'] = 'error id=8705 msg=there\sare\smore\smessages\sto\ssend';

        $history = $this->server->conversationHistory(7, null, null, 2);

        $this->assertTrue($history['more_data']);
        $this->assertSame([10, 20], array_column($history['messages'], 'timestamp'));
    }

    public function testConversationHistoryThrowsErrors(): void
    {
        $this->query->errors['conversationhistory -merge cid=7'] = 'error id=2568 msg=insufficient';

        $this->expectException(ServerQueryException::class);
        $this->expectExceptionCode(0xA08);
        $this->server->conversationHistory(7);
    }

    public function testPrivilegeKeyActions(): void
    {
        $cmd = 'tokenadd token_description=x token_max_uses=2 action_type=1 action_id1=6|action_type=4 action_id2=7';
        $this->query->replies[$cmd] = 'action_id=3 token=abc token_id=5|action_id=4';

        $token = $this->server->privilegeKeyCreateActions([
            ['type' => TeamSpeak3::TOKEN_ACTION_SERVERGROUP_ADD, 'id1' => 6],
            ['type' => TeamSpeak3::TOKEN_ACTION_CHANNEL_JOIN, 'id2' => 7],
        ], 'x', 2);

        $this->assertSame(5, $token['token_id']);
        $this->server->privilegeKeyModify(5, [['id' => 3]]);
        $this->assertSame('tokenedit token_id=5 action_id=3', $this->query->last());
    }

    public function testBulkedErrorsAreThrown(): void
    {
        $error = new StringHelper('error id=0 msg=ok|id=4864 msg=Invalid\sgroup\sid|id=0 msg=ok');
        $reply = new Reply([$error], 'tokenadd', null, false);

        $this->assertSame(0x1300, $reply->getErrorProperty('id'));
        $this->assertSame([0, 0x1300, 0], array_column($reply->getErrors(), 'id'));

        $this->expectException(ServerQueryException::class);
        $this->expectExceptionMessage('Invalid group id');
        new Reply([$error], 'tokenadd');
    }

    public function testPasswordVerification(): void
    {
        $this->assertTrue($this->server->channelPasswordVerify(1, 'right'));

        $this->query->errors['verifychannelpassword cid=1 password=wrong'] = 'error id=1028 msg=invalid\sserver\spassword';
        $this->assertFalse($this->server->channelPasswordVerify(1, 'wrong'));
    }

    public function testConnectionInfoIsReadFromNotification(): void
    {
        $this->query->replies['serverrequestconnectioninfo'] = 'notifyserverconnectioninfo connection_ping=12';

        $this->assertSame(['connection_ping' => 12], $this->server->connectionInfo());
    }

    public function testNotifyRegistration(): void
    {
        $this->server->notifyRegister('music', 0, 'player');
        $this->assertSame('servernotifyregister event=music id=0 specifier=player', $this->query->last());

        $this->query->errors['servernotifyunregister'] = 'error id=1539 msg=parameter\snot\sfound';
        $this->server->notifyUnregister();
        $this->assertSame('servernotifyunregister event=all specifier=all', $this->query->last());
    }

    public function testMissingIdsAreResolved(): void
    {
        $this->query->replies['banlist -count'] = 'banid=1 ip=1.2.3.4 name uid|banid=2 ip=1.2.3.4 name uid|banid=3 ip=5.6.7.8 name uid';
        $this->query->replies['banlist -count duration=1'] = $this->query->replies['banlist -count'];
        $this->query->replies['channellist -topic -flags -voice -limits -icon'] = 'cid=1 pid=0 channel_name=A|cid=9 pid=0 channel_name=New|cid=4 pid=1 channel_name=New';

        $this->assertSame(2, $this->server->banCreate(['ip' => '1.2.3.4']));
        $this->assertSame(3, $this->server->banCount());
        $this->assertSame(9, $this->server->channelCreate(['channel_name' => 'New']));
        $this->assertSame(4, $this->server->channelCreate(['channel_name' => 'New', 'cpid' => 1]));
    }

    public function testSnapshotDeployWithoutMapping(): void
    {
        $this->query->replies['serversnapshotdeploy -mapping data=x'] = 'virtualserver_port=9987 sid=4';

        $this->assertSame(['virtualserver_port' => 9987, 'sid' => 4, 'mapping' => []], $this->server->snapshotDeploy('data=x'));
    }

    public function testInstanceCommands(): void
    {
        $this->query->replies['querylist'] = 'server_id=-1 client_login_name=serveradmin|client_login_name=bot';
        $this->query->replies['querycreate client_login_name=bot sid=1'] = 'client_login_name=bot client_login_password=pw';
        $this->query->replies['listfeaturesupport'] = 'name=video support=1 version=1';

        $this->assertSame(['serveradmin', 'bot'], array_keys($this->host->queryAccountList()));
        $this->assertSame('pw', (string)$this->host->queryAccountCreate('bot', null, 1)['client_login_password']);
        $this->assertSame(['video'], array_keys($this->host->featureList()));

        $this->host->logQuery(5);
        $this->assertSame('logquery instance=1 limit=5', $this->query->last());
        $this->server->logQuery(5, null, null, ['server', 'client']);
        $this->assertSame('logquery limit=5 groups=server,client', $this->query->last());
    }
}

class VoySpeakFixtureQuery extends ServerQuery
{
    public array $replies = [
        'whoami' => 'virtualserver_id=1 virtualserver_port=9987 client_id=2 client_channel_id=1',
    ];

    public array $errors = [];

    private array $commands = [];

    public function __construct()
    {
    }

    public function __destruct()
    {
    }

    public function last(): string
    {
        return end($this->commands);
    }

    public function request(string $cmd, bool $throw = true): Reply
    {
        if ($cmd !== 'whoami') {
            $this->commands[] = $cmd;
        }

        $lines = isset($this->replies[$cmd]) ? explode("\n", $this->replies[$cmd]) : [];
        $lines[] = $this->errors[$cmd] ?? 'error id=0 msg=ok';

        return new Reply(array_map(fn (string $line) => new StringHelper($line), $lines), $cmd, $this->getHost(), $throw);
    }

    public function getHost(): Host
    {
        return $this->host ??= new Host($this);
    }
}
