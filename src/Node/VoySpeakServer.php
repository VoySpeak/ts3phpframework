<?php

namespace PlanetTeamSpeak\TeamSpeak3Framework\Node;

use PlanetTeamSpeak\TeamSpeak3Framework\Adapter\ServerQuery\Reply;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\AdapterException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\ServerQueryException;
use PlanetTeamSpeak\TeamSpeak3Framework\TeamSpeak3;

/**
 * @trait VoySpeakServer
 * @brief ServerQuery commands of a VoySpeak virtual server which do not exist on a TeamSpeak 3 Server.
 */
trait VoySpeakServer
{
    /**
     * Returns the music bots of the virtual server indexed by their bot ID (client database ID).
     *
     * @return array
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function musicBotList(): array
    {
        $bots = [];

        foreach ($this->request("clientlist -uid")->toAssocArray("client_database_id") as $botId => $bot) {
            if ($bot["client_type"] == TeamSpeak3::CLIENT_TYPE_MUSIC) {
                $bots[$botId] = $bot;
            }
        }

        return $bots;
    }

    /**
     * Creates a new music bot and returns its ID. Without $cid the bot joins the current channel, without $type the server
     * chooses the most durable type you are allowed to create.
     *
     * @param integer|null $cid
     * @param integer|null $type
     * @return integer
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function musicBotCreate(?int $cid = null, ?int $type = null): int
    {
        $bot = $this->execute("musicbotcreate", ["cid" => $cid, "type" => $type])->toList();

        $this->clientListReset();

        return $bot["bot_id"];
    }

    /**
     * Deletes a music bot.
     *
     * @param integer $botId
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function musicBotDelete(int $botId): void
    {
        $this->execute("musicbotdelete", ["bot_id" => $botId]);
        $this->clientListReset();
    }

    /**
     * Returns the state of the player of a music bot.
     *
     * @param integer $botId
     * @return array
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function musicBotPlayerInfo(int $botId): array
    {
        return $this->execute("musicbotplayerinfo", ["bot_id" => $botId])->toList();
    }

    /**
     * Performs an action on the player of a music bot. $units is the duration in milliseconds and only used by
     * TeamSpeak3::MUSIC_ACTION_FORWARD and TeamSpeak3::MUSIC_ACTION_REWIND.
     *
     * @param integer $botId
     * @param integer $action
     * @param integer|null $units
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function musicBotPlayerAction(int $botId, int $action, ?int $units = null): void
    {
        $this->execute("musicbotplayeraction", ["bot_id" => $botId, "action" => $action, "units" => $units]);
    }

    /**
     * Stops the player of a music bot.
     *
     * @param integer $botId
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function musicBotStop(int $botId): void
    {
        $this->musicBotPlayerAction($botId, TeamSpeak3::MUSIC_ACTION_STOP);
    }

    /**
     * Starts the player of a music bot.
     *
     * @param integer $botId
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function musicBotPlay(int $botId): void
    {
        $this->musicBotPlayerAction($botId, TeamSpeak3::MUSIC_ACTION_PLAY);
    }

    /**
     * Pauses the player of a music bot.
     *
     * @param integer $botId
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function musicBotPause(int $botId): void
    {
        $this->musicBotPlayerAction($botId, TeamSpeak3::MUSIC_ACTION_PAUSE);
    }

    /**
     * Plays the next song of a music bot.
     *
     * @param integer $botId
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function musicBotNext(int $botId): void
    {
        $this->musicBotPlayerAction($botId, TeamSpeak3::MUSIC_ACTION_NEXT);
    }

    /**
     * Plays the previous song of a music bot.
     *
     * @param integer $botId
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function musicBotPrevious(int $botId): void
    {
        $this->musicBotPlayerAction($botId, TeamSpeak3::MUSIC_ACTION_PREVIOUS);
    }

    /**
     * Seeks forward within the current song of a music bot.
     *
     * @param integer $botId
     * @param integer $milliseconds
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function musicBotForward(int $botId, int $milliseconds): void
    {
        $this->musicBotPlayerAction($botId, TeamSpeak3::MUSIC_ACTION_FORWARD, $milliseconds);
    }

    /**
     * Seeks backward within the current song of a music bot.
     *
     * @param integer $botId
     * @param integer $milliseconds
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function musicBotRewind(int $botId, int $milliseconds): void
    {
        $this->musicBotPlayerAction($botId, TeamSpeak3::MUSIC_ACTION_REWIND, $milliseconds);
    }

    /**
     * Assigns a playlist to a music bot (0 = remove the current playlist).
     *
     * @param integer $botId
     * @param integer $playlistId
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function musicBotPlaylistAssign(int $botId, int $playlistId): void
    {
        $this->execute("musicbotplaylistassign", ["bot_id" => $botId, "playlist_id" => $playlistId]);
    }

    /**
     * Subscribes to the events of a music bot (0 = remove the current subscription).
     *
     * @param integer $botId
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function musicBotSubscribe(int $botId): void
    {
        $this->execute("musicbotsetsubscription", ["bot_id" => $botId]);
    }

    /**
     * Returns the playlists of the virtual server.
     *
     * @return array
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function playlistList(): array
    {
        return $this->request("playlistlist")->toAssocArray("playlist_id");
    }

    /**
     * Creates a new playlist and returns its ID.
     *
     * @param array $properties
     * @return integer
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function playlistCreate(array $properties = []): int
    {
        $playlist = $this->request("playlistcreate")->toList();

        if (count($properties)) {
            $this->playlistModify($playlist["playlist_id"], $properties);
        }

        return $playlist["playlist_id"];
    }

    /**
     * Deletes a playlist.
     *
     * @param integer $playlistId
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function playlistDelete(int $playlistId): void
    {
        $this->execute("playlistdelete", ["playlist_id" => $playlistId]);
    }

    /**
     * Returns the properties of a playlist.
     *
     * @param integer $playlistId
     * @return array
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function playlistInfo(int $playlistId): array
    {
        return $this->execute("playlistinfo", ["playlist_id" => $playlistId])->toList();
    }

    /**
     * Changes the properties of a playlist.
     *
     * @param integer $playlistId
     * @param array $properties
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function playlistModify(int $playlistId, array $properties): void
    {
        $this->execute("playlistedit", ["playlist_id" => $playlistId] + $properties);
    }

    /**
     * Subscribes to the events of a playlist (0 = remove the current subscription).
     *
     * @param integer $playlistId
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function playlistSubscribe(int $playlistId): void
    {
        $this->execute("playlistsetsubscription", ["playlist_id" => $playlistId]);
    }

    /**
     * Returns the permissions of a playlist.
     *
     * @param integer $playlistId
     * @param boolean $permsid
     * @return array
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function playlistPermList(int $playlistId, bool $permsid = false): array
    {
        return $this->execute("playlistpermlist", ["playlist_id" => $playlistId, $permsid ? "-permsid" : null])
            ->toAssocArray($permsid ? "permsid" : "permid");
    }

    /**
     * Adds a set of permissions to a playlist. Multiple permissions can be added by using arrays.
     *
     * @param integer $playlistId
     * @param integer|string|array $permid
     * @param integer|integer[] $permvalue
     * @param boolean|boolean[] $permnegated
     * @param boolean|boolean[] $permskip
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function playlistPermAssign(int $playlistId, int|string|array $permid, int|array $permvalue, bool|array $permnegated = false, bool|array $permskip = false): void
    {
        $this->execute("playlistaddperm", ["playlist_id" => $playlistId] + $this->permParams($permid, $permvalue, $permnegated, $permskip));
    }

    /**
     * Removes a set of permissions from a playlist.
     *
     * @param integer $playlistId
     * @param integer|string|array $permid
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function playlistPermRemove(int $playlistId, int|string|array $permid): void
    {
        $this->execute("playlistdelperm", ["playlist_id" => $playlistId] + $this->permParams($permid));
    }

    /**
     * Returns the database IDs of all clients having individual permissions on a playlist.
     *
     * @param integer $playlistId
     * @return array
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function playlistClientList(int $playlistId): array
    {
        return array_column($this->execute("playlistclientlist", ["playlist_id" => $playlistId])->toArray(), "cldbid");
    }

    /**
     * Returns the client permissions of a playlist. Without $cldbid, the permissions of all clients will be returned.
     *
     * @param integer $playlistId
     * @param integer|null $cldbid
     * @param boolean $permsid
     * @return array
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function playlistClientPermList(int $playlistId, ?int $cldbid = null, bool $permsid = false): array
    {
        return $this->execute("playlistclientpermlist", ["playlist_id" => $playlistId, "cldbid" => $cldbid, $permsid ? "-permsid" : null])->toArray();
    }

    /**
     * Adds a set of permissions to a client on a playlist. Multiple permissions can be added by using arrays.
     *
     * @param integer $playlistId
     * @param integer $cldbid
     * @param integer|string|array $permid
     * @param integer|integer[] $permvalue
     * @param boolean|boolean[] $permnegated
     * @param boolean|boolean[] $permskip
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function playlistClientPermAssign(int $playlistId, int $cldbid, int|string|array $permid, int|array $permvalue, bool|array $permnegated = false, bool|array $permskip = false): void
    {
        $this->execute("playlistclientaddperm", ["playlist_id" => $playlistId, "cldbid" => $cldbid] + $this->permParams($permid, $permvalue, $permnegated, $permskip));
    }

    /**
     * Removes a set of permissions from a client on a playlist.
     *
     * @param integer $playlistId
     * @param integer $cldbid
     * @param integer|string|array $permid
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function playlistClientPermRemove(int $playlistId, int $cldbid, int|string|array $permid): void
    {
        $this->execute("playlistclientdelperm", ["playlist_id" => $playlistId, "cldbid" => $cldbid] + $this->permParams($permid));
    }

    /**
     * Returns the songs of a playlist.
     *
     * @param integer $playlistId
     * @param boolean $metadata extract the metadata into individual fields
     * @return array
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function playlistSongList(int $playlistId, bool $metadata = false): array
    {
        return $this->execute("playlistsonglist", ["playlist_id" => $playlistId, $metadata ? "-extract-metadata" : null])->toAssocArray("song_id");
    }

    /**
     * Adds a song to a playlist. Without $previous, the song will be added to the end of the playlist.
     *
     * @param integer $playlistId
     * @param string $url
     * @param string|null $type one of yt, ffmpeg or channel (NULL = detect)
     * @param integer|null $previous
     * @param string|null $cpw channel password for channel files
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function playlistSongAdd(int $playlistId, string $url, ?string $type = null, ?int $previous = null, ?string $cpw = null): void
    {
        $this->execute("playlistsongadd", ["playlist_id" => $playlistId, "url" => $url, "type" => $type, "previous" => $previous, "cpw" => $cpw]);
    }

    /**
     * Moves a song behind another song of the same playlist (0 = move to the top).
     *
     * @param integer $playlistId
     * @param integer $songId
     * @param integer $previous
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function playlistSongMove(int $playlistId, int $songId, int $previous = 0): void
    {
        $this->execute("playlistsongreorder", ["playlist_id" => $playlistId, "song_id" => $songId, "song_previous_song_id" => $previous]);
    }

    /**
     * Removes a song from a playlist.
     *
     * @param integer $playlistId
     * @param integer $songId
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function playlistSongRemove(int $playlistId, int $songId): void
    {
        $this->execute("playlistsongremove", ["playlist_id" => $playlistId, "song_id" => $songId]);
    }

    /**
     * Sets the current song of a playlist.
     *
     * @param integer $playlistId
     * @param integer $songId
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function playlistSongSetCurrent(int $playlistId, int $songId): void
    {
        $this->execute("playlistsongsetcurrent", ["playlist_id" => $playlistId, "song_id" => $songId]);
    }

    /**
     * Returns the state of one or more channel conversations.
     *
     * @param integer|integer[] $cid
     * @param string|null $cpw
     * @return array
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function conversationFetch(int|array $cid, ?string $cpw = null): array
    {
        $cid = array_values((array)$cid);

        return $this->execute("conversationfetch", ["cid" => $cid, "cpw" => $cpw === null ? null : array_fill(0, count($cid), $cpw)])->toAssocArray("cid");
    }

    /**
     * Returns the message history of a channel conversation. Messages are returned from $begin (newest, in milliseconds)
     * back to $end (oldest, in milliseconds). The server returns 25 messages by default and up to 100 at once.
     *
     * The result contains the keys cid, flag_volatile, more_data and messages.
     *
     * @param integer $cid
     * @param integer|null $begin
     * @param integer|null $end
     * @param integer|null $limit
     * @param string|null $cpw
     * @return array
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function conversationHistory(int $cid, ?int $begin = null, ?int $end = null, ?int $limit = null, ?string $cpw = null): array
    {
        $cmd = $this->prepare("conversationhistory -merge", ["cid" => $cid, "timestamp_begin" => $begin, "timestamp_end" => $end, "message_count" => $limit, "cpw" => $cpw]);
        $reply = $this->request($cmd, false);
        $error = $reply->getErrorProperty("id", 0x00);

        /* ERROR_conversation_more_data */
        if ($error != 0x00 && $error != 0x2201) {
            throw new ServerQueryException($reply->getErrorProperty("msg"), $error);
        }

