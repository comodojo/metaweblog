## comodojo/metaweblog

A [metaweblog](http://xmlrpc.scripting.com/metaWeblogApi.html) rpc client. It uses the [comodojo/rpcclient](https://github.com/comodojo/rpcclient) to send requests.

This lib is intended to be used as basic client for many blog platforms (such as wordpress) or to generate testing cases for rpc server.

## Installation

Install [composer](https://getcomposer.org/), then:

`` composer require comodojo/metaweblog 1.0.* ``

## Usage example

Getting recent posts from a blog:

```php
try {

    // Create a new metaweblog instance providing address, username and password
    $mwlog = new \Comodojo\MetaWeblog\MetaWeblog( "www.example.org", "john", "doe" );
    
    // Get last 10 posts
    $posts = $mwlog->getRecentPosts(10);

} catch (\Exception $e) {
	
	/* something did not work :( */

}

```

## Supported methods

This library supports the whole metaweblog API:

- `getPost($id)`: retrieve a post from weblog

- `getRecentPosts(/*optional, default 10*/ $howmany)`: get `$howmany` posts from blog
 
- `newPost($struct, /*optional, default true*/ $publish)`: create new post

- `editPost($postId, $struct, /*optional, default true*/ $publish)`: edit post referenced by `postId`

- `deletePost($postId, /*optional, default false*/ $appkey, /*optional, default false*/ $publish)`: delete a post referenced by `postId`

- `getCategories()`: retrieve a list of categories from weblog

- `newMediaObject($name, $mimetype, $content, /*optional, default false*/ $overwrite)`: upload a new media to weblog using `metaWeblog.newMediaObject` call

- `getTemplate($template_type, /*optional, default false*/ $appkey)`: get template

- `setTemplate($template, $template_type, /*optional, default false*/ $appkey)`: set template

- `getUsersBlogs(/*optional, default false*/ $appkey)`: returns information about all the blogs a given user is a member of

Refer to [comodojo/metaweblog API](http://api.comodojo.org/libs/Comodojo/MetaWeblog/MetaWeblog.html) for more detailed informations about methods.

## RPC client and transport options

The `getRpcClient()` method allows access to rpc client options (and also transport options).

For example, to get recent post from a blog listening on port 8080:

```php
try {

    // Create a new metaweblog instance providing address, username and password
    $mwlog = new \Comodojo\MetaWeblog\MetaWeblog( "www.example.org", "john", "doe" );
    
    $mwlog->getRpcClient()->getTransport()->setPort(8080);
    
    // Get last 10 posts
    $posts = $mwlog->getRecentPosts(10);

} catch (\Exception $e) {
	
	/* something did not work :( */

}

```

## Other setters/getters

- Get/set blog ID

    ```php
        // Get current blog ID
        $id = $mwlog->getId()
        
        // Set current blog ID
        $mwlog->setId(2);
        
    ```

- Get/set encoding 

    ```php
        // Get current encoding
        $enc = $mwlog->getEncoding()
        
        // Set current encoding
        $mwlog->setEncoding('utf-8');
        
    ```
