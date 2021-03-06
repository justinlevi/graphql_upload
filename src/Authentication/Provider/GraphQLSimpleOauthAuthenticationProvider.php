<?php

namespace Drupal\graphql_upload\Authentication\Provider;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\simple_oauth\Authentication\TokenAuthUser;
use Drupal\simple_oauth\Server\ResourceServerInterface;
use Drupal\simple_oauth\Authentication\Provider\SimpleOauthAuthenticationProvider;
use League\OAuth2\Server\Exception\OAuthServerException;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
class GraphQLSimpleOauthAuthenticationProvider extends SimpleOauthAuthenticationProvider {

  /**
   * {@inheritdoc}
   */
  public function authenticate(Request $request) {
    // Update the request with the OAuth information.
    try {
      $request = $this->resourceServer->validateAuthenticatedRequest($request);
    }
    catch (OAuthServerException $exception) {
      // Procedural code here is hard to avoid.
      watchdog_exception('simple_oauth', $exception);

      return NULL;
    }

    $_FILES = $request->files;

    $tokens = $this->entityTypeManager->getStorage('oauth2_token')->loadByProperties([
      'value' => $request->get('oauth_access_token_id'),
    ]);
    $token = reset($tokens);
    return new TokenAuthUser($token);
  }

}
