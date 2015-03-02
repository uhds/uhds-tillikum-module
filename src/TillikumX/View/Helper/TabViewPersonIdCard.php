<?php
/**
 * The Tillikum Project (http://tillikum.org/)
 *
 * @link       http://tillikum.org/websvn/
 * @copyright  Copyright 2009-2012 Oregon State University (http://oregonstate.edu/)
 * @license    http://www.gnu.org/licenses/gpl-2.0-standalone.html GPLv2
 */

namespace TillikumX\View\Helper;

use DateTime;
use Zend_View_Helper_Abstract as AbstractHelper;

/**
 * Helper for rendering the person tab view 'ID card' section
 */
class TabViewPersonIdCard extends AbstractHelper
{
    protected $commonDb;
    protected $diningInfoGw;

    public function __construct()
    {
        $this->commonDb = \Uhds_Db::factory('common');
        $this->diningInfoGw = new \Uhds\Model\Dining\InformationGateway();
    }

    public function tabViewPersonIdCard()
    {
        return $this;
    }

    public function canShowTab($person)
    {
        return ($person instanceof \TillikumX\Entity\Person\Person);
    }

    public function render($person)
    {
        $balances = array();
        foreach ($this->diningInfoGw->fetchPlanBalances($person->osuid) as $balance) {
            $balances[$balance['plan']] = $balance['balance'];
        }

        $proxNumber = $this->commonDb->fetchOne(
            $this->commonDb->select()
                ->from('proxcard', 'card')
                ->where('osuid = ?', $person->osuid)
        );

        
        $proxcardUpdate = $this->commonDb->fetchOne(
            $this->commonDb->select()
                ->from('proxcard_job', 'updated')
        );

        $mealplan_balance_import_time = $this->diningInfoGw->fetchLastBalanceImportTime();

        return $this->view->partial(
            '_partials/id_card.phtml',
            array(
                'balances' => $balances,
                'prox_number' => $proxNumber,
                'balances_updated_at' => $mealplan_balance_import_time,
                'prox_number_updated_at' => new DateTime($proxcardUpdate . 'Z')
            )
        );
    }
}