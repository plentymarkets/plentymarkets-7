<?php

namespace PayoneApi\Request\Authorization;

use PayoneApi\Request\AuthorizationRequestAbstract;
use PayoneApi\Request\ClearingTypes;
use PayoneApi\Request\GenericAuthorizationRequest;
use PayoneApi\Request\Parts\RedirectUrls;

/**
 * Class Creditcard
 */
class Creditcard extends AuthorizationRequestAbstract
{
    /**
     * @var string
     */
    protected $clearingtype = ClearingTypes::CREDITCARD;

    /**
     * @var string
     */
    private $pseudocardpan;

    /**
     * @var RedirectUrls
     */
    private $urls;

    /**
     * @var string|null
     */
    private $successurl;

    /**
     * @var string|null
     */
    private $errorurl;

    /**
     * @var string|null
     */
    private $backurl;

    /**
     * @param GenericAuthorizationRequest $authorizationRequest
     * @param RedirectUrls $urls
     * @param $pseudocardPan
     * @param $successurl
     * @param $errorurl
     */
    public function __construct(
        GenericAuthorizationRequest $authorizationRequest,
        RedirectUrls $urls,
        $pseudocardPan,
        $successurl = "",
        $errorurl = ""
    ) {
        $this->authorizationRequest = $authorizationRequest;
        $this->pseudocardpan = $pseudocardPan;
        $this->urls = $urls;
        $this->successurl = $successurl;
        $this->errorurl = $errorurl;
    }

    /**
     * Getter for Pseudocardpan
     *
     * @return string
     */
    public function getPseudocardpan()
    {
        return $this->pseudocardpan;
    }

    /**
     * Getter for Urls
     *
     * @return RedirectUrls
     */
    public function getUrls()
    {
        return $this->urls;
    }

    /**
     * @return string|null
     */
    public function getSuccessurl(): ?string
    {
        return $this->successurl;
    }

    /**
     * @return string|null
     */
    public function getErrorurl(): ?string
    {
        return $this->errorurl;
    }
}
