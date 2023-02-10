<?php
namespace Laililmahfud\ApDoc;

use App\Http\Controllers\Controller;

class ApDocGenerateController extends Controller{
       
    public function index(){
        (new ApDoc())->generate();
        return getdoc_file("/openapi-documentation.json");
    }
}