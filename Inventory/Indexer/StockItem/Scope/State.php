<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Inventory\Indexer\StockItem\Scope;

/**
 * This class represents state that defines which table should be used during indexation process
 *
 * There are two possible states:
 *   - use_temporary_table
 *   - use_main_table
 *
 * The 'use_main_table' state means that default indexer table should be used.
 *
 * The 'use_temporary_table' state is an opposite for 'use_main_table'
 *   which means that default indexer table should be left unchanged during indexation
 *   and temporary table should be used instead.
 */
class State
{
    /**
     * Use temporary index table state
     */
    const USE_TEMPORARY_INDEX = 'use_temporary_table';

    /**
     * Use main index table state
     */
    const USE_REGULAR_INDEX = 'use_main_table';

    /**
     * @var string
     */
    private $state = self::USE_REGULAR_INDEX;

    /**
     * Set the state to use temporary Index
     * @return void
     */
    public function useTemporaryIndex()
    {
        $this->state = self::USE_TEMPORARY_INDEX;
    }

    /**
     * Set the state to use regular Index
     * @return void
     */
    public function useRegularIndex()
    {
        $this->state = self::USE_REGULAR_INDEX;
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }
}