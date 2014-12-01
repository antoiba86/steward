<?php

namespace Lmc\Steward\Test;

use Fhaculty\Graph\Algorithm\Tree\OutTree;

/**
 * Interface for optimizers of tests order.
 */
interface OptimizeOrderInterface
{
    /**
     * For each vertex in the tree (except root node) evaluate its order.
     * This determines the order in which tests (that have same time delay or no time delay) would be stared.
     *
     * @param OutTree $tree
     * @return array Array of [string key (= testclass fully qualified name) => int value (= test order)]
     */
    public function optimize(OutTree $tree);
}
