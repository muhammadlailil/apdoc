<div align="center">
<h2>ApDoc for Laravel - The API Documentation Generator 
    <sup style="font-size:12px">
    Fork From <a target="_blank" href="https://github.com/ovac/idoc">Laravel IDoc</a>
    </sup>
</h2>
<p>Automatically generate an interactive API documentation from your existing Laravel routes.</p>
</div>

<br/>
<br/>

## Installation

> Note: PHP 8 and Laravel 9.19 or higher are the minimum dependencies.

```sh
$ composer require laililmahfud/apdoc
```

### Laravel
Publish the config file by running:

```bash
php artisan vendor:publish --tag=apdoc-config
```
This will create an `apdoc.php` file in your `config` folder.

## Configuration

Before you can generate your documentation, you'll need to configure a few things in your `config/apdoc.php`.

-   `path`
    This will be used to register the necessary routes for the package.

```php
'path' => 'api-documentation',
```

-   `title`
    Here, you can specify the title to place on the documentation page.

```php
'title' => 'ApDoc API',
```

-   `description`
    This will place a description on top of the documentation.

```php
'description' => 'ApDoc Api secification and documentation.',
```

-   `version`
    Documentation version number.


-   `output`
    This package can automatically generate an Open-API 3.0 specification file for your routes, along with the documentation. This is the file path where the generated documentation will be written to. Default: **storage/api-docs**

-   `servers`
    The servers array can be used to add multiple endpoints on the documentation so that the user can switch between endpoints. For example, This could be a test server and the live server.

```php
'servers' => [
   [
       'url' => 'https://test.app.com',
       'description' => 'DEV',
   ],
   [
       'url' => 'https://prod.app.com',
       'description' => 'LIVE.',
   ],
],
```

