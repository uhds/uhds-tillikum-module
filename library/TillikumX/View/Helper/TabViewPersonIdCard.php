<?php

/**
 * OSU Tillikum extension library
 *
 * @package TillikumX_View
 * @subpackage Helper
 */

namespace TillikumX\View\Helper;

use DateTime,
    Zend_View_Helper_Abstract as AbstractHelper;

/**
 * Helper for rendering the person tab view 'ID card' section
 *
 * @package TillikumX_View
 * @subpackage Helper
 */
class TabViewPersonIdCard extends AbstractHelper
{
    protected $commonDb;
    protected $diningInfoGw;
    protected $script;

    public function __construct()
    {
        $this->commonDb = \Uhds_Db::factory('common');
        $this->diningInfoGw = new \Uhds\Model\Dining\InformationGateway();

        $this->script = '_partials/id_card.phtml';
    }

    public function tabViewPersonIdCard()
    {
        return $this;
    }

    public function canShowTab($person)
    {
        if (!($person instanceof \TillikumX\Entity\Person\Person)) {
            return false;
        }

        return true;
    }

    public function render($person)
    {
        $balances = array();
        foreach ($this->diningInfoGw->fetchPlanBalances($person->osuid) as $balance) {
            $balances[$balance['plan']] = $balance['balance'];
        }

        $proxNumber = $this->commonDb->fetchOne($this->commonDb->select()
            ->from('proxcard', 'card')
            ->where('osuid = ?', $person->osuid)
        );

        $lastUpdates = $this->commonDb->fetchCol($this->commonDb->select()
            ->union(array(
                $this->commonDb->select()
                ->from('mealplan_balance_job', 'updated'),
                $this->commonDb->select()
                ->from('proxcard_job', 'updated')
            ), \Zend_Db_Select::SQL_UNION_ALL)
        );

        return $this->view->partial(
            $this->script,
            array(
                'balances' => $balances,
                'prox_number' => $proxNumber,
                'balances_updated_at' => new DateTime($lastUpdates[0] . 'Z'),
                'prox_number_updated_at' => new DateTime($lastUpdates[1] . 'Z')
            )
        );
    }
}
