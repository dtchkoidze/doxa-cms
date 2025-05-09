<?php

namespace Doxa\Core\Libraries\Datagrid\Enums;

enum ColumnTypeEnum: string
{
    /**
     * String.
     */
    case STRING = 'string';

    /**
     * Integer.
     */
    case INTEGER = 'integer';

    /**
     * Boolean.
     */
    case BOOLEAN = 'boolean';

    /**
     * Dropdown.
     */
    case DROPDOWN = 'dropdown';

    /**
     * Date range.
     */
    case DATE_RANGE = 'date_range';

    /**
     * Date time range.
     */
    case DATE_TIME_RANGE = 'datetime_range';

    case RELATED = 'related';

    // case DATE = 'date';

    // case DATETIME = 'datetime';
}
