<?php declare(strict_types=1);

/*
 * Copyright BibLibre, 2016
 * Copyright Daniel Berthereau 2018-2020
 *
 * This software is governed by the CeCILL license under French law and abiding
 * by the rules of distribution of free software.  You can use, modify and/ or
 * redistribute the software under the terms of the CeCILL license as circulated
 * by CEA, CNRS and INRIA at the following URL "http://www.cecill.info".
 *
 * As a counterpart to the access to the source code and rights to copy, modify
 * and redistribute granted by the license, users are provided only with a
 * limited warranty and the software's author, the holder of the economic
 * rights, and the successive licensors have only limited liability.
 *
 * In this respect, the user's attention is drawn to the risks associated with
 * loading, using, modifying and/or developing or reproducing the software by
 * the user in light of its specific status of free software, that may mean that
 * it is complicated to manipulate, and that also therefore means that it is
 * reserved for developers and experienced professionals having in-depth
 * computer knowledge. Users are therefore encouraged to load and test the
 * software's suitability as regards their requirements in conditions enabling
 * the security of their systems and/or data to be ensured and, more generally,
 * to use and operate it in the same conditions as regards security.
 *
 * The fact that you are presently reading this means that you have had
 * knowledge of the CeCILL license and that you accept its terms.
 */

namespace PslSearchForm\Form;

use Laminas\Form\Element;
use Laminas\Form\Fieldset;
use Laminas\Form\Form;
use Laminas\Log\Logger;
use Omeka\Api\Manager;
use Omeka\Api\Representation\SiteRepresentation;
use Search\Querier\Exception\QuerierException;
use Search\Query;

class PslForm extends Form
{
    /**
     * @var Manager
     */
    protected $apiManager;

    /**
     * @var SiteRepresentation
     */
    protected $site;

    /**
     * @var Logger
     */
    protected $logger;

    protected $formElementManager;

    public function init(): void
    {
        // Omeka adds a csrf automatically in \Omeka\Form\Initializer\Csrf.
        // Remove the csrf, because it is useless for a search form and the url
        // is not copiable (see the default search form that doesn't use it).
        foreach ($this->getElements() as $element) {
            $name = $element->getName();
            if (substr($name, -4) === 'csrf') {
                $this->remove($name);
                break;
            }
        }

        $this
            ->add([
                'name' => 'q',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Search', // @translate
                ],
                'attributes' => [
                    'placeholder' => 'Search', // @translate
                ],
            ])

            ->add($this->mapFieldset())
            ->add($this->dateFieldset())
            ->add($this->itemSetFieldset())
            ->add($this->textFieldset())

            ->add([
                'name' => 'submit',
                'type' => Element\Submit::class,
                'attributes' => [
                    'value' => 'Submit', // @translate
                    'type' => 'submit',
                ],
            ])
        ;

