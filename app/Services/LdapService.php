<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Talks to the Synology "LDAP Server" package (POSIX/NIS schema:
 * inetOrgPerson + posixAccount for users, posixGroup + memberUid for groups).
 * Same package runs on the auth VM and is read by the NAS's LDAP client,
 * so no schema translation is needed on either side.
 */
class LdapService
{
    private string $host;
    private int $port;
    private string $bindDn;
    private string $bindPassword;
    private string $usersDn;
    private string $groupsDn;
    private int $defaultGid;

    public function __construct()
    {
        $this->host         = Setting::get('ldap_host', '');
        $this->port         = (int) Setting::get('ldap_port', '389');
        $this->bindDn       = Setting::get('ldap_bind_dn', '');
        $this->bindPassword = Setting::get('ldap_bind_password', '');
        $this->usersDn      = Setting::get('ldap_users_dn', '');
        $this->groupsDn     = Setting::get('ldap_groups_dn', '');
        $this->defaultGid   = (int) Setting::get('ldap_default_gid', '100');
    }

    /** @return resource|\LDAP\Connection */
    private function connect()
    {
        if (! extension_loaded('ldap')) {
            throw new RuntimeException('Extension PHP "ldap" non installée sur le serveur.');
        }
        if (! $this->host || ! $this->bindDn) {
            throw new RuntimeException('Configuration LDAP incomplète.');
        }

        $conn = ldap_connect($this->host, $this->port);
        if (! $conn) {
            throw new RuntimeException("Impossible de joindre le serveur LDAP {$this->host}:{$this->port}");
        }

        ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);

        if (! @ldap_bind($conn, $this->bindDn, $this->bindPassword)) {
            throw new RuntimeException('Échec du bind LDAP : ' . ldap_error($conn));
        }

        return $conn;
    }

    public function testConnection(): bool
    {
        $this->connect();
        return true;
    }

    public function userExists(string $username): bool
    {
        $conn   = $this->connect();
        $result = @ldap_search($conn, $this->usersDn, "(uid={$this->escape($username)})", ['uid']);
        if ($result === false) {
            return false;
        }
        return ldap_count_entries($conn, $result) > 0;
    }

    /**
     * Create the LDAP account for a client if it doesn't already exist,
     * then make sure it belongs to the given group.
     */
    public function provisionUser(string $username, string $password, string $email, string $fullName, ?string $group): string
    {
        $conn = $this->connect();
        $dn   = "uid={$this->escape($username)},{$this->usersDn}";

        if (! $this->userExists($username)) {
            [$firstName, $lastName] = $this->splitName($fullName, $username);
            $uidNumber = $this->nextUidNumber();

            $entry = [
                'objectClass'     => ['top', 'inetOrgPerson', 'posixAccount', 'shadowAccount'],
                'uid'             => $username,
                'cn'              => $fullName ?: $username,
                'sn'              => $lastName ?: $username,
                'givenName'       => $firstName ?: $username,
                'mail'            => $email,
                'uidNumber'       => (string) $uidNumber,
                'gidNumber'       => (string) $this->defaultGid,
                'homeDirectory'   => "/home/{$username}",
                'loginShell'      => '/bin/false',
                'userPassword'    => $this->hashPassword($password),
            ];

            if (! @ldap_add($conn, $dn, $entry)) {
                throw new RuntimeException("Création du compte LDAP {$username} échouée : " . ldap_error($conn));
            }
        }

        if ($group) {
            $this->addUserToGroup($username, $group);
        }

        return $dn;
    }

    public function addUserToGroup(string $username, string $group): void
    {
        $conn = $this->connect();
        $groupDn = "cn={$this->escape($group)},{$this->groupsDn}";

        if ($this->isGroupMember($username, $group)) {
            return;
        }

        if (! @ldap_mod_add($conn, $groupDn, ['memberUid' => [$username]])) {
            throw new RuntimeException("Ajout de {$username} au groupe {$group} échoué : " . ldap_error($conn));
        }
    }

    public function removeUserFromGroup(string $username, string $group): void
    {
        $conn = $this->connect();
        $groupDn = "cn={$this->escape($group)},{$this->groupsDn}";

        if (! $this->isGroupMember($username, $group)) {
            return;
        }

        if (! @ldap_mod_del($conn, $groupDn, ['memberUid' => [$username]])) {
            Log::warning("Retrait de {$username} du groupe LDAP {$group} échoué : " . ldap_error($conn));
        }
    }

    /** Move a user from one plan group to another (upgrade/downgrade). */
    public function switchGroup(string $username, ?string $fromGroup, ?string $toGroup): void
    {
        if ($fromGroup && $fromGroup !== $toGroup) {
            $this->removeUserFromGroup($username, $fromGroup);
        }
        if ($toGroup) {
            $this->addUserToGroup($username, $toGroup);
        }
    }

    private function isGroupMember(string $username, string $group): bool
    {
        $conn    = $this->connect();
        $groupDn = "cn={$this->escape($group)},{$this->groupsDn}";
        $result  = @ldap_read($conn, $groupDn, '(objectClass=posixGroup)', ['memberUid']);

        if ($result === false) {
            return false;
        }

        $entries = ldap_get_entries($conn, $result);
        $members = $entries[0]['memberuid'] ?? [];

        return in_array($username, $members, true);
    }

    private function nextUidNumber(): int
    {
        $next = (int) Setting::get('ldap_next_uid', '10000');
        Setting::set('ldap_next_uid', (string) ($next + 1), 'ldap');
        return $next;
    }

    private function hashPassword(string $password): string
    {
        $salt = random_bytes(4);
        $hash = base64_encode(sha1($password . $salt, true) . $salt);
        return '{SSHA}' . $hash;
    }

    private function splitName(string $fullName, string $fallback): array
    {
        $parts = preg_split('/\s+/', trim($fullName), 2);
        return [$parts[0] ?? $fallback, $parts[1] ?? ''];
    }

    private function escape(string $value): string
    {
        return ldap_escape($value, '', LDAP_ESCAPE_DN);
    }
}
