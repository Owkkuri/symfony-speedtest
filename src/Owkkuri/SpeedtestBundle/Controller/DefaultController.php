<?php

namespace Owkkuri\SpeedtestBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Solarium\Client;

class DefaultController extends Controller
{
    /**
     * @Route("/")
     * @Template()
     * @param Request $request
     * @return array
     */
    public function indexAction(Request $request)
    {
        /* @var $client Client */

        $term = $request->get('search','*:*');

        if(empty($term)) {
            $term = '*:*';
        }

        $client = $this->get('solarium.client');
        // get a select query instance
        $query = $client->createSelect();

        $edismax = $query->getEDisMax();
        $edismax->setQueryFields('name_s cc_s sponsor_s country_s');



        $query->setQuery($term);
        $query->setRows(5);

        $facetSet = $query->getFacetSet();

        $facetSet->createFacetField('countrycode')->setField('cc_s');
        $facetSet->setLimit(10);
        $facetSet->setMinCount(1);

        $resultset = $client->select($query);

        $docs = array();
        $facets = array();

        $facet = $resultset->getFacetSet()->getFacet('countrycode');
        foreach ($facet as $value => $count) {
            $facets[$value]['count'] = $count;
            $facets[$value]['label'] = $value;
        }

        foreach ($resultset as $document) {

            $doc = array();

            // the documents are also iterable, to get all fields
            foreach ($document as $field => $value) {
                // this converts multivalue fields to a comma-separated string
                if (is_array($value)) {
                    $value = implode(', ', $value);
                }

                $doc[$field] = $value;
            }

            $docs[] = $doc;
        }


        return array('docs' => $docs, 'numfound' => $resultset->getNumFound(), 'facets' => $facets);
    }
}
