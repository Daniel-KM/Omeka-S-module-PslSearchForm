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

namespace PslSearchForm\FormAdapter;

use Search\FormAdapter\FormAdapterInterface;
use Search\FormAdapter\TraitUnrestrictedQuery;

class PslFormAdapter implements FormAdapterInterface
{
    use TraitUnrestrictedQuery;

    public function getLabel()
    {
        return 'PSL';
    }

    public function getFormClass()
    {
        return  \PslSearchForm\Form\PslForm::class;
    }

    public function getFormPartialHeaders()
    {
        return 'search/search-form-psl-headers';
    }

    public function getFormPartial()
    {
        return 'search/search-form-psl';
    }

    public function getConfigFormClass()
    {
        return \PslSearchForm\Form\Admin\PslFormConfigFieldset::class;
    }

    public function toQuery(array $request, array $formSettings)
    {
        // Only fields that are present on the form are used.
        // But this form has more fields than the standard form.
        $query = $this->toUnrestrictedQuery($request, $formSettings);

        if (!empty($request['map']['spatial-coverage']) && !empty($formSettings['spatial_coverage_field'])) {
            $query->addFilter($formSettings['spatial_coverage_field'], $request['map']['spatial-coverage']);
        }

        if (!empty($formSettings['date_range_field']) &&
            (isset($request['date']['from']) || isset($request['date']['to']))
        ) {
            $start = $request['date']['from'];
            $end = $request['date']['to'];
            if ($start || $end) {
                $query->addDateRangeFilter($formSettings['date_range_field'], $start, $end);
            }
        }

        if (!empty($formSettings['creation_year_field'])
            && !empty($request['text']['creation-year'])
        ) {
            $query->addFilter($formSettings['creation_year_field'], $request['text']['creation-year']);
        }

        return $query;
    }
}
