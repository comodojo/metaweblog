<?php

class MetaWeblogTest extends \PHPUnit_Framework_TestCase {

    protected $mwlog = null;

    protected function setUp() {

        $address = "http://localhost/xmlrpc.php";

        $user = "admin";

        $pass = "admin";
        
        $this->mwlog = new \Comodojo\MetaWeblog\MetaWeblog($address, $user, $pass);
    
    }

    public function testGetRecentPosts() {
        
        $posts = $this->mwlog->getRecentPosts(2);

        $this->assertInternalType('array', $posts);

        $this->assertSame('publish', $posts[0]['post_status']);

    }

    public function testGetPost() {
        
        $post = $this->mwlog->getPost(1);

        $this->assertInternalType('array', $post);

        $this->assertSame(1, $post['postid']);

    }

    public function testNewPost() {

        $struct = array(
            'title'             =>  "Test Post",
            'description'       =>  "Test Post description",
            'post_type'         =>  "post",
            'categories'        =>  array('uncategorized'),
            'mt_keywords'       =>  array('test','post'),
            'mt_allow_comments' =>  "open",
            'mt_allow_pings'    =>  "open"
        );
        
        $post = $this->mwlog->newPost($struct);

        $this->assertInternalType('int', intval($post));

        $this->assertGreaterThan(1, intval($post));

    }

    public function testEditPost() {

        $struct = array(
            'title'             =>  "Test Post",
            'description'       =>  "Test Post description",
            'post_type'         =>  "post",
            'categories'        =>  array('uncategorized'),
            'mt_keywords'       =>  array('test','post'),
            'mt_allow_comments' =>  "open",
            'mt_allow_pings'    =>  "open"
        );
        
        $post = $this->mwlog->editPost(1, $struct);

        $this->assertTrue($post);

    }

    public function testDeletePost() {

        $post = $this->mwlog->deletePost(1);

        $this->assertTrue($post);

    }

    public function testGetCategories() {

        $cats = $this->mwlog->getCategories();

        $this->assertInternalType('array', $cats);

        $this->assertSame('Uncategorized', $cats[0]['description']);

    }

    public function testGetUsersBlogs() {

        $blogs = $this->mwlog->getUsersBlogs();

        $this->assertInternalType('array', $blogs);

        $this->assertSame('Test', $blogs[0]['blogName']);

    }

    public function testNewMediaObject() {

        $file = __DIR__."/../resources/keepcalm.png";

        $content = file_get_contents($file);

        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        $mime = finfo_file($finfo, $file);

        finfo_close($finfo);

        $name = "keepcalm-" . time() . ".png";

        $upload = $this->mwlog->newMediaObject($name, $mime, $content);

        $this->assertInternalType('array', $upload);

        $this->assertGreaterThan(0, intval($upload['id']));

        $this->assertSame($name, $upload['file']);

        $this->assertSame($mime, $upload['type']);

    }
    

}
