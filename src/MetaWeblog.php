<?php namespace Comodojo\MetaWeblog;

use \Comodojo\Exception\MetaWeblogException;
use \Comodojo\Exception\RpcException;
use \Comodojo\Exception\HttpException;
use \Comodojo\Exception\XmlrpcException;
use \Exception;
use \Comodojo\RpcClient\RpcClient;

/** 
 * MetaWeblog client
 *
 * @package     Comodojo Spare Parts
 * @author      Marco Giovinazzi <info@comodojo.org>
 * @license     GPL-3.0+
 *
 * LICENSE:
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

class MetaWeblog {

    /**
     * Address of the xmlrpc server interface
     * 
     * @param   string
     */
    private $address = null;
    
    /**
     * Username
     * 
     * @param   string
     */
    private $user = null;
    
    /**
     * Password
     * 
     * @param   string
     */
    private $pass = null;

    /**
     * XmlRpc server port
     * 
     * @param   string
     */
    private $port = 80;

    /**
     * Weblog ID (leave it 0 if you're in single-blog mode)
     * 
     * @param   string
     */
    private $id = 0;
    
    /**
     * Messages encoding (will be applied to - almost - every string!)
     * 
     * @param   string
     */
    private $encoding = "UTF-8";

    private $rpc_client = false;

    /**
     * Class constructor
     * 
     * @param   STRING  $weblogAddress
     * @param   STRING  $weblogUserName
     * @param   STRING  $weblogUserName
     */
    public function __construct($address, $user, $pass) {
        
        if ( empty($address) ) throw new MetaWeblogException("Invalid remote xmlrpc server");

        $this->address = $address;

        $this->user = empty($user) ? null : $user;

        $this->pass = empty($pass) ? null : $pass;

        try {
            
            $this->rpc_client = new RpcClient($address);

        } catch (Exception $e) {
            
            throw $e;

        }

    }

    final public function setPort($port) {

        $this->port = filter_var($port, FILTER_VALIDATE_INT, array(
            "options" => array(
                "min_range" => 1,
                "max_range" => 65535,
                "default" => 80
                )
            )
        );

        return $this;

    }

    final public function setId($id) {

        $this->id = filter_var($id, FILTER_VALIDATE_INT, array(
            "options" => array(
                "default" => 0
                )
            )
        );

        return $this;

    }

    final public function setEncoding($encoding) {

        $this->encoding = $encoding;

        return $this;

    }

    final public function getPort() {

        return $this->port;

    }

    final public function getId() {

        return $this->id;

    }

    final public function getEncoding() {

        return $this->encoding;

    }

    /**
     * Retrieve a post from weblog
     * 
     * @param   int     $id     Post's ID
     * @return  array
     */
    public function getPost($id) {

        if ( empty($id) ) throw new MetaWeblogException("Invalid post id");

        $params = array(
            $id,
            $this->user,
            $this->pass
        );

        try {

            $response = $this->sendRpcRequest('metaWeblog.getPost', $params);

        } catch (HttpException $he) {

            throw $he;

        } catch (RpcException $re) {

            throw $re;

        } catch (XmlrpcException $xe) {

            throw $xe;

        } catch (Exception $e) {

            throw $e;

        }

        return $response;

    }

    /**
     * Get [$howmany] posts from blog
     * 
     * @param   int     $howmany    Number of posts to retrieve from blog (default 10)
     * 
     * @return  array   Posts from blog
     */
    public function getRecentPosts($howmany=10) {

        $howmany = filter_var($howmany, FILTER_VALIDATE_INT, array(
            "options" => array(
                "default" => 10
                )
            )
        );

        $params = array(
            $this->id,
            $this->user,
            $this->pass,
            $howmany
        );

        try {

            $response = $this->sendRpcRequest('metaWeblog.getRecentPosts', $params);

        } catch (HttpException $he) {

            throw $he;

        } catch (RpcException $re) {

            throw $re;

        } catch (XmlrpcException $xe) {

            throw $xe;

        } catch (Exception $e) {

            throw $e;

        }

        return $response;

    }

    /**
     * Create new post using xmlrpc
     * 
     * Minimum $struct elements to compose new post are:
     *  - title         STRING  the post title
     *  - description   STRING  the post content
     * 
     * If one or both not defined, method will throw an "Invalid post struct" error.
     * 
     * @param   ARRAY   $struct     A post stuct
     * @return  INT                 Assigned post ID
     */
    public function newPost($struct, $publish=true) {

        if ( !is_array($struct) OR @array_key_exists('title', $struct) === false OR @array_key_exists('description', $struct) === false ) throw new MetaWeblogException('Invalid post struct');

        $real_post_struct = array(
            'title'             =>  self::sanitizeText($struct['title'], $this->encoding),
            'description'       =>  self::sanitizeText($struct['description'], $this->encoding),
            'post_type'         =>  isset($struct['post_type']) ? $struct['post_type'] : "post",
            'mt_text_more'      =>  isset($struct['mt_text_more']) ? self::sanitizeText($struct['mt_text_more'], $this->encoding) : false,
            'categories'        =>  isset($struct['categories']) AND is_array($struct['categories']) ? array_map( function($value) { return self::sanitizeText($value, $this->encoding); }, $struct['categories'] ) : array(),
            'mt_keywords'       =>  isset($struct['mt_keywords']) AND is_array($struct['mt_keywords']) ? array_map( function($value) { return self::sanitizeText($value, $this->encoding); }, $struct['mt_keywords'] ) : array(),
            'mt_excerpt'        =>  isset($struct['mt_excerpt']) ? self::sanitizeText($struct['mt_excerpt'], $this->encoding) : false,
            'mt_text_more'      =>  isset($struct['mt_text_more']) ? self::sanitizeText($struct['mt_text_more'], $this->encoding) : false,
            'mt_allow_comments' =>  isset($struct['mt_allow_comments']) ? $struct['mt_allow_comments'] : "open",
            'mt_allow_pings'    =>  isset($struct['mt_allow_pings']) ? $struct['mt_allow_pings'] : "open"
        );

        if ( isset($struct['enclosure']) ) $real_post_struct['enclosure'] = $struct['enclosure'];

        $params = array(
            $this->id,
            $this->user,
            $this->pass,
            $real_post_struct,
            filter_var($publish, FILTER_VALIDATE_BOOLEAN)
        );

        try {

            $response = $this->sendRpcRequest('metaWeblog.newPost', $params);

        } catch (HttpException $he) {

            throw $he;

        } catch (RpcException $re) {

            throw $re;

        } catch (XmlrpcException $xe) {

            throw $xe;

        } catch (Exception $e) {

            throw $e;

        }

        return $response;

    }
    
    /**
     * Edit post using remote server xmlrpc interface, referenced by postId
     * 
     * A post struct currently support a non-standard set of elements to better
     * support modern interfaces such as wordpress one. Anyway, server should ignore
     * elements not known.
     * 
     * @param   ARRAY   $struct     A post stuct
     * @return  INT                 Assigned post ID
     */
    public function editPost($postId, $struct, $publish = true) {

        if ( empty($postId) ) throw new MetaWeblogException('Invalid post id');

        if ( is_array($struct) OR @array_key_exists('title', $struct) === false OR @array_key_exists('description', $struct) === false ) throw new MetaWeblogException('Invalid post struct');

        $real_post_struct = array(
            'title'             =>  self::sanitizeText($struct['title'], $this->encoding),
            'description'       =>  self::sanitizeText($struct['description'], $this->encoding),
            'post_type'         =>  isset($struct['post_type']) ? $struct['post_type'] : "post",
            'mt_text_more'      =>  isset($struct['mt_text_more']) ? self::sanitizeText($struct['mt_text_more'], $this->encoding) : false,
            'categories'        =>  isset($struct['categories']) AND is_array($struct['categories']) ? array_map( function($value) { return self::sanitizeText($value, $this->encoding); }, $struct['categories'] ) : array(),
            'mt_keywords'       =>  isset($struct['mt_keywords']) AND is_array($struct['mt_keywords']) ? array_map( function($value) { return self::sanitizeText($value, $this->encoding); }, $struct['mt_keywords'] ) : array(),
            'mt_excerpt'        =>  isset($struct['mt_excerpt']) ? self::sanitizeText($struct['mt_excerpt'], $this->encoding) : false,
            'mt_text_more'      =>  isset($struct['mt_text_more']) ? self::sanitizeText($struct['mt_text_more'], $this->encoding) : false,
            'mt_allow_comments' =>  isset($struct['mt_allow_comments']) ? $struct['mt_allow_comments'] : "open",
            'mt_allow_pings'    =>  isset($struct['mt_allow_pings']) ? $struct['mt_allow_pings'] : "open"
        );

        if ( isset($struct['enclosure']) ) $real_post_struct['enclosure'] = $struct['enclosure'];
        
        $params = array(
            $postId,
            $this->user,
            $this->pass,
            $real_post_struct,
            filter_var($publish, FILTER_VALIDATE_BOOLEAN)
        );

        try {

            $response = $this->sendRpcRequest('metaWeblog.editPost', $params);

        } catch (HttpException $he) {

            throw $he;

        } catch (RpcException $re) {

            throw $re;

        } catch (XmlrpcException $xe) {

            throw $xe;

        } catch (Exception $e) {

            throw $e;

        }

        return $response;

    }

    public function deletePost($postId, $appkey = false, $publish = false) {

        if ( empty($postId) ) throw new MetaWeblogException('Invalid post id');

        $params = array(
            $appkey,
            $postId,
            $this->user,
            $this->pass,
            $publish
        );

        try {

            $response = $this->sendRpcRequest('metaWeblog.deletePost', $params);

        } catch (HttpException $he) {

            throw $he;

        } catch (RpcException $re) {

            throw $re;

        } catch (XmlrpcException $xe) {

            throw $xe;

        } catch (Exception $e) {

            throw $e;

        }

        return $response;

    }

    /**
     * Retrieve a list of categories from weblog
     * 
     * @return  ARRAY   Categories
     */
    public function getCategories() {

        $params = array(
            $this->id,
            $this->user,
            $this->pass
        );

        try {

            $response = $this->sendRpcRequest('metaWeblog.getCategories', $params);

        } catch (HttpException $he) {

            throw $he;

        } catch (RpcException $re) {

            throw $re;

        } catch (XmlrpcException $xe) {

            throw $xe;

        } catch (Exception $e) {

            throw $e;

        }

        return $response;

    }

    /**
     * upload a new media to weblog using metaWeblog.newMediaObject call
     * 
     * [...]
     * 
     * @param   ARRAY   $struct     A post stuct
     * @return  INT                 Assigned post ID
     */
    public function newMediaObject($name, $mimetype, $content, $overwrite=false) {

        if ( empty($name) OR empty($mimetype) OR empty($content) ) throw new MetaWeblogException('Invalid media object');

        $params = array(
            $this->id,
            $this->user,
            $this->pass,
            array(
                "name"      => $name,
                "type"      => $mimetype,
                "bits"      => base64_encode($content),
                "overwrite" => filter_var($overwrite, FILTER_VALIDATE_BOOLEAN)
            )
        );

        try {

            $this->rpc_client->setValueType($params[3]["bits"], "base64");
            
            $response = $this->sendRpcRequest('metaWeblog.newMediaObject', $params);

        } catch (HttpException $he) {

            throw $he;

        } catch (RpcException $re) {

            throw $re;

        } catch (XmlrpcException $xe) {

            throw $xe;

        } catch (Exception $e) {

            throw $e;

        }

        return $response;
        
    }

    public function getTemplate($template_type, $appkey=false) {

        if ( empty($template_type) ) throw new MetaWeblogException('Invalid template type');

        $params = array(
            $appkey,
            $this->id,
            $this->user,
            $this->pass,
            $template_type
        );

        try {

            $response = $this->sendRpcRequest('metaWeblog.getTemplate', $params);

        } catch (HttpException $he) {

            throw $he;

        } catch (RpcException $re) {

            throw $re;

        } catch (XmlrpcException $xe) {

            throw $xe;

        } catch (Exception $e) {

            throw $e;

        }

        return $response;

    }

    public function setTemplate($template, $template_type, $appkey=false) {

        if ( empty($template_type) ) throw new MetaWeblogException('Invalid template type');

        if ( empty($template) ) throw new MetaWeblogException('Invalid template name');

        $params = array(
            $appkey,
            $this->id,
            $this->user,
            $this->pass,
            $template,
            $template_type
        );

        try {

            $response = $this->sendRpcRequest('metaWeblog.setTemplate', $params);

        } catch (HttpException $he) {

            throw $he;

        } catch (RpcException $re) {

            throw $re;

        } catch (XmlrpcException $xe) {

            throw $xe;

        } catch (Exception $e) {

            throw $e;

        }

        return $response;

    }

    /**
     * Returns information about all the blogs a given user is a member of
     * 
     * @param   int     $howmany    Number of posts to retrieve from blog (default 10)
     * 
     * @return  array   Posts from blog
     */
    public function getUsersBlogs($appkey=false) {

        $params = array(
            $appkey,
            $this->user,
            $this->pass
        );

        try {

            $response = $this->sendRpcRequest('metaWeblog.getUsersBlogs', $params);

        } catch (HttpException $he) {

            throw $he;

        } catch (RpcException $re) {

            throw $re;

        } catch (XmlrpcException $xe) {

            throw $xe;

        } catch (Exception $e) {

            throw $e;

        }

        return $response;

    }

    private function sendRpcRequest($method, $params) {

        try {
            
            $return = $this->rpc_client
                ->port($this->port)
                ->encode($this->encoding)
                ->request($method, $params, false)
                ->send();

        } catch (Exception $e) {
            
            throw $e;

        }

        return $return;

    }

    static private function sanitizeText($text, $encoding) {

        return htmlentities( iconv( mb_detect_encoding($text, mb_detect_order(), false), $encoding, $text) );

    }

}
