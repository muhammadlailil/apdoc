<?php
namespace Laililmahfud\ApDoc;

use App\Http\Controllers\Controller;

class ApDocGenerateController extends Controller{
       
    public function __construct(
        private $apDoc = new ApDoc
    )
    {
    }
    public function index(){
        $this->apDoc->generate();
        return getdoc_file("/openapi-documentation.json");
    }
}