<?php
namespace Laililmahfud\ApDoc;

use Laililmahfud\ApDoc\ApDocGenerator;
use Laililmahfud\ApDoc\Tools\RouteMatcher;
use Illuminate\Routing\Route;
use ReflectionClass;
use ReflectionException;
use Mpociot\Reflection\DocBlock;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;


class ApDoc{
    
    public function __construct(
        private $routeMatcher = new RouteMatcher
    )
    {
    }

    public function generate(){
        $routes = $this->routeMatcher->getRoutesToBeDocumented();
        $generator = new ApDocGenerator();

        $parsedRoutes = $this->processRoutes($generator, $routes);
        $sortedRoute = [];
        foreach ($parsedRoutes as $key => $val)
        {
            $sortedRoute[$key] = $val['sorting'];
        }
        array_multisort($sortedRoute, SORT_ASC, $parsedRoutes);

        $parsedRoutes = collect($parsedRoutes)->groupBy('group');

        $this->writeMarkdown($parsedRoutes);
    }
    /**
     * @param  Collection $parsedRoutes
     *
     * @return void
     */
    private function writeMarkdown($parsedRoutes)
    {
        $outputPath = storage_path(config('apdoc.output','api-docs'));

        if (!File::isDirectory($outputPath)) {
            File::makeDirectory($outputPath, 0777, true, true);
        }

        // $this->info('Generating OPEN API 3.0.0 Config');
        file_put_contents($outputPath . DIRECTORY_SEPARATOR . 'openapi-documentation.json', $this->generateOpenApi3Config($parsedRoutes));
    }

    /**
     * @param ApDocGenerator $generator
     * @param array $routes
     *
     * @return array
     */
    private function processRoutes(ApDocGenerator $generator, array $routes)
    {
        $parsedRoutes = [];
        foreach ($routes as $routeItem) {
            $route = $routeItem['route'];
            /** @var Route $route */
            if ($this->isValidRoute($route) && $this->isRouteVisibleForDocumentation($route->getAction()['uses'])) {
                $parsedRoutes[] = $generator->processRoute($route, $routeItem['apply']);
                // $this->info('Processed route: [' . implode(',', $generator->getMethods($route)) . '] ' . $generator->getUri($route));
            } else {
                // $this->warn('Skipping route: [' . implode(',', $generator->getMethods($route)) . '] ' . $generator->getUri($route));
            }
        }

        return $parsedRoutes;
    }

    /**
     * @param $route
     *
     * @return bool
     */
    private function isValidRoute(Route $route)
    {
        return !is_callable($route->getAction()['uses']) && !is_null($route->getAction()['uses']);
    }

    /**
     * @param $route
     *
     * @throws ReflectionException
     *
     * @return bool
     */
    private function isRouteVisibleForDocumentation($route)
    {
        list($class, $method) = explode('@', $route);
        $reflection = new ReflectionClass($class);

        if (!$reflection->hasMethod($method)) {
            return false;
        }

        $comment = $reflection->getMethod($method)->getDocComment();

        if ($comment) {
            $phpdoc = new DocBlock($comment);

            return collect($phpdoc->getTags())
                ->filter(function ($tag) use ($route) {
                    return $tag->getName() === 'hideFromApiDocumentation';
                })
                ->isEmpty();
        }

        return true;
    }

