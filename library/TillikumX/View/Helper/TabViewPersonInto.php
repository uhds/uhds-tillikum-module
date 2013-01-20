<?php

/**
 * OSU Tillikum extension library
 *
 * @package TillikumX_View
 * @subpackage Helper
 */

namespace TillikumX\View\Helper;

use Zend_View_Helper_Abstract as AbstractHelper;

/**
 * Helper for rendering the person tab view 'into' section
 *
 * @package TillikumX_View
 * @subpackage Helper
 */
class TabViewPersonInto extends AbstractHelper
{
    protected $script;

    public function __construct()
    {
        $this->script = '_partials/into.phtml';
    }

    public function tabViewPersonInto()
    {
        return $this;
    }

    public function canShowTab($person)
    {
        if (!method_exists($person, 'getIntoData')) {
            return false;
        }

        return $person->getIntoData() !== null;
    }

    public function render($person)
    {
        return $this->view->partial(
            $this->script,
            array(
                'person' => $person
            )
        );
    }
}
