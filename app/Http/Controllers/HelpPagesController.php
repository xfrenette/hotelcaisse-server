<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class HelpPagesController extends Controller
{
    public function index()
    {
        return $this->page('index');
    }

    public function page($page)
    {
        $path = 'help.' . $page;

        if (!view()->exists($path)) {
            throw new NotFoundHttpException();
        }

        return view('help.' . $page);
    }
}
