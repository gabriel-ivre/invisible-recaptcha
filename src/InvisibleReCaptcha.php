<?php

namespace AlbertCht\InvisibleReCaptcha;

use Symfony\Component\HttpFoundation\Request;
use GuzzleHttp\Client;

class InvisibleReCaptcha
{
    const API_URI = 'https://www.google.com/recaptcha/api.js';
    const VERIFY_URI = 'https://www.google.com/recaptcha/api/siteverify';

    /**
     * The reCaptcha sitekey key.
     *
     * @var string
     */
    protected $siteKey;

    /**
     * The reCaptcha secret key.
     *
     * @var string
     */
    protected $secretKey;

    /**
     * The config to determine if hide the badge.
     *
     * @var boolean
     */
    protected $hideBadge;

    /**
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * InvisibleReCaptcha.
     *
     * @param string $secretKey
     * @param string $siteKey
     */
    public function __construct($siteKey, $secretKey, $hideBadge = false)
    {
        $this->siteKey = $siteKey;
        $this->secretKey = $secretKey;
        $this->hideBadge = $hideBadge;
        $this->client = new Client(['timeout' => 5]);
    }

    /**
     * Get reCaptcha js by optional language param.
     *
     * @param string $lang
     *
     * @return string
     */
    public function getJs($lang = null)
    {
        return $lang ? self::API_URI . '?hl=' . $lang : self::API_URI;
    }
    /**
     * Render HTML reCaptcha by formId and submitId.
     *
     * @param string $formId
     * @param string $submitId
     * @param string $lang
     *
     * @return string
     */
    public function render($lang = null)
    {
        $html = '<div id="_g-recaptcha"></div>' . "\n";
        if ($this->hideBadge) {
            $html .= '<style>.grecaptcha-badge{display:none;!important}</style>' . "\n";
        }
        $html .= '<div class="g-recaptcha" data-sitekey="' . $this->siteKey .'" ';
        $html .= 'data-bind="send-btn" data-callback="_submitForm"></div>';
        $html .= '<script src="' . $this->getJs($lang) . '" async defer></script>' . "\n";
        $html .= '<script>var _submitForm,_captchaForm, _captchaSubmit;</script>';
        $html .= '<script>window.onload = function(){';
        $html .= ' _captchaForm=document.querySelector("#_g-recaptcha").closest("form");';
        $html .= " _captchaSubmit=_captchaForm.querySelector('[type=submit]');";
        $html .= '_submitForm=function(){_captchaForm.submit();}}</script>' . "\n";

        return $html;
    }

    /**
     * Verify invisible reCaptcha response.
     *
     * @param string $response
     * @param string $clientIp
     *
     * @return bool
     */
    public function verifyResponse($response, $clientIp)
    {
        if (empty($response)) {
            return false;
        }

        $response = $this->sendVerifyRequest([
            'secret' => $this->secretKey,
            'remoteip' => $clientIp,
            'response' => $response
        ]);

        return isset($response['success']) && $response['success'] === true;
    }

    /**
     * Verify invisible reCaptcha response by Symfony Request.
     *
     * @param Request $request
     *
     * @return bool
     */
    public function verifyRequest(Request $request)
    {
        return $this->verifyResponse(
            $request->get('g-recaptcha-response'),
            $request->getClientIp()
        );
    }

    /**
     * Send verify request.
     *
     * @param array $query
     *
     * @return array
     */
    protected function sendVerifyRequest(array $query = [])
    {
        $response = $this->client->post(self::VERIFY_URI, [
            'form_params' => $query,
        ]);

        return json_decode($response->getBody(), true);
    }

    
}
