<?php

namespace PhpEarth\Swoole\Driver\Symfony;

/**
 * Inspired by PHP-PM. Session id is not regenerated for each request since session_destroy()
 * doesn't reset the session_id() nor does it regenerate a new one for a new session,
 * here the default Symfony NativeSessionStorage uses PHP session_regenerated_id()
 * with weaker ids. Here a better session id is generated.
 */
class SessionStorage extends \Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage
{
    public $swooleResponse;

    /**
     * {@inheritdoc}
     */
    public function regenerate($destroy = false, $lifetime = null)
    {
        // NativeSessionStorage uses session_regenerate_id  which also places a
        // setcookie call, so we need to deactivate this, to not have
        // additional Set-Cookie header set.
        ini_set('session.use_cookies', 0);
        if ($isRegenerated = parent::regenerate($destroy, $lifetime)) {
            $params = session_get_cookie_params();
            session_id(\bin2hex(\random_bytes(32)));
            $this->swooleResponse->rawcookie(
                session_name(),
                session_id(),
                $params['lifetime'] ? time() + $params['lifetime'] : null,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        ini_set('session.use_cookies', 1);

        return $isRegenerated;
    }
}