-   `security`
    This is where you specify authentication and authorization schemes, by default the HTTP authentication scheme using Bearer is setting but you can modify it, add others or even define it as null according to the requirements of your project. For more information, please visit [Swagger Authentication](https://swagger.io/docs/specification/authentication/).

```php
'security' => [
       'BearerAuth' => [
           'type' => 'http',
           'scheme' => 'bearer',
           'bearerFormat' => 'JWT',
       ],
   ],
```

It will generate documentation using your specified configuration.

## Documenting your API

This package uses these resources to generate the API documentation:

### Grouping endpoints

This package uses the HTTP controller doc blocks to create a table of contents and show descriptions for your API methods.

Using `@group` in a controller doc block creates a Group within the API documentation. All routes handled by that controller will be grouped under this group in the sidebar. The short description after the `@group` should be unique to allow anchor tags to navigate to this section. A longer description can be included below. Custom formatting and `<aside>` tags are also supported. (see the [Documentarian docs](http://marcelpociot.de/documentarian/installation/markdown_syntax))

> Note: using `@group` is optional. Ungrouped routes will be placed in a "general" group.

Above each method within the controller you wish to include in your API documentation you should have a doc block. This should include a unique short description as the first entry. An optional second entry can be added with further information. Both descriptions will appear in the API documentation in a different format as shown below.
You can also specify an `@group` on a single method to override the group defined at the controller level.

```php
/**
 * @group User management
 *
 * APIs for managing users
 */
class UserController extends Controller
{

	/**
	 * Create a user
	 *
	 * [Insert optional longer description of the API endpoint here.]
	 *
	 */
	 public function createUser()
	 {

	 }

	/**
	 * @group Account management
	 *
	 */
	 public function changePassword()
	 {

	 }
}
```

### Specifying request parameters

To specify a list of valid parameters your API route accepts, use the `@bodyParam`, `@queryParam` and `@pathParam` annotations.

-   The `@bodyParam` annotation takes the name of the parameter, its type, an optional "required" label, and then its description.
-   The `@queryParam` annotation takes the name of the parameter, an optional "required" label, and then its description
-   The `@pathParam` annotation takes the name of the parameter, an optional "required" label, and then its description
-   The `@defaultParam` annotation takes the default param
-   The `@requestBody` annotation to make request body type, default is `application/json`

```php

/**
 * @group Items
 */
class ItemController extends Controller
{

    /**
     * List items
     *
     * Get a list of items.
     *
     * @authenticated
     * @requestBody multipart/form-data
     * @responseFile responses/items.index.json
     * @defaultParam
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //...
    }

    /**
     * Store item
     *
     * Add a new item to the items collection.
     *
     * @bodyParam name string required
     * The name of the item. Example: Samsung Galaxy s10
     *
     * @bodyParam price number required
     * The price of the item. Example: 100.00
     *
     * @authenticated
     * @response {
     *      "status": 200,
     *      "success": true,
     *      "data": {
     *          "id": 10,
     *          "price": 100.00,
     *          "name": "Samsung Galaxy s10"
     *      }
     * }
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //...
    }


    /**
     * Get item
     *
     * Get item by it's unique ID.
     *
     * @pathParam item integer required
     * The ID of the item to retrieve. Example: 10
     *
     * @response {
     *      "status": 200,
     *      "success": true,
     *      "data": {
     *          "id": 10,
     *          "price": 100.00,
     *          "name": "Samsung Galaxy s10"
     *      }
     * }
     * @authenticated
     *
     * @param  \App\Item  $item
     * @return \Illuminate\Http\Response
     */
    public function show(Item $item)
    {
        //...
    }
```

Note: You can also add the `@bodyParam` annotations to a `\Illuminate\Foundation\Http\FormRequest` subclass:

```php
/**
 * @bodyParam title string required The title of the post.
 * @bodyParam body string required The title of the post.
 * @bodyParam type string The type of post to create. Defaults to 'textophonious'.
 * @bodyParam author_id int the ID of the author
 * @bodyParam thumbnail file This is required if the post type is 'imagelicious'.
 */
class MyRequest extends \Illuminate\Foundation\Http\FormRequest
{

}

public function createPost(MyRequest $request)
{
    // ...
}
```

### Indicating auth status

You can use the `@authenticated` annotation on a method to indicate if the endpoint is authenticated. A field for authentication token will be made available and marked as required on the interractive documentation.

### Providing an example response

You can provide an example response for a route. This will be displayed in the examples section. There are several ways of doing this.

#### @response

You can provide an example response for a route by using the `@response` annotation with valid JSON:

```php
/**
 * @response {
 *  "id": 4,
 *  "name": "Jessica Jones",
 *  "roles": ["admin"]
 * }
 */
public function show($id)
{
    return User::find($id);
}
```

Moreover, you can define multiple `@response` tags as well as the HTTP status code related to a particular response (if no status code set, `200` will be returned):

```php
/**
 * @response {
 *  "id": 4,
 *  "name": "Jessica Jones",
 *  "roles": ["admin"]
 * }
 * @response 404 {
 *  "message": "No query results for model [\App\User]"
 * }
 */
public function show($id)
{
    return User::findOrFail($id);
}
```

#### @transformer, @transformerCollection, and @transformerModel

You can define the transformer that is used for the result of the route using the `@transformer` tag (or `@transformerCollection` if the route returns a list). The package will attempt to generate an instance of the model to be transformed using the following steps, stopping at the first successful one:

1. Check if there is a `@transformerModel` tag to define the model being transformed. If there is none, use the class of the first parameter to the transformer's `transform()` method.
2. Get an instance of the model from the Eloquent model factory
3. If the parameter is an Eloquent model, load the first from the database.
4. Create an instance using `new`.

Finally, it will pass in the model to the transformer and display the result of that as the example response.

For example:

```php
/**
 * @transformercollection \App\Transformers\UserTransformer
 * @transformerModel \App\User
 */
public function listUsers()
{
    //...
}

/**
 * @transformer \App\Transformers\UserTransformer
 */
public function showUser(User $user)
{
    //...
}

/**
 * @transformer \App\Transformers\UserTransformer
 * @transformerModel \App\User
 */
public function showUser(int $id)
{
    // ...
}
```

For the first route above, this package will generate a set of two users then pass it through the transformer. For the last two, it will generate a single user and then pass it through the transformer.

> Note: for transformer support, you need to install the league/fractal package

```bash
composer require league/fractal
```

#### @responseFile

For large response bodies, you may want to use a dump of an actual response. You can put this response in a file (as a JSON string) within your Laravel storage directory and link to it. For instance, we can put this response in a file named `users.get.json` in `storage/responses`:

```
{"id":5,"name":"Jessica Jones","gender":"female"}
```

Then in your controller, link to it by:

```php
/**
 * @responseFile responses/users.get.json
 */
public function getUser(int $id)
{
  // ...
}
```

The package will parse this response and display in the examples for this route.

Similarly to `@response` tag, you can provide multiple `@responseFile` tags along with the HTTP status code of the response:

```php
/**
 * @responseFile responses/users.get.json
 * @responseFile 404 responses/model.not.found.json
 */
public function getUser(int $id)
{
  // ...
}
```
## You may also like...

-   [Laravel Api Documentation Generator](mpociot/laravel-apidoc-generator) - A laravel api documentation generator.

## License

MIT
