<?php
/**
 * This file defines data repository for Inventory
 *
 * PHP version 5
 *
 * @category  asign
 * @package   AsignYellowcube_v2.0_CE_5.1
 * @author    entwicklung@a-sign.ch
 * @copyright A-Sign
 * @license   http://www.a-sign.ch/
 * @version   2.0
 * @link      http://www.a-sign.ch/
 * @since     File available since Release 1.0
 */

namespace Shopware\CustomModels\AsignModels\Errorlogs;
use Shopware\Components\Model\ModelRepository;

/**
* Defines repository for Errorlogs
*
* @category A-Sign
* @package  AsignYellowcube_v2.0_CE_5.1
* @author   entwicklung@a-sign.ch
* @link     http://www.a-sign.ch
*/
class Repository extends ModelRepository
{
    /**
     * Returns all the logs based on filter or sort.
     *
     * @param array   $filters Filters
     * @param integer $sort    Sort value
     * @param integer $offset  Offset value
     * @param integer $limit   Limit value
     *
     * @return array
     */
    public function getLogsListQuery($filters, $sort, $offset = 0, $limit = 100)
    {
        $select = Shopware()->Db()->select()
                ->from('asign_yellowcube_logs');

        //If a filter is set
        if ($filters) {
            foreach ($filters as $filter) {
                $select->where('asign_yellowcube_logs.type LIKE ?', '%' . $filter["value"] . '%');
                $select->orWhere('asign_yellowcube_logs.message LIKE ?', '%' . $filter["value"] . '%');
            }
        }

        // add sorting features...
        if ($sort) {
            $sorting = reset($sort);
            $column = $sorting['property'];
            $direction = $sorting['direction'];

            switch ($column) {
                case 'logtype':
                    $select->order('asign_yellowcube_logs.type ' . $direction);
                    break;
                case 'message':
                    $select->order('asign_yellowcube_logs.message ' . $direction);
                    break;
                case 'timestamp':
                    $select->order('asign_yellowcube_logs.createdon ' . $direction);
                    break;
                default:
                    $select->order('asign_yellowcube_logs.createdon ' . $direction);
            }
        } else {
            $select->order('asign_yellowcube_logs.createdon DESC');
        }

        return $select;
    }

    /**
     * Stores logs information when error generated
     *
     * @param string $sType    Type of Error
     * @param object $oError   Exception error object
     * @param bool   $isDirect Is it non-object?
     *
     * @return null
     */
    public function saveLogsData($sType, $oError, $isDirect = false)
    {
        if (!$isDirect) {
            $sMessage = str_replace("'", '"', $oError->getMessage());
            $sDevlog  = str_replace("'", '"', $oError->__toString());
        } else {
            $sMessage = str_replace("'", '"', $oError);
        }

        $iSql = "INSERT INTO `asign_yellowcube_logs` SET `type` = '" . $sType . "', `message` = '" . $sMessage . "', `devlog` = '" . $sDevlog . "', createdon = CURRENT_TIMESTAMP";
        Shopware()->Db()->query($iSql);
    }
}
