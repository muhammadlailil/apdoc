<?php
namespace Laililmahfud\ApDoc;

use App\Http\Controllers\Controller;
use Laililmahfud\ApDoc\Tools\RouteMatcher;

class ApDocGenerateController extends Controller{
       
    public function index(){
        $apdoc = new ApDoc();
        $routes = $apdoc->generate();
        return getdoc_file("/openapi-documentation.json");
    }
}