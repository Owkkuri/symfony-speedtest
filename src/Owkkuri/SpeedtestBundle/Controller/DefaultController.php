<?php

namespace Owkkuri\SpeedtestBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class DefaultController extends Controller
{
    /**
     * @Route("/")
     * @Template()
     */
    public function indexAction($name = 'rawr')
    {
        $client = $this->get('solarium.client');
        $select = $client->createSelect();
        $select->setQuery('*:*');
        $results = $client->select($select);

        $flash = $this->get('braincrafted_bootstrap.flash');
        $flash->alert('This is an alert flash message.');
        $flash->error('This is an error flash message.');
        $flash->info('This is an info flash message.');
        $flash->success('This is an success flash message.');


        return array('name' => $name);
    }
}
