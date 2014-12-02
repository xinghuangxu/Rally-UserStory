<?php

/**
 * Rally API Connector
 *
 * Simple class for interacting with RallyDev web services
 *
 * @version 2.0
 * @author St. John Johnson <stjohn@yahoo-inc.com>  && Leon Xu <xinghuangxu@gmail.com>


 *


 */
class Rally
{

    private static $instance = null;

    public static function getInstance($username = "", $password = "")
    {
        if ($username && $password && !isset(self::$instance)) {
            self::$instance = new Rally($username, $password);
        }
        return self::$instance;
    }

    // Curl Object
    private $_curl;
    // Rally's Domain
    private $_domain;
    // Just for debugging
    private $_debug = false;
    // Some fancy user agent here
    private $_agent = 'PHP - Rally Api - 1.4';
    // Current API version
    private $_version = 'v2.0';
    // Current Workspace
    private $_workspace;
    // These headers are required to get valid JSON responses
    private $_headers_request = array('Content-Type: text/java_script');
    // Silly object translation
    private $_objectTranslation = array(
        'story' => 'hierarchicalrequirement',
        'userstory' => 'hierarchicalrequirement',
        'feature' => 'portfolioitem/feature',
        'initiative' => 'portfolioitem/initiative',
        'theme' => 'portfolioitem/theme',
    );
    // User object
    protected $_user = '';
    //Security Token
    private $_key = "";

    /**
     * Create Rally Api Object
     *
     * @param string $username
     *   The username for Rally
     * @param string $password
     *   The password for Rally (probably hunter2)
     * @param string $domain
     *   Override for Domain to talk to
     */
    public function __construct($username, $password, $domain = 'rally1.rallydev.com')
    {
        $this->_domain = $domain;


        $this->_curl = curl_init();

        set_time_limit(0);
        //ini_set('display_errors', '0');
        $this->_setopt(CURLOPT_RETURNTRANSFER, true);
        $this->_setopt(CURLOPT_HTTPHEADER, $this->_headers_request);
        $this->_setopt(CURLOPT_VERBOSE, $this->_debug);
        $this->_setopt(CURLOPT_USERAGENT, $this->_agent);
        $this->_setopt(CURLOPT_HEADER, 0);
        $this->_setopt(CURLOPT_SSL_VERIFYHOST, 0);
        $this->_setopt(CURLOPT_SSL_VERIFYPEER, 0);
        $this->_setopt(CURLOPT_COOKIEJAR, dirname(__file__) . '/cookie.txt');
        // Authentication
        $this->_setopt(CURLOPT_USERPWD, "$username:$password");
        $this->_setopt(CURLOPT_HTTPAUTH, CURLAUTH_ANY);


        // Validate Login was Successful
        $user_data = $this->find('user', "(EmailAddress = \"{$username}\")");
        $security_data = $this->_getSecurityToken('security/authorize');
        //print_r($security_data);
        // global $token;
        $this->_key = $security_data['SecurityToken'];
        //print_r($security_data['SecurityToken']);
        $_SESSION['token'] = $security_data['SecurityToken'];
        $x = $_SESSION['token'];

        // echo $_SESSION['token'];
        // print_r($this->_key);
        // print_r($this->_key['SecurityToken']);
        //print_r($user_data);
        $this->_user = $user_data[0];
    }

    /**
     * Return Reference to User
     *
     * @return string
     *   Reference link to User
     */
    public function me()
    {
        return $this->_user['_ref'];
    }

    /**
     * Translate object types
     *
     * This is only really for
     *   story -> hierarchicalrequirement
     *
     * @param string $object
     *   Rally Object Type
     * @return string
     *   Translated Object
     */
    protected function _translate($object)
    {
        $object = strtolower($object);
        if (isset($this->_objectTranslation[$object])) {
            return $this->_objectTranslation[$object];
        }
        return $object;
    }

    /**
     * Set current workspace
     *
     * @param string $workspace_ref
     *   Workspace URL Reference
     */
    public function setWorkspace($workspace_ref)
    {
        $this->_workspace = $workspace_ref;
    }

    /**
     * Generates a reference URL to the Object
     *
     * @param string $object
     *   Rally Object Type
     * @param int $id
     *   Rally Object ID
     * @return string
     *   Proper URL or _ref to use
     */
    public function getRef($object, $id)
    {
        $object = $this->_translate($object);
        $ref = "/{$object}";
        if ($id) {
            $ref .= "/{$id}";
        }
        return $ref;
    }

