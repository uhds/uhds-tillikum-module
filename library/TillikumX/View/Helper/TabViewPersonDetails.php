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
 * Helper for rendering the person tab view 'details' section
 *
 * @package TillikumX_View
 * @subpackage Helper
 */
class TabViewPersonDetails extends AbstractHelper
{
    protected $script;

    public function __construct()
    {
        $this->script = '_partials/details.phtml';
    }

    public function tabViewPersonDetails()
    {
        return $this;
    }

    public function canShowTab($person)
    {
        return true;
    }

    public function render($person)
    {
        return $this->view->partial(
            $this->script,
            array(
                'person' => $person,
                'tbc' => new \Uhds_View_Helper_TranslateBannerCode()
            )
        );
    }
}