    /**
     * Generate Open API 3.0.0 collection json file.
     *
     * @param Collection $routes
     *
     * @return string
     */
    private function generateOpenApi3Config(Collection $routes)
    {
        $result = $routes->map(function ($routeGroup, $groupName) use ($routes) {

            return $routeGroup->sortBy('sorting')->sortBy('title')->map(function ($route) use ($groupName, $routes, $routeGroup) {

                $methodGroup = $routeGroup->where('uri', $route['uri'])->mapWithKeys(function ($route) use ($groupName, $routes) {

                    $bodyParameters = collect($route['bodyParameters'])->map(function ($schema, $name) use ($routes) {

                        $type = $schema['type'];
                        $default = $schema['value'];

                        if ($type === 'float') {
                            $type = 'number';
                        }

                        if ($type === 'json' && $default) {
                            $type = 'object';
                            $default = json_decode($default);
                        }

                        return [
                            'in' => 'formData',
                            'name' => $name,
                            'description' => $schema['description'],
                            'properties' => @$schema['properties'],
                            'properties_required' => @$schema['properties_required'],
                            'required' => $schema['required'],
                            'type' => $type,
                            'default' => $default,
                        ];
                    });

                    $jsonParameters = [
                        $route['requestBody'] => [
                            'schema' => [
                                'type' => 'object',
                            ]
                             + (
                                count($required = $bodyParameters
                                        ->values()
                                        ->where('required', true)
                                        ->pluck('name'))
                                ? ['required' => $required]
                                : []
                            )

                             + (
                                count($properties = $bodyParameters
                                        ->values()
                                        ->filter()
                                        ->mapWithKeys(function ($parameter) use ($routes) {
                                            $param = [
                                                $parameter['name'] => [
                                                    'type' => ($parameter['type']=='file')?'string':$parameter['type'],
                                                    'description' => $parameter['description'],
                                                    'properties' => @$parameter['properties']?? [],
                                                    'required' => @$parameter['properties_required']?? [],
                                                ]
                                            ];
                                            if($parameter['default']){
                                                $param[$parameter['name']]['example'] = $parameter['default'];
                                            }
                                            if($parameter['type']=='file'){
                                                $param[$parameter['name']]['format'] = "binary";
                                            }
                                            return $param;
                                        }))
                                ? ['properties' => $properties]
                                : []
                            )

                            //  + (
                            //     count($properties = $bodyParameters
                            //             ->values()
                            //             ->filter()
                            //             ->mapWithKeys(
                            //                 function ($parameter) {
                            //                     return [$parameter['name'] => $parameter['default']];
                            //                 }
                            //             ))
                            //     ? ['example' => $properties]
                            //     : []
                            // )
                        ],
                    ];

                    $queryParameters = collect($route['queryParameters'])->map(function ($schema, $name) {
                        $queryReturn = [
                            'in' => 'query',
                            'name' => $name,
                            'description' => $schema['description'],
                            'required' => $schema['required'],
                            'schema' => [
                                'type' => $schema['type'],
                            ],
                        ];
                        if($schema['value']){
                            $queryReturn['schema']['example'] = $schema['value'];
                        }
                        return $queryReturn;
                    });

                    $pathParameters = collect($route['pathParameters'] ?? [])->map(function ($schema, $name) use ($route) {
                        $pathParam = [
                            'in' => 'path',
                            'name' => $name,
                            'description' => $schema['description'],
                            'required' => $schema['required'],
                            'schema' => [
                                'type' => $schema['type'],
                            ],
                        ];
                        if($schema['value']){
                            $pathParam['schema']['example'] = $schema['value'];
                        }
                        return $pathParam;
                    });

                    $headerParameters = collect($route['headers'])->map(function ($schema, $header) use ($route) {

                        if ($header === 'Authorization') {
                            return;
                        }

                        $headerParam = [
                            'in' => 'header',
                            'name' => $header,
                            'description' => @$schema['description'],
                            'required' => true,
                            'schema' => [
                                'type' => $schema['type'],
                            ],
                        ];
                        if($schema['default']){
                            $headerParam['schema']['default'] = $schema['default'];
                        }
                        return $headerParam;
                    });
               

                    $response = [];
                    if(count($route['response'] ?? [])){
                        foreach($route['response'] as $resp){
                            $response[$resp['status']] = [
                                'description' => (string) $resp['status'],
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                            'example' => json_decode($resp['content'], true),
                                        ],
                                    ],
                                ]
                            ];
                        }
                    }
                 
                    return [
                        strtolower($route['methods'][0]) => (

                            (
                                $route['authenticated']
                                ? ['security' => [
                                   collect(config('apdoc.security'))->map(function () {
                                    return [];
                                }),
                                ]]
                                : []
                            )

                             + ([
                                "tags" => [
                                    $groupName,
                                ],
                                'summary' => $route['title'],
                                'operationId' => str()->slug($route['title']).'-'.str()->ulid(),
                                'description' => $route['description'],
                             ]) +

                            (
                                count(array_intersect(['POST', 'PUT', 'PATCH'], $route['methods']))
                                ? ['requestBody' => [
                                    'description' => "",
                                    'required' => true,
                                    'content' => collect($jsonParameters)->filter()->toArray(),
                                ]]
                                : []
                            ) +

                            [
                                'parameters' => (

                                    array_merge(
                                        array_merge(
                                            collect($queryParameters->values()->toArray())
                                                ->filter()
                                                ->toArray(),
                                            collect($pathParameters->values()->toArray())
                                                ->filter()
                                                ->toArray()
                                        ) ,
                                        collect($headerParameters->values()->toArray())
                                        ->filter()
                                        ->values()
                                        ->toArray()
                                    )
                                ),

                                'responses' => $response,
                            ]
                        ),
                    ];
                });

                return collect([
                    ('/' . $route['uri']) => $methodGroup,
                ]);
            })->values();
        });

        $paths = [];

        foreach ($result->filter()->toArray() as $groupName => $group) {
            foreach ($group as $key => $value) {
                $paths[key($value)] = $value[key($value)];
            }
        }

        $description = "";
        $descriptionCode = null;
        $descriptionError = null;
        foreach(config('apdoc.api.response_code',[]) as $code => $value){
            $descriptionCode .= "<tr><td>{$code}</td><td>: {$value}</td></tr>";
        }
        foreach(config('apdoc.api.response_error',[]) as $type => $desc){
            $descriptionError .= "<tr><td>{$type}</td><td>: {$desc}</td></tr>";
        }
        if($descriptionCode || $descriptionError){
            $description .= '<br><div class="table-response-detail">';
            if($descriptionCode){
                $description .= "<table><tr><th><p><b>API Response Code</b></p></th>{$descriptionCode}</tr>";
            }
            if($descriptionError){
                $description .= "<table><tr><th><p><b>API Response Code</b></p></th>{$descriptionError}</tr>";
            }
        }
        $overview_information_view = config('apdoc.api.overview_information_view');
        $description .= $overview_information_view ? file_get_contents(resource_path("views/{$overview_information_view}")) : '';

        $collection = [

            'openapi' => '3.0.0',

            'info' => [
                'title' => config('apdoc.info.title'),
                'version' => config('apdoc.info.version'),
                'description' => config('apdoc.info.description').$description,
                'termsOfService' => config('apdoc.terms_of_service'),
                "license" =>  !empty(config('apdoc.license')) ? config('apdoc.license') : null,
                "contact" =>  config('apdoc.contact'), 
            ],

            'components' => [

                'securitySchemes' => config('apdoc.security'),

                'schemas' => $routes->mapWithKeys(function ($routeGroup, $groupName) {

                    if ($groupName != 'Payment processors') {
                        return [];
                    }

                    return collect($routeGroup)->mapWithKeys(function ($route) use ($groupName, $routeGroup) {

                        $bodyParameters = collect($route['bodyParameters'])->map(function ($schema, $name) {

                            $type = $schema['type'];

                            if ($type === 'float') {
                                $type = 'number';
                            }

                            if ($type === 'json') {
                                $type = 'object';
                            }

                            return [
                                'in' => 'formData',
                                'name' => $name,
                                'description' => $schema['description'],
                                'required' => $schema['required'],
                                'type' => $type,
                                'default' => $schema['value'],
                            ];
                        });

                        return ["PM{$route['paymentMethod']->id}" => ['type' => 'object']

                             + (
                                count($required = $bodyParameters
                                        ->values()
                                        ->where('required', true)
                                        ->pluck('name'))
                                ? ['required' => $required]
                                : []
                            )

                             + (
                                count($properties = $bodyParameters
                                        ->values()
                                        ->filter()
                                        ->mapWithKeys(function ($parameter) {
                                            return [
                                                $parameter['name'] => [
                                                    'type' => $parameter['type'],
                                                    'example' => $parameter['default'],
                                                    'description' => $parameter['description'],
                                                ],
                                            ];
                                        }))
                                ? ['properties' => $properties]
                                : []
                            )

                             + (
                                count($properties = $bodyParameters
                                        ->values()
                                        ->filter()
                                        ->mapWithKeys(function ($parameter) {
                                            return [$parameter['name'] => $parameter['default']];
                                        }))
                                ? ['example' => $properties]
                                : []
                            )
                        ];
                    });
                })->filter(),
            ],

            'servers' => config('apdoc.servers'),

            'paths' => $paths,

            'x-tagGroups' => config('apdoc.tag_groups'),
        ];

        return json_encode($collection);
    }
}