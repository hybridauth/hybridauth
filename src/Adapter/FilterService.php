<?php

namespace Hybridauth\Adapter;

class FilterService {
    /**
     *
     * @param int $type: One of INPUT_GET, INPUT_POST, INPUT_COOKIE, INPUT_SERVER, or INPUT_ENV
     * @param string $var_name: Name of a variable to get
     * @param int $filter: [optional] The ID of the filter to apply. The manual page lists the available filters.
     * @param mixed $options: Associative array of options or bitwise disjunction of flags. If filter accepts options, flags can be provided in "flags" field of array.
     *
     * @return mixed: Value of the requested variable on success, FALSE if the filter fails, or NULL if the variable_name variable is not set. If the flag FILTER_NULL_ON_FAILURE is used, it returns FALSE if the variable is not set and NULL if the filter fails.
     * https://php.net/manual/en/function.filter-input.php
     */
    public function filterInput($type, $var_name, $filter = FILTER_DEFAULT, $options = 0) {
        return filter_input($type, $var_name, $filter, $options);
    }
}
