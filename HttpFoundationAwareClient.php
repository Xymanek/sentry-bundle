<?php
namespace Xymanek\SentryBundle;

use Symfony\Component\HttpFoundation\RequestStack;

class HttpFoundationAwareClient extends \Raven_Client
{
    /**
     * @var RequestStack
     */
    protected $requestStack;

    public function __construct ($options_or_dsn = null, array $options = [])
    {
        if (isset($options['trust_x_forwarded_proto']) && $options['trust_x_forwarded_proto']) {
            throw new \InvalidArgumentException(
                '"trust_x_forwarded_proto" option is not supported as Symfony\'s HttpFoundation component is used instead'
            );
        }

        parent::__construct($options_or_dsn, $options);
    }

    /**
     * @param RequestStack $requestStack
     */
    public function setRequestStack (RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    protected function get_http_data ()
    {
        $request = $this->requestStack->getMasterRequest();

        $result = [
            'method' => $request->getMethod(),
            'url' => $this->get_current_url(),
            'query_string' => $request->getQueryString() ?? '',
            'headers' => $request->headers->all(),
        ];

        // Don't set this as an empty array as PHP will treat it as a numeric array
        // instead of a mapping which goes against the defined Sentry spec
        if ($body = $request->request->all() !== []) {
            $result['data'] = $body;
        }
        if ($cookies = $request->cookies->all() !== []) {
            $result['cookies'] = $cookies;
        }

        return [
            'request' => $result
        ];
    }

    protected function get_user_data ()
    {
        $user = $this->context->user;

        if ($user === null) {
            $request = $this->requestStack->getMasterRequest();

            if ($request === null) {
                return [];
            }

            $user['ip_address'] = $request->getClientIp();
            $session = $request->getSession();

            if ($session && $session->isStarted()) {
                $user['id'] = $session->getName() . '=' . $session->getId();

                if (!empty($data = $session->all())) {
                    $user['session_data'] = $session->all();
                }
            }
        }

        return [
            'user' => $user,
        ];
    }

    protected function get_current_url ()
    {
        $request = $this->requestStack->getMasterRequest();

        if ($request === null) {
            return null;
        }

        return $request->getUri();
    }

    protected function isHttps ()
    {
        $request = $this->requestStack->getMasterRequest();

        if ($request === null) {
            throw new \BadMethodCallException('This method should be only called in HTTP context');
        }

        return $request->isSecure();
    }
}