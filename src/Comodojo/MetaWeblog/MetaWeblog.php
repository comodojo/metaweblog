<?php namespace Comodojo\MetaWeblog;

use \Comodojo\RpcClient\RpcClient;
use \Comodojo\RpcClient\RpcRequest;
use \Comodojo\RpcClient\Traits\Encoding;
use \Comodojo\Foundation\Validation\DataFilter;
use \Comodojo\Exception\MetaWeblogException;
use \Comodojo\Exception\RpcException;
use \Comodojo\Exception\HttpException;
use \Comodojo\Exception\XmlrpcException;
use \Exception;

/**
 * MetaWeblog client
 *
 * @package     Comodojo Spare Parts
 * @author      Marco Giovinazzi <marco.giovinazzi@comodojo.org>
 * @license     MIT
 *
 * LICENSE:
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

class MetaWeblog {

    use Encoding;

    /**
     * Address of the xmlrpc server interface
     *
     * @param   string
     */
    private $address;

    /**
     * Username
     *
     * @param   string
     */
    protected $user;

    /**
     * Password
     *
     * @param   string
     */
    protected $pass;

    /**
     * Weblog ID (leave it 0 if you're in single-blog mode)
     *
     * @param   string
     */
    private $id = 0;

    /**
     * RpcClient handler
     *
     * @param   \Comodojo\RpcClient\RpcClient
     */
    private $rpc_client;

    /**
     * Class constructor
     *
     * @param   string  $address  RPC server full address
     * @param   string  $user     (optional) Username
     * @param   string  $pass     (optional) Password
     *
     * @throws MetaWeblogException
     * @throws HttpException;
     * @throws Exception;
     */
    public function __construct($address, $user = false, $pass = false) {

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
     * @return  MetaWeblog
     */
    public function setId($id) {

        $this->id = DataFilter::filterInteger($id, 0);

        return $this;

    }

    /**
     * Get blog id
     *
     * @return  int
     */
    public function getId() {

        return $this->id;

    }

    /**
     * Get the RPC client object
     *
     * @return RpcClient
     */
    public function getRpcClient() {

        return $this->rpc_client;

    }

    /**
     * Retrieve a post from weblog
     *
     * @param   int     $id     Post's ID
     *
     * @return  array
     *
     * @throws MetaWeblogException
     * @throws RpcException
     * @throws HttpException
     * @throws XmlrpcException
     * @throws Exception
     */
    public function getPost($id) {

        if ( empty($id) ) throw new MetaWeblogException("Invalid post id");

        $params = [
            $id,
            $this->user,
            $this->pass
        ];

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
     * @throws RpcException
     * @throws HttpException
     * @throws XmlrpcException
     * @throws Exception
     */
    public function getRecentPosts($howmany = 10) {

        $howmany = DataFilter::filterInteger($howmany, 1, PHP_INT_MAX, 10);

        $params = [
            $this->id,
            $this->user,
            $this->pass,
            $howmany
        ];

        try {

            return $this->sendRpcRequest(
                RpcRequest::create('metaWeblog.getRecentPosts', $params)
            );

        } catch (HttpException $he) {

            throw $he;

        } catch (RpcException $re) {

            throw $re;

        } catch (XmlrpcException $xe) {

            throw $xe;

        } catch (Exception $e) {

            throw $e;

        }

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
     * @throws MetaWeblogException
     * @throws RpcException
     * @throws HttpException
     * @throws XmlrpcException
     * @throws Exception
     */
    public function newPost(array $struct, $publish = true) {

        if (
            @array_key_exists('title', $struct) === false ||
            @array_key_exists('description', $struct) === false
        ) {
            throw new MetaWeblogException('Invalid post struct');
        }

        $real_post_struct = [
            'title'             =>  self::sanitizeText($struct['title'], $this->encoding),
            'description'       =>  self::sanitizeText($struct['description'], $this->encoding),
            'post_type'         =>  isset($struct['post_type']) ? $struct['post_type'] : "post",
            'mt_text_more'      =>  isset($struct['mt_text_more']) ? self::sanitizeText($struct['mt_text_more'], $this->encoding) : false,
            'categories'        =>  (isset($struct['categories']) && is_array($struct['categories'])) ? self::sanitizeText($struct['categories'], $this->encoding) : array(),
            'mt_keywords'       =>  (isset($struct['mt_keywords']) && is_array($struct['mt_keywords'])) ? self::sanitizeText($struct['mt_keywords'], $this->encoding) : array(),
            'mt_excerpt'        =>  isset($struct['mt_excerpt']) ? self::sanitizeText($struct['mt_excerpt'], $this->encoding) : false,
            'mt_text_more'      =>  isset($struct['mt_text_more']) ? self::sanitizeText($struct['mt_text_more'], $this->encoding) : false,
            'mt_allow_comments' =>  isset($struct['mt_allow_comments']) ? $struct['mt_allow_comments'] : "open",
            'mt_allow_pings'    =>  isset($struct['mt_allow_pings']) ? $struct['mt_allow_pings'] : "open"
        ];

        if ( isset($struct['enclosure']) ) $real_post_struct['enclosure'] = $struct['enclosure'];

        $params = [
            $this->id,
            $this->user,
            $this->pass,
            $real_post_struct,
            DataFilter::filterBoolean($publish)
        ];

        try {

            return $this->sendRpcRequest(
                RpcRequest::create('metaWeblog.newPost', $params)
            );

        } catch (HttpException $he) {

            throw $he;

        } catch (RpcException $re) {

            throw $re;

        } catch (XmlrpcException $xe) {

            throw $xe;

        } catch (Exception $e) {

            throw $e;

        }

    }

    /**
     * Edit post using remote server xmlrpc interface, referenced by postId
     *
     * @param   array  $struct A post stuct
     *
     * @return  int            Assigned post ID
     *
     * @throws MetaWeblogException
     * @throws RpcException
     * @throws HttpException
     * @throws XmlrpcException
     * @throws Exception
     */
    public function editPost($postId, array $struct, $publish = true) {

        if ( empty($postId) ) throw new MetaWeblogException('Invalid post id');

        if (
            @array_key_exists('title', $struct) === false ||
            @array_key_exists('description', $struct) === false
        ) {
            throw new MetaWeblogException('Invalid post struct');
        }

        $real_post_struct = [
            'title'             =>  self::sanitizeText($struct['title'], $this->encoding),
            'description'       =>  self::sanitizeText($struct['description'], $this->encoding),
            'post_type'         =>  isset($struct['post_type']) ? $struct['post_type'] : "post",
            'mt_text_more'      =>  isset($struct['mt_text_more']) ? self::sanitizeText($struct['mt_text_more'], $this->encoding) : false,
            'categories'        =>  (isset($struct['categories']) && is_array($struct['categories'])) ? self::sanitizeText($struct['categories'], $this->encoding) : array(),
            'mt_keywords'       =>  (isset($struct['mt_keywords']) && is_array($struct['mt_keywords'])) ? self::sanitizeText($struct['mt_keywords'], $this->encoding) : array(),
            'mt_excerpt'        =>  isset($struct['mt_excerpt']) ? self::sanitizeText($struct['mt_excerpt'], $this->encoding) : false,
            'mt_text_more'      =>  isset($struct['mt_text_more']) ? self::sanitizeText($struct['mt_text_more'], $this->encoding) : false,
            'mt_allow_comments' =>  isset($struct['mt_allow_comments']) ? $struct['mt_allow_comments'] : "open",
            'mt_allow_pings'    =>  isset($struct['mt_allow_pings']) ? $struct['mt_allow_pings'] : "open"
        ];

        if ( isset($struct['enclosure']) ) $real_post_struct['enclosure'] = $struct['enclosure'];

        $params = array(
            $postId,
            $this->user,
            $this->pass,
            $real_post_struct,
            DataFilter::filterBoolean($publish)
        );

        try {

            return $this->sendRpcRequest(
                RpcRequest::create('metaWeblog.editPost', $params)
            );

        } catch (HttpException $he) {

            throw $he;

        } catch (RpcException $re) {

            throw $re;

        } catch (XmlrpcException $xe) {

            throw $xe;

        } catch (Exception $e) {

            throw $e;

        }

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
     * @throws MetaWeblogException
     * @throws RpcException
     * @throws HttpException
     * @throws XmlrpcException
     * @throws Exception
     */
    public function deletePost($postId, $appkey = false, $publish = false) {

        if ( empty($postId) ) throw new MetaWeblogException('Invalid post id');

        $params = [
            $appkey,
            $postId,
            $this->user,
            $this->pass,
            DataFilter::filterBoolean($publish)
        ];

        try {

            return $this->sendRpcRequest(
                RpcRequest::create('metaWeblog.deletePost', $params)
            );

        } catch (HttpException $he) {

            throw $he;

        } catch (RpcException $re) {

            throw $re;

        } catch (XmlrpcException $xe) {

            throw $xe;

        } catch (Exception $e) {

            throw $e;

        }

    }

    /**
     * Retrieve a list of categories from weblog
     *
     * @return  array   Categories
     *
     * @throws RpcException
     * @throws HttpException
     * @throws XmlrpcException
     * @throws Exception
     */
    public function getCategories() {

        $params = [
            $this->id,
            $this->user,
            $this->pass
        ];

        try {

            return $this->sendRpcRequest(
                RpcRequest::create('metaWeblog.getCategories', $params)
            );

        } catch (HttpException $he) {

            throw $he;

        } catch (RpcException $re) {

            throw $re;

        } catch (XmlrpcException $xe) {

            throw $xe;

        } catch (Exception $e) {

            throw $e;

        }

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
     * @throws MetaWeblogException
     * @throws RpcException
     * @throws HttpException
     * @throws XmlrpcException
     * @throws Exception
     */
    public function newMediaObject($name, $mimetype, $content, $overwrite = false) {

        if ( empty($name) || empty($mimetype) || empty($content) ) throw new MetaWeblogException('Invalid media object');

        $params = [
            $this->id,
            $this->user,
            $this->pass,
            [
                "name"      => $name,
                "type"      => $mimetype,
                "bits"      => base64_encode($content),
                "overwrite" => DataFilter::filterBoolean($overwrite)
            ]
        ];

        try {

            $request = RpcRequest::create('metaWeblog.newMediaObject', $params);
            $request->setSpecialType($params[3]["bits"], "base64");

            return $this->sendRpcRequest($request);

        } catch (HttpException $he) {

            throw $he;

        } catch (RpcException $re) {

            throw $re;

        } catch (XmlrpcException $xe) {

            throw $xe;

        } catch (Exception $e) {

            throw $e;

        }

    }

    /**
     * get template
     *
     * @param   string $template_type
     * @param   string $appkey
     *
     * @return  array
     *
     * @throws MetaWeblogException
     * @throws RpcException
     * @throws HttpException
     * @throws XmlrpcException
     * @throws Exception
     */
    public function getTemplate($template_type, $appkey = false) {

        if ( empty($template_type) ) throw new MetaWeblogException('Invalid template type');

        $params = array(
            $appkey,
            $this->id,
            $this->user,
            $this->pass,
            $template_type
        );

        try {

            return $this->sendRpcRequest(
                RpcRequest::create('metaWeblog.getTemplate', $params)
            );

        } catch (HttpException $he) {

            throw $he;

        } catch (RpcException $re) {

            throw $re;

        } catch (XmlrpcException $xe) {

            throw $xe;

        } catch (Exception $e) {

            throw $e;

        }

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
    public function setTemplate($template, $template_type, $appkey = false) {

        if ( empty($template_type) ) throw new MetaWeblogException('Invalid template type');

        if ( empty($template) ) throw new MetaWeblogException('Invalid template name');

        $params = [
            $appkey,
            $this->id,
            $this->user,
            $this->pass,
            $template,
            $template_type
        ];

        try {

            return $this->sendRpcRequest(
                RpcRequest::create('metaWeblog.setTemplate', $params)
            );

        } catch (HttpException $he) {

            throw $he;

        } catch (RpcException $re) {

            throw $re;

        } catch (XmlrpcException $xe) {

            throw $xe;

        } catch (Exception $e) {

            throw $e;

        }

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
    public function getUsersBlogs($appkey = false) {

        $params = [
            $appkey,
            $this->user,
            $this->pass
        ];

        try {

            return $this->sendRpcRequest(
                RpcRequest::create('metaWeblog.getUsersBlogs', $params)
            );

        } catch (HttpException $he) {

            throw $he;

        } catch (RpcException $re) {

            throw $re;

        } catch (XmlrpcException $xe) {

            throw $xe;

        } catch (Exception $e) {

            throw $e;

        }

    }

    /**
     * Send the request via RpcClient
     *
     * @param   string   $method
     * @param   array    $params
     *
     * @return  mixed
     *
     * @throws RpcException
     * @throws HttpException
     * @throws XmlrpcException
     * @throws Exception
     */
    protected function sendRpcRequest(RpcRequest $request) {

        try {

            return $this->rpc_client
                ->setEncoding($this->encoding)
                ->addRequest($request)
                ->send();

        } catch (HttpException $he) {

            throw $he;

        } catch (RpcException $re) {

            throw $re;

        } catch (XmlrpcException $xe) {

            throw $xe;

        } catch (Exception $e) {

            throw $e;

        }

    }

    /**
     * Re-encode text
     *
     * @param   mixed    $mixed
     * @param   string   $encoding
     *
     * @return  mixed
     */
    protected static function sanitizeText($mixed, $encoding) {

        if ( is_array($mixed) ) {

            foreach ( $mixed as $id => $val ) $mixed[$id] = self::sanitizeText($val, $encoding);

        } else {

            $mixed = htmlentities( iconv( mb_detect_encoding($mixed, mb_detect_order(), false), $encoding, $mixed) );

        }

        return $mixed;

    }

}
