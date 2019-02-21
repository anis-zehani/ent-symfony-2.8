<?php

namespace PronoteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('PronoteBundle:Default:index.html.twig');

    }
}
