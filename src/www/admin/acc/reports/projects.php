<?php
namespace Garradin;

use Garradin\Accounting\Accounts;
use Garradin\Accounting\Reports;
use Garradin\Entities\Accounting\Account;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ACCES);

$by_year = (bool)qg('by_year');

$tpl->assign(compact('by_year'));
$tpl->assign('list', Reports::getAnalyticalSums($by_year));

$tpl->assign('analytical_type', Account::TYPE_ANALYTICAL);
$tpl->assign('analytical_accounts_count', CURRENT_YEAR_ID ? $current_year->accounts()->countByType(Account::TYPE_ANALYTICAL) : null);

$tpl->display('acc/reports/projects.tpl');