        $this->getInputFilter()
            ->get('itemSet')->add([
                'name' => 'ids',
                'required' => false,
            ])
        ;
    }

    public function getLocations()
    {
        $searchPage = $this->getOption('search_page');
        $settings = $searchPage->settings();
        $formSettings = $settings['form'];
        if (empty($formSettings['spatial_coverage_field']) || empty($formSettings['locations'])) {
            return [];
        }
        $spatialCoverageField = $formSettings['spatial_coverage_field'];
        $locations = $formSettings['locations'];

        $searchQuerier = $searchPage->index()->querier();

        $query = new Query;
        $query->setResources(['items']);
        $query->addFacetField($spatialCoverageField);

        $locationsOut = [];
        try {
            $response = $searchQuerier->query($query);
            $facetCounts = $response->getFacetCounts();
            if (isset($facetCounts[$spatialCoverageField])) {
                foreach ($facetCounts[$spatialCoverageField] as $facetCount) {
                    $name = $facetCount['value'];
                    if (isset($locations[$name])) {
                        $locationsOut[$name] = [
                            'coords' => $locations[$name],
                            'count' => $facetCount['count'],
                        ];
                    }
                }
            }
        } catch (QuerierException $e) {
            $this->getLogger()->err($e->getMessage());
        }

        return $locationsOut;
    }

    protected function mapFieldset()
    {
        $fieldset = new Fieldset('map');

        $fieldset->add([
            'name' => 'spatial-coverage',
            'type' => Element\Hidden::class,
        ]);

        return $fieldset;
    }

    protected function dateFieldset()
    {
        $fieldset = new Fieldset('date');
        $fieldset->setLabel('date'); // @translate

        $fieldset->add([
            'name' => 'from',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'From year', // @translate
            ],
            'attributes' => [
                'placeholder' => 'YYYY', // @translate
            ],
        ]);

        $fieldset->add([
            'name' => 'to',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'To year', // @translate
            ],
            'attributes' => [
                'placeholder' => 'YYYY', // @translate
            ],
        ]);

        return $fieldset;
    }

    protected function itemSetFieldset()
    {
        $fieldset = new Fieldset('itemSet');

        $fieldset->add([
            'name' => 'ids',
            'type' => Element\MultiCheckbox::class,
            'options' => [
                'label' => 'Collections', // @translate
                'value_options' => $this->getItemSetsOptions(),
            ],
        ]);

        return $fieldset;
    }

    protected function textFieldset()
    {
        $fieldset = new Fieldset('text');

        $filterFieldset = $this->getFilterFieldset();
        if ($filterFieldset->count()) {
            $fieldset->add([
                'name' => 'filters',
                'type' => Element\Collection::class,
                'options' => [
                    'label' => 'Filters', // @translate
                    'count' => 2,
                    'should_create_template' => true,
                    'allow_add' => true,
                    'target_element' => $filterFieldset,
                    'required' => false,
                ],
            ]);
        }

        $fieldset->add([
            'name' => 'creation-year',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'Creation year', // @translate
            ],
            'attributes' => [
                'placeholder' => 'YYYY', // @translate
            ],
        ]);

        return $fieldset;
    }

    protected function getItemSetsOptions()
    {
        $site = $this->getSite();
        if (empty($site)) {
            $itemSets = $this->getApiManager()->search('item_sets')->getContent();
        } else {
            // The site item sets may be public of private in Omeka 2.0, so it's
            // not possible currently to use $site->siteItemSets().
            $itemSets = $this->getApiManager()->search('item_sets', ['site_id' => $site->id()])->getContent();
        }
        // TODO Update for Omeka 2 to avoid to load full resources (title).
        $options = [];
        /** @var \Omeka\Api\Representation\ItemSetRepresentation[] $itemSets */
        foreach ($itemSets as $itemSet) {
            $options[$itemSet->id()] = (string) $itemSet->displayTitle();
        }
        return $options;
    }

    protected function getFilterFieldset()
    {
        $options = $this->getOptions();
        return $this->getForm(FilterFieldset::class, $options);
    }

    protected function getForm($name, $options)
    {
        return $this->getFormElementManager()
            ->get($name, $options);
    }

    /**
     * @param Manager $apiManager
     * @return self
     */
    public function setApiManager(Manager $apiManager)
    {
        $this->apiManager = $apiManager;
        return $this;
    }

    /**
     * @return \Omeka\Api\Manager
     */
    public function getApiManager()
    {
        return $this->apiManager;
    }

    /**
     * @param SiteRepresentation $site
     * @return \PslSearchForm\Form\PslForm
     */
    public function setSite(SiteRepresentation $site = null)
    {
        $this->site = $site;
        return $this;
    }

    /**
     * @return \Omeka\Api\Representation\SiteRepresentation
     */
    public function getSite()
    {
        return $this->site;
    }

    /**
     * @param Logger $logger
     * @return self
     */
    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * @return Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param Object $formElementManager
     * @return \PslSearchForm\Form\PslForm
     */
    public function setFormElementManager($formElementManager)
    {
        $this->formElementManager = $formElementManager;
        return $this;
    }

    public function getFormElementManager()
    {
        return $this->formElementManager;
    }
}
