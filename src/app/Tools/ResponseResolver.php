<?php
namespace Laililmahfud\ApDoc\Tools;


use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Response;
use Laililmahfud\ApDoc\Tools\ResponseStrategies\ResponseTagStrategy;
use Laililmahfud\ApDoc\Tools\ResponseStrategies\ResponseCallStrategy;
use Laililmahfud\ApDoc\Tools\ResponseStrategies\ResponseFileStrategy;
use Laililmahfud\ApDoc\Tools\ResponseStrategies\TransformerTagsStrategy;

class ResponseResolver
{
    /**
     * @var array
     */
    public static $strategies = [
        ResponseTagStrategy::class,
        TransformerTagsStrategy::class,
        ResponseFileStrategy::class,
    ];

    /**
     * @var Route
     */
    private $route;

    /**
     * @param Route $route
     */
    public function __construct(Route $route)
    {
        $this->route = $route;
    }


     /**
     * @param array $tags
     * @param array $routeProps
     *
     * @return array|null
     */
    private function resolve(array $tags, array $routeProps)
    {
        $resolves = [];
        foreach (static::$strategies as $strategy) {
            $strategy = new $strategy();
            foreach($tags as $tag){
                /** @var Response[]|null $response */
                $responses = $strategy($this->route, [$tag], $routeProps);

                if (!is_null($responses)) {
                    $resolve =  array_map(function (Response $response) {
                        return ['status' => $response->getStatusCode(), 'content' => $this->getResponseContent($response)];
                    }, $responses);
                    $resolves = array_merge($resolves,$resolve);
                }
            }
        }
        return $resolves;
    }

    /**
     * @param $route
     * @param $tags
     * @param $routeProps
     *
     * @return array
     */
    public static function getResponse($route, $tags, $routeProps)
    {
        return (new static($route))->resolve($tags, $routeProps);
    }

    /**
     * @param $response
     *
     * @return mixed
     */
    private function getResponseContent($response)
    {
        return $response ? $response->getContent() : '';
    }
}
