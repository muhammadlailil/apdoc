<?php
namespace Laililmahfud\ApDoc\Tools;

use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;

class RouteMatcher
{
    public function getRoutesToBeDocumented()
    {
        $matchedRoutes = [];
        $allRoutes = $this->getAllRoutes();
        foreach ($allRoutes as $route) {
            $matchedRoutes[] = [
                'route' => $route,
                'apply' => config('apdoc.api.apply') ?: []
            ];
        }
        return $matchedRoutes;
    }

    private function getAllRoutes(): Collection
    {
        return collect(RouteFacade::getRoutes())
            ->filter(function (Route $route) {
                $routeResolver = function (Route $route) {
                    $expectedDomain = config('apdoc.domain');
                    return Str::startsWith($route->uri, config('apdoc.api.path')) 
                    && (!$expectedDomain || $route->getDomain() === $expectedDomain)
                    && !in_array($route->uri,config('apdoc.api.exclude',[]));
                };
                return $routeResolver($route);
            })
            ->filter(fn(Route $r) => $r->getAction('controller'))
            ->values();
    }
}
