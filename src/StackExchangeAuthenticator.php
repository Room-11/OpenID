<?php declare(strict_types=1);

namespace Room11\OpenId;

use Amp\Artax\FormBody;
use Amp\Artax\HttpClient;
use Amp\Artax\Request as HttpRequest;
use Amp\Artax\Response as HttpResponse;
use function Room11\DOMUtils\domdocument_load_html;
use Room11\DOMUtils\LibXMLFatalErrorException;

class StackExchangeAuthenticator implements Authenticator
{
    private $httpClient;
    private $uriFactory;

    public function __construct(HttpClient $httpClient, UriFactory $uriFactory)
    {
        $this->httpClient = $httpClient;
        $this->uriFactory = $uriFactory;
    }

    public function logIn(string $url, Credentials $credentials)
    {
        /** @var HttpResponse $response */
        $response = yield $this->httpClient->request($url);

        $startUrl = $response->getRequest()->getUri();

        try {
            $doc = domdocument_load_html($response->getBody());
        } catch (LibXMLFatalErrorException $e) {
            throw new FailedAuthenticationException('Parsing response body as HTML failed', $e->getCode(), $e);
        }

        $xpath = new \DOMXPath($doc);

        $loginForm = $this->getLoginForm($doc);
        $submitURL = $this->getSubmitUrl($loginForm, $response->getRequest()->getUri());
        $fkey = $this->getFKey($xpath, $loginForm);

        $body = (new FormBody)
            ->addField("email", (string)$credentials->getEmailAddress())
            ->addField("password", (string)$credentials->getPassword())
            ->addField("fkey", $fkey)
            ->addField("ssrc", "")
            ->addField("oauth_version", "")
            ->addField("oauth_server", "")
            ->addField("openid_username", "")
            ->addField("openid_identifier", "");

        $request = (new HttpRequest)
            ->setUri($submitURL)
            ->setMethod("POST")
            ->setBody($body);

        $response = yield $this->httpClient->request($request);

        if ($response->getRequest()->getUri() === $startUrl) {
            throw new FailedAuthenticationException('Authentication failed using the supplied credentials');
        }

        return $response;
    }

    private function getLoginForm(\DOMDocument $doc)
    {
        $formNode = $doc->getElementById('login-form');

        if ($formNode === null || strtolower($formNode->tagName) !== 'form') {
            throw new InvalidLoginUrlException('Could not find login form on login page');
        }

        return $formNode;
    }

    private function getSubmitUrl(\DOMElement $formNode, string $baseURL): string
    {
        return $formNode->hasAttribute('action')
            ? (string)$this->uriFactory->build($baseURL)->resolve($formNode->getAttribute('action'))
            : $baseURL;
    }

    private function getFKey(\DOMXPath $xpath, \DOMElement $formNode): string
    {
        /** @var \DOMElement $node */

        $nodes = $xpath->query(".//input[@name='fkey']", $formNode);
        if ($nodes->length < 1) {
            throw new InvalidLoginUrlException('Could not find fkey for login form');
        }

        $node = $nodes->item(0);
        return $node->getAttribute('value');
    }
}
