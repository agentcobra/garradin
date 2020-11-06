<?php
namespace Garradin;

use Garradin\Entities\Services\Reminder;
use Garradin\Services\Reminders;
use Garradin\Services\Services;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('membres', Membres::DROIT_ADMIN);

$reminder = Reminders::get((int) qg('id'));

if (!$reminder) {
	throw new UserException("Ce rappel n'existe pas");
}

$csrf_key = 'reminder_edit_' . $reminder->id();

$form->runIf('save', function () use ($reminder) {
	$reminder->importForm();
	$reminder->save();
}, $csrf_key, ADMIN_URL . 'services/reminders/');

$delay_before = $delay_after = '';

if ($reminder->delay < 0) {
	$delay_type = 1;
	$delay_before = abs($reminder->delay);
}
elseif ($reminder->delay > 0) {
	$delay_type = 2;
	$delay_after = abs($reminder->delay);
}
else {
	$delay_type = 0;
}

$services_list = Services::listAssoc();

$tpl->assign(compact('delay_type', 'delay_before', 'delay_after', 'reminder', 'csrf_key', 'services_list'));

$tpl->display('services/reminders/edit.tpl');