<?php

namespace PlanetTeamSpeak\TeamSpeak3Framework\Node;

use PlanetTeamSpeak\TeamSpeak3Framework\Exception\AdapterException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\ServerQueryException;

/**
 * @trait VoySpeakHost
 * @brief ServerQuery commands of a VoySpeak server instance which do not exist on a TeamSpeak 3 Server.
 */
trait VoySpeakHost
{
    /**
     * Returns a list of features supported by the VoySpeak server.
     *
     * @return array
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function featureList(): array
    {
        return $this->request("listfeaturesupport")->toAssocArray("name");
    }

    /**
     * Returns a list of properties known by the VoySpeak server including their flags, types and default values.
     *
     * @param string $type one of all, server, channel, client, instance, connection or playlist
     * @return array
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function propertyList(string $type = "all"): array
    {
        return $this->execute("propertylist", ["-" . $type])->toArray();
    }

    /**
     * Returns the ServerQuery accounts of the VoySpeak instance. If $sid is specified, only accounts bound to this virtual
     * server will be returned (0 = accounts which are not bound to a virtual server).
     *
     * @param integer|null $sid
     * @return array
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function queryAccountList(?int $sid = null): array
    {
        return $this->execute("querylist", ["sid" => $sid])->toAssocArray("client_login_name");
    }

    /**
     * Creates a new ServerQuery account and returns its credentials. A random password will be generated when $password is
     * not specified. Without $sid, the account gets bound to the selected virtual server.
     *
     * @param string $username
     * @param string|null $password
     * @param integer|null $sid
     * @param integer|null $cldbid
     * @return array
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function queryAccountCreate(string $username, ?string $password = null, ?int $sid = null, ?int $cldbid = null): array
    {
        return $this->execute("querycreate", ["client_login_name" => $username, "client_login_password" => $password, "sid" => $sid, "cldbid" => $cldbid])->toList();
    }

    /**
     * Deletes a ServerQuery account.
     *
     * @param string $username
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function queryAccountDelete(string $username): void
    {
        $this->execute("querydelete", ["client_login_name" => $username]);
    }

    /**
     * Changes the login name of a ServerQuery account.
     *
     * @param string $username
     * @param string $newUsername
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function queryAccountRename(string $username, string $newUsername): void
    {
        $this->execute("queryrename", ["client_login_name" => $username, "client_new_login_name" => $newUsername]);
    }

    /**
     * Changes the password of a ServerQuery account and returns the new credentials. A random password will be generated
     * when $password is not specified.
     *
     * @param string $username
     * @param string|null $password
     * @return array
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function queryAccountChangePassword(string $username, ?string $password = null): array
    {
        return $this->execute("querychangepassword", ["client_login_name" => $username, "client_login_password" => $password])->toList();
    }

    /**
     * Returns entries of the instance action log. VoySpeak replaced the content of logview by this command. Entries are
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
        return $this->execute("logquery", ["instance" => true, "limit" => $limit, "begin" => $begin, "end" => $end, "groups" => is_array($groups) ? implode(",", $groups) : $groups])->toArray();
    }
}