    /**
     * Find Rally objects
     *
     * This method automatically traverses
     * and strips out Rally API gibbledy-gook
     *
     * @param string $object
     *   Rally Object Type
     * @param string $query
     *   Query String
     * @param string $order
     *   Field to sort on
     * @param bool $fetch
     *   Fetch all content
     * @return array
     *   Returned Objects
     */
    /*
      public function findmeta($object, $query, $order = '', $fetch = true) {
      $object = $this->_translate($object);
      $params = array(
      'query' => $query,
      'fetch' => ($fetch ? 'true' : 'false'),
      'pagesize' => 100,
      'start' => 1,
      );
      if (!empty($order)) {
      $params['order'] = $order;
      }


      // Loop through and ask for results
      $results = array();
      for (;;) { // I hate infinite loops
      $objects = $this->_get($this->_addWorkspace("{$object}", $params));
      $results = array_merge($results, $objects['Results']);
      //            print_r("objects:");
      //            print_r($objects);
      // Continue only if there are more
      if ($objects['TotalResultCount'] > 99 + $params['start']) {
      $params['start'] += 100;
      continue;
      }


      // We're done, break
      break;
      }
      //        print_r("results:");
      //        print_r($results);
      return $results;
      }
     */

//    public function findWithLink($link)
//    {
//        return $this->_get($link);
//    }

    public function find($object, $query, $order = '', $fetch = '')
    {
        $object = $this->_translate($object);
        $params = array(
            'query' => $query,
            //'fetch' => ($fetch ? 'true' : 'false'),
            'fetch' => $fetch,
            'pagesize' => 100,
            'start' => 1,
        );
        if (!empty($order)) {
            $params['order'] = $order;
        }


        // Loop through and ask for results
        $results = array();
        for (;;) { // I hate infinite loops
            $objects = $this->_get($this->_addWorkspace("{$object}", $params));
            $results = array_merge($results, $objects['Results']);
            //            print_r("objects:");
            //            print_r($objects);
            // Continue only if there are more
            if ($objects['TotalResultCount'] > 99 + $params['start']) {
                $params['start'] += 100;
                continue;
            }


            // We're done, break
            break;
        }
        //        print_r("results:");
        //        print_r($results);
        return $results;
    }

    private function _getSecurityToken($object)
    {
        $object = $this->_get($this->_addWorkspace("{$object}"));
        //        print_r("Security Key:");
        //        print_r($object);
        return $object;
    }

    private function _addKey($method)
    {
        return $method . "?key=" . $this->_key;
    }

    /**
     * Get a Rally object
     *
     * @param string $object
     *   Rally Object Type
     * @param int $id
     *   Rally Object ID
     * @return array
     *   Rally API response
     */
    public function get($object, $id)
    {
        return $this->_get($this->_addWorkspace($this->getRef($object, $id)));
    }

    /**
     * Create a Rally object
     *
     * @param string $object
     *   Rally Object Type
     * @param array $params
     *   Fields to create with
     * @return array
     *   Rally API response
     */
    public function create($object, array $params)
    {
        $url = $this->_addWorkspace($this->getRef($object, 'create'));


        $object = $this->_put($url, $params);
        return $object['Object'];
    }

    /**
     * Update a Rally object
     *
     * @param string $object
     *   Rally Object Type
     * @param int $id
     *   Rally Object ID
     * @param array $params
     *   Fields to update
     * @return array
     *   Rally API response
     */
    public function update($object, $id, array $params)
    {
        $url = $this->_addWorkspace($this->getRef($object, $id));
        //        print_r($url);
        //        print_r($params);
        $object = $this->_post($url, $params);
        return $object['Object'];
    }

    /**
     * Delete a Rally object
     *
     * @param string $object
     *   Rally Object Type
     * @param int $id
     *   Rally Object ID
     * @return bool
     */
    public function delete($object, $id)
    {
        $url = $this->_addWorkspace($this->getRef($object, $id));


        // There are no values that return here
        $this->_delete($this->getRef($url, $id));
        return true;
    }

    /**
     * Wraps Workspace around URL
     *
     * @param string $url
     *   URL to access
     * @param array $params
     *   Any additional parameters to put on the Query String
     */
    protected function _addWorkspace($url, array $params = array())
    {
        // Add workspace
        if ($this->_workspace) {
            $params['workspace'] = $this->_workspace;
        }


        // Add params as url
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }


