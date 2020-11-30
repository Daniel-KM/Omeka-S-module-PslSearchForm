<?php declare(strict_types=1);

/*
 * Copyright BibLibre, 2016
 * Copyright Daniel Berthereau 2018
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

namespace PslSearchForm\Form\Admin;

use Laminas\Form\Element;
use Laminas\Form\Fieldset;
use Search\Query;

class PslFormConfigFieldset extends Fieldset
{
    public function init(): void
    {
        $this->add($this->getAdvancedFieldsFieldset());

        $fieldOptions = $this->getFieldsOptions();

        $this
            ->add([
                'name' => 'is_public_field',
                'type' => Element\Select::class,
                'options' => [
                    'label' => 'Is Public field', // @translate
                    'value_options' => $fieldOptions,
                    'empty_option' => 'None', // @translate
                ],
                'attributes' => [
                    'required' => false,
                    'class' => 'chosen-select',
                ],
            ])
            ->add([
                'name' => 'date_range_field',
                'type' => Element\Select::class, // @translate
                'options' => [
                    'label' => 'Date range field', // @translate
                    'value_options' => $fieldOptions,
                    'empty_option' => 'None', // @translate
                ],
                'attributes' => [
                    'required' => false,
                    'class' => 'chosen-select',
                ],
            ])
            ->add([
                'name' => 'item_set_id_field',
                'type' => Element\Select::class,
                'options' => [
                    'label' => 'Item set id field', // @translate
                    'value_options' => $fieldOptions,
                    'empty_option' => 'None', // @translate
                ],
                'attributes' => [
                    'required' => false,
                    'class' => 'chosen-select',
                ],
            ])
            ->add([
                'name' => 'creation_year_field',
                'type' => Element\Select::class,
                'options' => [
                    'label' => 'Creation year field', // @translate
                    'value_options' => $fieldOptions,
                    'empty_option' => 'None', // @translate
                ],
                'attributes' => [
                    'required' => false,
                    'class' => 'chosen-select',
                ],
            ])
            ->add([
                'name' => 'spatial_coverage_field',
                'type' => Element\Select::class,
                'options' => [
                    'label' => 'Spatial coverage field', // @translate
                    'value_options' => $fieldOptions,
                    'empty_option' => 'None', // @translate
                ],
                'attributes' => [
                    'required' => false,
                    'class' => 'chosen-select',
                ],
            ])
            ->add(
                $this->getLocationsFieldset()
            )
            ->add([
                'name' => 'filter_value_joiner',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Add the joiner ("and" or "or") to the filters', // @translate
                ],
            ])
            ->add([
                'name' => 'filter_value_type',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Add the type ("equal", "in", etc.) to the filters', // @translate
                ],
            ])
        ;
    }

    protected function getAdvancedFieldsFieldset()
    {
        $advancedFieldsFieldset = new Fieldset('advanced-fields');
        $advancedFieldsFieldset->setLabel('Advanced search fields'); // @translate
        $advancedFieldsFieldset->setAttribute('data-sortable', '1');
        $advancedFieldsFieldset->setAttribute('data-ordered', '0');

        $fields = $this->getAvailableFields();
        $weights = range(0, count($fields));
        $weight_options = array_combine($weights, $weights);
        $weight = 0;
        foreach ($fields as $field) {
            $fieldset = new Fieldset($field['name']);
            $fieldset->setLabel($this->getFieldLabel($field));

            $displayFieldset = new Fieldset('display');
            $displayFieldset->add([
                'name' => 'label',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Label', // @translate
                ],
            ]);
            $fieldset->add($displayFieldset);

            $fieldset->add([
                'name' => 'enabled',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Enabled', // @translate
                ],
            ]);

            $fieldset->add([
                'name' => 'weight',
                'type' => Element\Select::class,
                'options' => [
                    'label' => 'Weight', // @translate
                    'value_options' => $weight_options,
                ],
                'attributes' => [
                    'value' => $weight++,
                ],
            ]);

            $advancedFieldsFieldset->add($fieldset);
        }

        return $advancedFieldsFieldset;
    }

    protected function getAvailableFields()
    {
        $searchPage = $this->getOption('search_page');
        $searchAdapter = $searchPage->index()->adapter();
        return $searchAdapter->getAvailableFields($searchPage->index());
    }

    protected function getFieldsOptions()
    {
        $options = [];
        foreach ($this->getAvailableFields() as $name => $field) {
            if (isset($field['label'])) {
                $options[$name] = sprintf('%s (%s)', $field['label'], $name);
            } else {
                $options[$name] = $name;
            }
        }
        return $options;
    }

    protected function getLocationsFieldset()
    {
        $fieldset = new Fieldset('locations');

        $locations = $this->getLocations();
        if (empty($locations)) {
            $fieldset->setLabel('Locations (none found)'); // @translate
        } else {
            $fieldset->setLabel('Locations'); // @translate
            foreach ($locations as $location) {
                $fieldset->add([
                    'name' => $location,
                    'type' => Element\Text::class,
                    'options' => [
                        'label' => $location,
                    ],
                    'attributes' => [
                        'placeholder' => 'Latitude, Longitude', // @translate
                    ],
                ]);
            }
        }

        return $fieldset;
    }

    protected function getLocations()
    {
        /** @var \Search\Api\Representation\SearchPageRepresentation $searchPage */
        $searchPage = $this->getOption('search_page');
        $searchQuerier = $searchPage->index()->querier();
        $settings = $searchPage->settings();
        $spatialCoverageField = $settings['form']['spatial_coverage_field']
            ?? '';

        $locations = [];
        if ($spatialCoverageField) {
            $query = new Query;
            $query->setQuery('*');
            $query->setResources(['items']);
            $query->addFacetField($spatialCoverageField);

            $response = $searchQuerier->setQuery($query)->query();

            $facetCounts = $response->getFacetCounts();
            if (isset($facetCounts[$spatialCoverageField])) {
                foreach ($facetCounts[$spatialCoverageField] as $facetCount) {
                    $locations[] = $facetCount['value'];
                }
            }
        }

        return $locations;
    }

    protected function getFieldLabel($field)
    {
        $searchPage = $this->getOption('search_page');
        $settings = $searchPage->settings();

        $name = $field['name'];
        $label = $field['label'] ?? null;
        if (isset($settings['form']['advanced-fields'][$name])) {
            $fieldSettings = $settings['form']['advanced-fields'][$name];

            if (isset($fieldSettings['display']['label'])
                && $fieldSettings['display']['label']) {
                $label = $fieldSettings['display']['label'];
            }
        }
        $label = $label ? sprintf('%s (%s)', $label, $field['name']) : $field['name'];

        return $label;
    }
}