        $history = ["cid" => $cid, "flag_volatile" => 0, "more_data" => $error == 0x2201, "messages" => []];

        foreach ($reply->toArray() as $row) {
            $history["flag_volatile"] = $row["flag_volatile"] ?? $history["flag_volatile"];

            if (isset($row["timestamp"])) {
                unset($row["cid"], $row["flag_volatile"]);

                $history["messages"][] = $row;
            }
        }

        return $history;
    }

    /**
     * Deletes messages of a client from a channel conversation, starting with the newest one between $begin (oldest,
     * in milliseconds) and $end (newest, in milliseconds). Up to 100 messages can be deleted at once.
     *
     * @param integer $cid
     * @param integer $cldbid
     * @param integer $begin
     * @param integer $end
     * @param integer $limit
     * @param string|null $cpw
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function conversationMessageDelete(int $cid, int $cldbid, int $begin, int $end, int $limit = 1, ?string $cpw = null): void
    {
        $this->execute("conversationmessagedelete", ["cid" => $cid, "cldbid" => $cldbid, "timestamp_begin" => $begin, "timestamp_end" => $end, "limit" => $limit, "cpw" => $cpw]);
    }

    /**
     * Subscribes to (or unsubscribes from) one or more channel conversations.
     *
     * @param integer|integer[] $cid
     * @param boolean $subscribe
     * @return array
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function conversationSubscribe(int|array $cid, bool $subscribe = true): array
    {
        $cid = array_values((array)$cid);

        return $this->execute("conversationsetsubscription", ["cid" => $cid, "subscribe" => array_fill(0, count($cid), $subscribe)])->toAssocArray("cid");
    }

    /**
     * Returns the actions of a privilege key (token), which can be specified by its ID or key.
     *
     * @param integer|string $token
     * @return array
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function privilegeKeyActionList(int|string $token): array
    {
        return $this->execute("tokenactionlist", [is_int($token) ? "token_id" : "token" => $token])->toArray();
    }

    /**
     * Creates a privilege key (token) with multiple actions. Each action is an array containing the keys type (one of
     * TeamSpeak3::TOKEN_ACTION_*), id1, id2 and text.
     *
     * @param array $actions
     * @param string|null $description
     * @param integer|null $maxUses 0 = unlimited
     * @param integer|null $expires unix timestamp, 0 = never
     * @return array
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function privilegeKeyCreateActions(array $actions, ?string $description = null, ?int $maxUses = null, ?int $expires = null): array
    {
        $token = $this->execute("tokenadd", $this->tokenParams($actions, $description, $maxUses, $expires))->toArray();

        return $token[0];
    }

    /**
     * Modifies a privilege key (token), which can be specified by its ID or key. New actions use the same format as in
     * privilegeKeyCreateActions(). To delete an action, pass an array containing only its id.
     *
     * @param integer|string $token
     * @param array $actions
     * @param string|null $description
     * @param integer|null $maxUses
     * @param integer|null $expires
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function privilegeKeyModify(int|string $token, array $actions = [], ?string $description = null, ?int $maxUses = null, ?int $expires = null): void
    {
        $this->execute("tokenedit", [is_int($token) ? "token_id" : "token" => $token] + $this->tokenParams($actions, $description, $maxUses, $expires));
    }

    /**
     * Modifies an existing ban. Available keys are ip, name, uid, hwid, reason and time.
     *
     * @param integer $banid
     * @param array $properties
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function banModify(int $banid, array $properties): void
    {
        $this->execute("banedit", ["banid" => $banid] + $properties);
    }

    /**
     * Returns the clients which triggered a ban.
     *
     * @param integer $banid
     * @param integer|null $offset
     * @param integer|null $limit
     * @return array
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function banTriggerList(int $banid, ?int $offset = null, ?int $limit = null): array
    {
        return $this->execute("bantriggerlist", ["banid" => $banid, "offset" => $offset, "limit" => $limit])->toArray();
    }

    /**
     * Returns the channels with a name matching the pattern.
     *
     * @param string $pattern
     * @return array
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function channelFind(string $pattern): array
    {
        return $this->execute("channelfind", ["pattern" => $pattern])->toAssocArray("cid");
    }

    /**
     * Returns the description of a channel.
     *
     * @param integer $cid
     * @return string
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function channelGetDescription(int $cid): string
    {
        return (string)($this->execute("channelgetdescription", ["cid" => $cid])->toList()["channel_description"] ?? "");
    }

    /**
     * Subscribes to one or more channels.
     *
     * @param integer|integer[] $cid
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function channelSubscribe(int|array $cid): void
    {
        $this->execute("channelsubscribe", ["cid" => (array)$cid]);
    }

    /**
     * Subscribes to all channels.
     *
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function channelSubscribeAll(): void
    {
        $this->request("channelsubscribeall");
    }

    /**
     * Unsubscribes from one or more channels.
     *
     * @param integer|integer[] $cid
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function channelUnsubscribe(int|array $cid): void
    {
        // the server only handles the first channel of a bulk
        foreach ((array)$cid as $id) {
            $this->execute("channelunsubscribe", ["cid" => $id]);
        }
    }

    /**
     * Unsubscribes from all channels.
     *
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function channelUnsubscribeAll(): void
    {
        $this->request("channelunsubscribeall");
    }

    /**
     * Returns TRUE if the plain text password of the virtual server is correct.
     *
     * @param string $password
     * @return boolean
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function serverPasswordVerify(string $password): bool
    {
        try {
            $this->execute("verifyserverpassword", ["password" => $password]);
        } catch (ServerQueryException $e) {
            /* ERROR_server_invalid_password */
            if ($e->getCode() != 0x404) {
                throw $e;
            }

            return false;
        }

        return true;
    }

    /**
     * Returns TRUE if the plain text password of the channel is correct.
     *
     * @param integer $cid
     * @param string $password
     * @return boolean
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function channelPasswordVerify(int $cid, string $password): bool
    {
        try {
            $this->execute("verifychannelpassword", ["cid" => $cid, "password" => $password]);
        } catch (ServerQueryException $e) {
            /* ERROR_server_invalid_password */
            if ($e->getCode() != 0x404) {
                throw $e;
            }

            return false;
        }

        return true;
    }

    /**
     * Returns the database IDs of one or more clients indexed by their unique identifier.
     *
     * @param string|string[] $cluid
     * @return array
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function clientGetDbidByUid(string|array $cluid): array
    {
        $dbids = [];

        foreach ($this->execute("clientgetdbidfromuid", ["cluid" => (array)$cluid])->toArray() as $client) {
            $dbids[(string)$client["cluid"]] = $client["cldbid"];
        }

        return $dbids;
    }

    /**
     * Returns the unique identifier, database ID and name of one or more online clients.
     *
     * @param integer|integer[] $clid
     * @return array
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function clientGetUidById(int|array $clid): array
    {
        return $this->execute("clientgetuidfromclid", ["clid" => (array)$clid])->toAssocArray("clid");
    }

    /**
     * Returns connection information of a client. The result may be incomplete if the server has to request it from the
     * client first; the missing part will be sent as a connectioninfo event afterwards.
     *
     * @param integer $clid
     * @return array
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function clientConnectionInfo(int $clid): array
    {
        return $this->fetchNotifyData($this->execute("getconnectioninfo", ["clid" => $clid]), "connectioninfo");
    }

    /**
     * Mutes a client.
     *
     * @param integer $clid
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function clientMute(int $clid): void
    {
        $this->execute("clientmute", ["clid" => $clid]);
    }

    /**
     * Unmutes a client.
     *
     * @param integer $clid
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function clientUnmute(int $clid): void
    {
        $this->execute("clientunmute", ["clid" => $clid]);
    }

    /**
     * Tells a client that you are typing a private message.
     *
     * @param integer $clid
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function clientChatComposing(int $clid): void
    {
        $this->execute("clientchatcomposing", ["clid" => $clid]);
    }

    /**
     * Tells a client that you have closed the private conversation.
     *
     * @param integer $clid
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function clientChatClosed(int $clid): void
    {
        $this->execute("clientchatclosed", ["clid" => $clid]);
    }

    /**
     * Changes the read flag of an offline message.
     *
     * @param integer $msgid
     * @param boolean $read
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function messageUpdateFlag(int $msgid, bool $read = true): void
    {
        $this->execute("messageupdateflag", ["msgid" => $msgid, "flag" => $read]);
    }

    /**
     * Returns the registered (or all available) notification events.
     *
     * @param boolean $all
     * @return array
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function notifyList(bool $all = false): array
    {
        return $this->execute("servernotifylist", [$all ? "-all" : null])->toArray();
    }

    /**
     * Joins the default channel of the virtual server with the ServerQuery client.
     *
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function join(): void
    {
        $this->request("join");
    }

    /**
     * Leaves the current channel of the virtual server with the ServerQuery client.
     *
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function leave(): void
    {
        $this->request("left");
    }

    /**
     * Returns entries of the virtual server action log. VoySpeak replaced the content of logview by this command. Entries are
     * returned from $begin (newest, in milliseconds) back to $end (oldest, in milliseconds).
     *
     * @param integer|null $limit
     * @param integer|null $begin
     * @param integer|null $end
     * @param string|array|null $groups any of custom, server, channel, permission, client, files and query
     * @return array
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function logQuery(?int $limit = null, ?int $begin = null, ?int $end = null, string|array|null $groups = null): array
    {
        return $this->execute("logquery", ["limit" => $limit, "begin" => $begin, "end" => $end, "groups" => is_array($groups) ? implode(",", $groups) : $groups])->toArray();
    }

    /**
     * Returns the data of a notification which has been received together with a reply. VoySpeak answers some commands
     * by using a notification instead of a regular reply.
     *
     * @param Reply $reply
     * @param string $type
     * @return array
     */
    protected function fetchNotifyData(Reply $reply, string $type): array
    {
        $data = $reply->toList();

        foreach ($reply->getNotifyEvents() as $event) {
            if ((string)$event->getType() === $type) {
                $data = array_merge($data, $event->getData());
            }
        }

        return $data;
    }

    /**
     * Returns the parameters for one or more permissions.
     *
     * @param integer|string|array $permid
     * @param integer|array|null $permvalue
     * @param boolean|array|null $permnegated
     * @param boolean|array|null $permskip
     * @return array
     */
    protected function permParams(int|string|array $permid, int|array|null $permvalue = null, bool|array|null $permnegated = null, bool|array|null $permskip = null): array
    {
        $permid = array_values((array)$permid);
        $params = [is_numeric($permid[0] ?? null) ? "permid" : "permsid" => $permid];

        foreach (["permvalue" => $permvalue, "permnegated" => $permnegated, "permskip" => $permskip] as $ident => $value) {
            if ($value !== null) {
                $params[$ident] = is_array($value) ? array_values($value) : array_fill(0, count($permid), $value);
            }
        }

        return $params;
    }

    /**
     * Returns the parameters for a privilege key (token).
     *
     * @param array $actions
     * @param string|null $description
     * @param integer|null $maxUses
     * @param integer|null $expires
     * @return array
     */
    protected function tokenParams(array $actions, ?string $description, ?int $maxUses, ?int $expires): array
    {
        $params = ["token_description" => $description, "token_max_uses" => $maxUses, "token_expired" => $expires];

        foreach (["id", "type", "id1", "id2", "text"] as $key) {
            $params["action_" . $key] = array_map(fn (array $action) => $action[$key] ?? null, array_values($actions));
        }

        return $params;
    }
}