        return $url;
    }

    /**
     * Perform a HTTP Get
     *
     * @param string $method
     *   Method of the API to execute
     * @return array
     *   API return data
     */
    protected function _get($method)
    {
        $this->_setopt(CURLOPT_CUSTOMREQUEST, 'GET');
        $this->_setopt(CURLOPT_POSTFIELDS, '');


        return $this->_execute($this->_addKey($method));
    }

    /**
     * Perform a HTTP Post
     *
     * @param string $method
     *   Method of the API to execute
     * @param array $params
     *   Paramters to pass
     * @return array
     *   API return data
     */
    protected function _post($method, array $params = array())
    {
        $this->_setopt(CURLOPT_CUSTOMREQUEST, 'POST');


        $payload = json_encode(array('Content' => $params));
        $this->_setopt(CURLOPT_POSTFIELDS, $payload);


        return $this->_execute($this->_addKey($method));
    }

    /**
     * Perform a HTTP Put
     *
     * @param string $method
     *   Method of the API to execute
     * @param array $params
     *   Paramters to pass
     * @return array
     *   API return data
     */
    protected function _put($method, array $params = array())
    {
        $this->_setopt(CURLOPT_CUSTOMREQUEST, 'PUT');


        $payload = json_encode(array('Content' => $params));
        $this->_setopt(CURLOPT_POSTFIELDS, $payload);
        //        print_r("put method:");
        //        print_r($method);
        return $this->_execute($this->_addKey($method));
    }

    /**
     * Perform a HTTP Delete
     *
     * @param string $method
     *   Method of the API to execute
     * @return array
     *   API return data
     */
    protected function _delete($method)
    {
        $this->_setopt(CURLOPT_CUSTOMREQUEST, 'DELETE');


        return $this->_execute($method);
    }

    /**
     * Execute the Curl object
     *
     * @param string $method
     *   Method of the API to execute
     * @return array
     *   API return data
     * @throws RallyApiException
     *   On Curl errors
     */
    protected function _execute($method)
    {
        $method = ltrim($method, '/');
        $url = "https://{$this->_domain}/slm/webservice/{$this->_version}/{$method}";
        

        $this->_setopt(CURLOPT_URL, $url);
        //        print_r("URL-leonx:");
        //        print_r($url);
        $response = curl_exec($this->_curl);
        //        print_r($response);
        //        return;
        if (curl_errno($this->_curl)) {
            throw new RallyApiException(curl_error($this->_curl));
        }


        $info = curl_getinfo($this->_curl);
        //        print_r("Response-Leonx");
        //        print_r($response);
        return $this->_result($response, $info);
    }

    /**
     * Perform Json Decryption of the output
     *
     * @param string $response
     *   Curl Response
     * @param array $info
     *   Curl Info Array
     * @return array
     *   API return data
     * @throws RallyApiException
     *   On non-2xx responses
     */
    protected function _result($response, array $info)
    {
        // Panic on non-200 responses
        if ($info['http_code'] >= 300 || $info['http_code'] < 200) {
            header('HTTP/1.0 400 Bad error');
            throw new RallyApiException($response, $info['http_code']);
        }


        $object = json_decode($response, true);


        $wrappers = array(
            'OperationResult',
            'CreateResult',
            'QueryResult');
        // If we have one of these formats, strip out errors
        if (in_array(key($object), $wrappers)) {
            // Strip key
            $object = reset($object);


            // Look for errors and warnings
            if (!empty($object['Errors'])) {
                throw new RallyApiError(implode(PHP_EOL, $object['Errors']));
            }
            if (!empty($object['Warnings'])) {
                throw new RallyApiWarning(implode(PHP_EOL, $object['Warnings']));
            }
        }
        //        print_r("ResultObject-Leonx");
        //        print_r($object);
        return $object;
    }

    /**
     * Wrapper for curp_setopt
     *
     * @param string $option
     *   the CURLOPT_XXX option to set
     * @param varied $value
     *   the value
     */
    protected function _setopt($option, $value)
    {
        curl_setopt($this->_curl, $option, $value);
    }

}

class RallyApiException extends Exception
{
    
}

class RallyApiError extends RallyApiException
{
    
}

class RallyApiWarning extends RallyApiException
{
    
}
