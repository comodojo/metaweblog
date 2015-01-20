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
    protected $user = null;
    
    /**
     * Password
     * 
     * @param   string
     */
    protected $pass = null;

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
    protected $encoding = "UTF-8";

    private $rpc_client = false;

    /**
     * Class constructor
     * 
     * @param   string  $address  RPC server full address
     * @param   string  $user     (optional) Username
     * @param   string  $pass     (optional) Password
     * 
     * @throws \Comodojo\Exception\MetaWeblogException
     * @throws \Comodojo\Exception\HttpException;
     * @throws \Exception;
     */
    public function __construct($address, $user=false, $pass=false) {
        
        if ( empty($address) ) throw new MetaWeblogException("Invalid remote xmlrpc server");

        $this->address = $address;

        $this->user = empty($user) ? null : $user;

        $this->pass = empty($pass) ? null : $pass;

        try {
            
            $this->rpc_client = new RpcClient($address);

        } catch (HttpException $he) {
            
            throw $he;

        } catch (Exception $e) {
            
            throw $e;

        }

    }

    /**
     * Set the blog id
     *
     * @param   int     $id    Blog ID
     * 
     * @return  Object  $this
     */
    final public function setId($id) {

        $this->id = filter_var($id, FILTER_VALIDATE_INT, array(
            "options" => array(
                "default" => 0
                )
            )
        );

        return $this;

    }

    /**
     * Set encoding
     *
     * @param   string  $encoding
     * 
     * @return  Object  $this
     */
    final public function setEncoding($encoding) {

        $this->encoding = $encoding;

        return $this;

    }

    /**
     * Get blog id
     * 
     * @return  int
     */
    final public function getId() {

        return $this->id;

    }

    /**
     * Get current encoding
     * 
     * @return  string
     */
    final public function getEncoding() {

        return $this->encoding;

    }
    
    /**
     * Get the RPC client object
     *
     * @return  \Comodojo\RpcClient\RpcClient
     */
    final public function getRpcClient() {

        return $this->rpc_client;

    }

    /**
     * Retrieve a post from weblog
     * 
     * @param   int     $id     Post's ID
     * 
     * @return  array
     * 
     * @throws \Comodojo\Exception\MetaWeblogException
     * @throws \Comodojo\Exception\RpcException
     * @throws \Comodojo\Exception\HttpException
     * @throws \Comodojo\Exception\XmlrpcException
     * @throws \Exception
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
     * 
     * @throws \Comodojo\Exception\RpcException
     * @throws \Comodojo\Exception\HttpException
     * @throws \Comodojo\Exception\XmlrpcException
     * @throws \Exception
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
     *  - title         string  the post title
     *  - description   string  the post content
     * 
     * If one or both not defined, method will throw an "Invalid post struct" error.
     * 
     * @param  array  $struct  A post stuct
     * 
     * @return int
     * 
     * @throws \Comodojo\Exception\MetaWeblogException
     * @throws \Comodojo\Exception\RpcException
     * @throws \Comodojo\Exception\HttpException
     * @throws \Comodojo\Exception\XmlrpcException
     * @throws \Exception
     */
    public function newPost($struct, $publish=true) {

        if ( !is_array($struct) OR @array_key_exists('title', $struct) === false OR @array_key_exists('description', $struct) === false ) throw new MetaWeblogException('Invalid post struct');

        $real_post_struct = array(
            'title'             =>  self::sanitizeText($struct['title'], $this->encoding),
            'description'       =>  self::sanitizeText($struct['description'], $this->encoding),
            'post_type'         =>  isset($struct['post_type']) ? $struct['post_type'] : "post",
            'mt_text_more'      =>  isset($struct['mt_text_more']) ? self::sanitizeText($struct['mt_text_more'], $this->encoding) : false,
            'categories'        =>  ( isset($struct['categories']) AND is_array($struct['categories']) ) ? self::sanitizeText($struct['categories'], $this->encoding) : array(),
            'mt_keywords'       =>  ( isset($struct['mt_keywords']) AND is_array($struct['mt_keywords']) ) ? aself::sanitizeText($struct['mt_keywords'], $this->encoding) : array(),
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
     * @param   array  $struct A post stuct
     * 
     * @return  int            Assigned post ID
     * 
     * @throws \Comodojo\Exception\MetaWeblogException
     * @throws \Comodojo\Exception\RpcException
     * @throws \Comodojo\Exception\HttpException
     * @throws \Comodojo\Exception\XmlrpcException
     * @throws \Exception
     */
    public function editPost($postId, $struct, $publish = true) {

        if ( empty($postId) ) throw new MetaWeblogException('Invalid post id');

        if ( !is_array($struct) OR @array_key_exists('title', $struct) === false OR @array_key_exists('description', $struct) === false ) throw new MetaWeblogException('Invalid post struct');

        $real_post_struct = array(
            'title'             =>  self::sanitizeText($struct['title'], $this->encoding),
            'description'       =>  self::sanitizeText($struct['description'], $this->encoding),
            'post_type'         =>  isset($struct['post_type']) ? $struct['post_type'] : "post",
            'mt_text_more'      =>  isset($struct['mt_text_more']) ? self::sanitizeText($struct['mt_text_more'], $this->encoding) : false,
            'categories'        =>  ( isset($struct['categories']) AND is_array($struct['categories']) ) ? self::sanitizeText($struct['categories'], $this->encoding) : array(),
            'mt_keywords'       =>  ( isset($struct['mt_keywords']) AND is_array($struct['mt_keywords']) ) ? aself::sanitizeText($struct['mt_keywords'], $this->encoding) : array(),
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

    /**
     * Delete a post referenced by postId
     * 
     * @param   int  $postId
     * @param   int  $appkey
     * @param   int  $publish
     * 
     * @return  bool
     * 
     * @throws \Comodojo\Exception\MetaWeblogException
     * @throws \Comodojo\Exception\RpcException
     * @throws \Comodojo\Exception\HttpException
     * @throws \Comodojo\Exception\XmlrpcException
     * @throws \Exception
     */
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
     * 
     * @throws \Comodojo\Exception\RpcException
     * @throws \Comodojo\Exception\HttpException
     * @throws \Comodojo\Exception\XmlrpcException
     * @throws \Exception
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
     * @param   string $name
     * @param   string $mimetype
     * @param   mixed  $content
     * @param   int    $overwrite
     * 
     * @return  bool
     * 
     * @throws \Comodojo\Exception\MetaWeblogException
     * @throws \Comodojo\Exception\RpcException
     * @throws \Comodojo\Exception\HttpException
     * @throws \Comodojo\Exception\XmlrpcException
     * @throws \Exception
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

    /**
     * get template
     * 
     * @param   string $template_type
     * @param   string $appkey
     * 
     * @return  array
     * 
     * @throws \Comodojo\Exception\MetaWeblogException
     * @throws \Comodojo\Exception\RpcException
     * @throws \Comodojo\Exception\HttpException
     * @throws \Comodojo\Exception\XmlrpcException
     * @throws \Exception
     */
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

    /**
     * get template
     * 
     * @param   string $template
     * @param   string $template_type
     * @param   string $appkey
     * 
     * @return  bool
     * 
     * @throws \Comodojo\Exception\MetaWeblogException
     * @throws \Comodojo\Exception\RpcException
     * @throws \Comodojo\Exception\HttpException
     * @throws \Comodojo\Exception\XmlrpcException
     * @throws \Exception
     */
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
     * @param   int   $appkey
     * 
     * @return  array Posts from blog
     * 
     * @throws \Comodojo\Exception\RpcException
     * @throws \Comodojo\Exception\HttpException
     * @throws \Comodojo\Exception\XmlrpcException
     * @throws \Exception
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

    protected function sendRpcRequest($method, $params) {

        try {
            
            $return = $this->rpc_client
                ->setEncoding($this->encoding)
                ->addRequest($method, $params, false)
                ->send();

        } catch (HttpException $he) {

            throw $he;

        }  catch (RpcException $re) {

            throw $re;

        } catch (XmlrpcException $xe) {

            throw $xe;

        } catch (Exception $e) {
            
            throw $e;

        }

        return $return;

    }

    static protected function sanitizeText($mixed, $encoding) {

        if ( is_array($mixed) ) {
           
           foreach ( $mixed as $id => $val ) $mixed[$id] = self::sanitizeText($val, $encoding);
           
        } else {

           $mixed = htmlentities( iconv( mb_detect_encoding($mixed, mb_detect_order(), false), $encoding, $mixed) );

        }

        return $mixed;

    }

}
