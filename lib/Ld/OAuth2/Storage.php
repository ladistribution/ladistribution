<?php

require_once 'oauth2-php/OAuth2.php';
require_once 'oauth2-php/IOAuth2Storage.php';
require_once 'oauth2-php/IOAuth2GrantCode.php';
require_once 'oauth2-php/IOAuth2RefreshTokens.php';

/**
 * Ld storage engine for the OAuth2 Library.
 */
class Ld_OAuth2_Storage implements IOAuth2GrantCode, IOAuth2RefreshTokens {

  /**@#+
   * Centralized table names
   *
   * @var string
   */
  const TABLE_CLIENTS = 'clients';
  const TABLE_CODES   = 'auth_codes';
  const TABLE_TOKENS  = 'access_tokens';
  const TABLE_REFRESH = 'refresh_tokens';
  /**@#-*/

  public function getModel($model)
  {
      $site = Zend_Registry::get('site');
      return $site->getModel($model);
  }

  public function addClient($params = array())
  {
      $params['client_secret'] = Ld_Auth::generatePhrase();;
      $result = $this->getModel(self::TABLE_CLIENTS)->add($params);
      return array(
          'client_id' => $result['id'],
          'client_secret' => $result['client_secret'],
          'expires_in' => 0
      );
  }

  public function checkClientCredentials($client_id, $client_secret = NULL)
  {
      if ($client = $this->getClientDetails($client_id)) {
          return $client['client_secret'] == $client_secret;
      }
      return false;
  }

  public function getClientDetails($client_id)
  {
    return $this->getModel(self::TABLE_CLIENTS)->get($client_id);
  }

  public function getAccessToken($oauth_token)
  {
    return $this->getToken($oauth_token, FALSE);
  }

  public function setAccessToken($oauth_token, $client_id, $user_id, $expires, $scope = NULL)
  {
    $this->setToken($oauth_token, $client_id, $user_id, $expires, $scope, FALSE);
  }

  public function getRefreshToken($refresh_token)
  {
    return $this->getToken($refresh_token, TRUE);
  }

  public function setRefreshToken($refresh_token, $client_id, $user_id, $expires, $scope = NULL)
  {
    return $this->setToken($refresh_token, $client_id, $user_id, $expires, $scope, TRUE);
  }

  public function unsetRefreshToken($refresh_token)
  {
      $sTokens = $this->getModel(self::TABLE_TOKENS)->getAll();
      foreach ($sTokens as $id => $sToken) {
          if ($sToken['token'] == $refresh_token) {
              $this->getModel(self::TABLE_TOKENS)->delete($id);
          }
      }
  }

  public function getAuthCode($code)
  {
      return $this->getModel(self::TABLE_CODES)->getOneByKey('code', $code);
  }

  public function setAuthCode($code, $client_id, $user_id, $redirect_uri, $expires, $scope = NULL)
  {
    $authCodes = $this->getModel(self::TABLE_CODES);
    // Clean Old Auth Codes
    foreach ($authCodes->searchByKey('client_id', $client_id) as $id => $authCode) {
        if ($authCode['user_id'] == $user_id) {
            $authCodes->delete($id);
        }
    }
    $authCodes->add(compact('code', 'client_id', 'user_id', 'redirect_uri', 'expires', 'scope'));
  }

  public function checkRestrictedGrantType($client_id, $grant_type)
  {
    return TRUE; // Not implemented
  }

  protected function setToken($token, $client_id, $user_id, $expires, $scope, $isRefresh = TRUE)
  {
      $tableName = $isRefresh ? self::TABLE_REFRESH : self::TABLE_TOKENS;
      $tokens = $this->getModel($tableName);
      // Clean Old Tokens
      foreach ($tokens->searchByKey('client_id', $client_id) as $id => $oldToken) {
          if ($oldToken['user_id'] == $user_id) {
              $tokens->delete($id);
          }
      }
      $tokens->add(compact('token', 'client_id', 'user_id', 'expires', 'scope'));
  }

  protected function getToken($token, $isRefresh = true)
  {
      $tableName = $isRefresh ? self::TABLE_REFRESH :  self::TABLE_TOKENS;
      $tokenName = $isRefresh ? 'refresh_token' : 'oauth_token';
      if ($sToken = $this->getModel($tableName)->getOneByKey('token', $token)) {
          $sToken[$tokenName] = $sToken['token'];
          unset($sToken['token']);
          return $sToken;
      }
  }

}
