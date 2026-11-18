<?php

namespace designworkshop\searchurlencoder\controllers;

use designworkshop\searchurlencoder\SearchUrlEncoder;
use craft\web\Controller;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class SearchController extends Controller
{
    protected array|bool|int $allowAnonymous = ['render', 'submit'];

    /**
     * POST handler for search forms: redirects to the encoded URL.
     * Optional hidden field `profile` picks which search page to go to.
     */
    public function actionSubmit(): Response
    {
        $this->requirePostRequest();
        $request = $this->request;

        $plugin = SearchUrlEncoder::getInstance();
        $profile = (string)$request->getBodyParam('profile', 'default');

        if (!isset($plugin->getProfiles()[$profile])) {
            throw new BadRequestHttpException('Unknown search profile.');
        }

        $query = (string)$request->getRequiredBodyParam('q');
        $extra = (array)$request->getBodyParam('params', []);

        return $this->redirect($plugin->encoder->url($query, $extra, null, $profile));
    }

    /**
     * Renders the profile's results template for /<path>/<token>.
     * `profile` is supplied by the URL rule, not by the visitor.
     */
    public function actionRender(string $token = '', string $profile = 'default'): Response
    {
        $plugin = SearchUrlEncoder::getInstance();
        $profiles = $plugin->getProfiles();

        if (!isset($profiles[$profile])) {
            throw new NotFoundHttpException('Unknown search profile.');
        }

        $data = $plugin->encoder->decode($token, $profile);

        if ($data === null) {
            throw new NotFoundHttpException('Invalid search URL.');
        }

        $query = $data['q'];
        unset($data['q']);

        return $this->renderTemplate($profiles[$profile]['template'], [
            'query' => $query,
            'searchParams' => $data,
            'token' => $token,
            'profile' => $profile,
        ]);
    }
}
