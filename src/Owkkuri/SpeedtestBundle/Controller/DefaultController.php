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

        //Get the POST/GET search param
        $term = $request->get('search','*:*');

        //If the term is empty, default to search all
        if(empty($term)) {
            $term = '*:*';
        }

        //Magic starts here
        $client = $this->get('solarium.client');
        // get a select query instance
        $query = $client->createSelect();

        //Enable edismax, more natural searching
        $edismax = $query->getEDisMax();
        $edismax->setQueryFields('name_s cc_s sponsor_s country_s');


        //Set the term we're looking for, defaults to '*:*'
        $query->setQuery($term);

        //Set the limit
        $query->setRows(5);

        //Create a facet set for the left nav
        $facetSet = $query->getFacetSet();

        $facetSet->createFacetField('countrycode')->setField('cc_s');
        $facetSet->createFacetField('sponsor')->setField('sponsor_s');
        $facetSet->setLimit(10);

        //exclude 0 facets1
        $facetSet->setMinCount(1);

        //Get the result set
        $resultset = $client->select($query);

        $docs = array();
        $facets = array();

        $facet = $resultset->getFacetSet()->getFacet('countrycode');
        foreach ($facet as $value => $count) {
            $facets[$value]['count'] = $count;
            $facets[$value]['label'] = $value;
        }

        $morefacets = array();
//        $morefacet = $resultset->getFacetSet()->getFacet('sponsor');
//        foreach ($morefacet as $value => $count) {
//            $morefacets[$value]['count'] = $count;
//            $morefacets[$value]['label'] = $value;
//        }

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


        return array('docs' => $docs, 'numfound' => $resultset->getNumFound(), 'facets' => $facets, 'morefacets' => $morefacets);
    }
}
