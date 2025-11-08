<?php

/**
 * Care plan form new.php
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Jacob T Paul <jacob@zhservices.com>
 * @author    Vinish K <vinish@zhservices.com>
 * @author    Brady Miller <brady.g.miller@gmail.com>
 * @copyright Copyright (c) 2015 Z&H Consultancy Services Private Limited <sam@zhservices.com>
 * @copyright Copyright (c) 2017-2019 Brady Miller <brady.g.miller@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

require_once("../../globals.php");
require_once("$srcdir/api.inc.php");
require_once("$srcdir/patient.inc.php");
require_once("$srcdir/options.inc.php");
require_once($GLOBALS['srcdir'] . '/csv_like_join.php');
require_once($GLOBALS['fileroot'] . '/custom/code_types.inc.php');

use OpenEMR\Common\Csrf\CsrfUtils;
use OpenEMR\Common\Forms\ReasonStatusCodes;
use OpenEMR\Core\Header;

$returnurl = 'encounter_top.php';
$formid = (int)($_GET['id'] ?? 0);

// Fetch existing Care Plan form if it exists
if (empty($formid)) {
    $sql = "SELECT form_id, encounter FROM `forms` WHERE formdir = 'care_plan' AND pid = ? AND encounter = ? AND deleted = 0 LIMIT 1";
    $formid = sqlQuery($sql, array($_SESSION["pid"], $_SESSION["encounter"]))['form_id'] ?? 0;
    if (!empty($formid)) {
        echo "<script>var message=" . js_escape(xl("Already a Care Plan form for this encounter. Using existing Care Plan form.")) . "</script>";
    }
}

// Fetch Care Plan details if form ID is present
if (!empty($formid)) {
    $sql = "SELECT * FROM `form_care_plan` WHERE id=? AND pid = ? AND encounter = ?";
    $res = sqlStatement($sql, array($formid, $_SESSION["pid"], $_SESSION["encounter"]));
    $check_res = [];
    while ($row = sqlFetchArray($res)) {
        $check_res[] = $row;
    }
} else {
    $check_res = [];
}

// Fetch care plan types
$sql1 = "SELECT option_id AS `value`, title FROM `list_options` WHERE list_id = ?";
$result = sqlStatement($sql1, array('Plan_of_Care_Type'));
$care_plan_type = [];
foreach ($result as $value) {
    $care_plan_type[] = $value;
}

// Initialize reason codes
$reasonCodeStatii = ReasonStatusCodes::getCodesWithDescriptions();
$reasonCodeStatii[ReasonStatusCodes::NONE]['description'] = xl("Select a status code");
?>
<html>
<head>
    <title><?php echo xlt("Care Plan Form"); ?></title>
    <?php Header::setupHeader(['datetime-picker', 'reason-code-widget']); ?>
    <script src="<?php echo attr($GLOBALS['webroot']); ?>/interface/forms/care_plan/careplan.js?v=<?php echo attr($GLOBALS['v_js_includes']); ?>" type="text/javascript"></script>
    <script>
        window.addEventListener('DOMContentLoaded', function () {
            window.careplanForm.init(<?php echo js_url($GLOBALS['webroot']); ?>);
        });

        $(function () {
            // Datepicker initialization
            $(document).on('mouseover', '.datepicker', function () {
                $(this).datetimepicker({
                    <?php $datetimepicker_timepicker = true; ?>
                    <?php $datetimepicker_showseconds = false; ?>
                    <?php $datetimepicker_formatInput = false; ?>
                    <?php require($GLOBALS['srcdir'] . '/js/xl/jquery-datetimepicker-2-5-4.js.php'); ?>
                });
            });
            if (typeof message !== 'undefined') {
                alert(message);
            }
            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>
</head>
<body>
<div class="container mt-3">
    <div class="row">
        <div class="col-12">
            <h2><?php echo xlt('Care Plan Form'); ?></h2>
            <form method='post' name='my_form' action='<?php echo $rootdir ?>/forms/care_plan/save.php?id=<?php echo attr_url($formid) ?>'>
                <input type="hidden" name="csrf_token_form" value="<?php echo attr(CsrfUtils::collectCsrfToken()); ?>" />
                <fieldset>
                    <legend><?php echo xlt('Enter Details'); ?></legend>
                    <div class="container">
                        <?php if (!empty($check_res)): ?>
                            <?php foreach ($check_res as $key => $obj): ?>
                                <div class="tb_row" id="tb_row_<?php echo attr($key) + 1; ?>">
                                    <div class="form-row">
                                        <div class="forms col-md-4">
                                            <label for="code_<?php echo attr($key) + 1; ?>" class="h5"><?php echo xlt('Code'); ?>:</label>
                                            <input type="text" id="code_<?php echo attr($key) + 1; ?>" name="code[]" class="form-control code"
                                                   value="<?php echo attr($obj["code"]); ?>" onclick='sel_code(<?php echo attr_js($GLOBALS['webroot']) ?>, this.closest(".tb_row").id);' data-toggle='tooltip' data-placement='bottom' title='<?php echo attr($obj['code']); ?>' />
                                            <span id="displaytext_<?php echo attr($key) + 1; ?>" class="displaytext help-block"><?php echo text($obj["codetext"] ?? ''); ?></span>
                                            <input type="hidden" id="codetext_<?php echo attr($key) + 1; ?>" name="codetext[]" class="codetext" value="<?php echo attr($obj["codetext"]); ?>" />
                                            <input type="hidden" id="user_<?php echo attr($key) + 1; ?>" name="user[]" class="user" value="<?php echo attr($obj["user"]); ?>" />
                                        </div>
                                        <div class="forms col-md-4">
                                            <label for="code_date_<?php echo attr($key) + 1; ?>" class="h5"><?php echo xlt('Date'); ?>:</label>
                                            <input type='text' id="code_date_<?php echo attr($key) + 1; ?>" name='code_date[]' class="form-control code_date datepicker" value='<?php echo attr($obj["date"]); ?>' title='<?php echo xla('yyyy-mm-dd HH:MM Date of service'); ?>' />
                                        </div>
                                        <div class="forms col-md-4">
                                            <label for="care_plan_type_<?php echo attr($key) + 1; ?>" class="h5" style="display:none;"><?php echo xlt('Type'); ?>:</label>
                                            <select name="care_plan_type[]" id="care_plan_type_<?php echo attr($key) + 1; ?>" class="form-control care_plan_type" style="display:none;">
                                                <option value=""></option>
                                                <?php foreach ($care_plan_type as $value): ?>
                                                    <option value="<?php echo attr($value['value']); ?>" <?php echo ($value['value'] == $obj["care_plan_type"]) ? 'selected' : ''; ?>><?php echo text($value['title']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-row mt-2">
                                        <div class="forms col-md-6">
                                            <label for="cara_<?php echo attr($key) + 1; ?>" class="h5"><?php echo xlt('Cara:'); ?>:</label>
                                            <select name="cara[]" class="form-control">
                                                <option value="O" <?php echo ($obj['cara'] ?? "") == "O" ? "selected" : ""; ?>><?php echo xlt("O"); ?></option>
                                                <option value="M" <?php echo ($obj['cara'] ?? "") == "M" ? "selected" : ""; ?>><?php echo xlt("M"); ?></option>
                                                <option value="D" <?php echo ($obj['cara'] ?? "") == "D" ? "selected" : ""; ?>><?php echo xlt("D"); ?></option>
                                                <option value="V" <?php echo ($obj['cara'] ?? "") == "V" ? "selected" : ""; ?>><?php echo xlt("V"); ?></option>
                                                <option value="L" <?php echo ($obj['cara'] ?? "") == "L" ? "selected" : ""; ?>><?php echo xlt("L"); ?></option>
                                            </select>
                                        </div>
                                        <div class="forms col-md-6">
                                            <label for="diente_<?php echo attr($key) + 1; ?>" class="h5"><?php echo xlt('Diente:'); ?>:</label>
                                            <input type="text" name="diente[]" class="form-control" value="<?php echo attr($obj['diente'] ?? ''); ?>" placeholder="<?php echo xlt('Número'); ?>" />
                                        </div>
                                    </div>
                                    <div class="form-row mt-2">
                                        <div class="forms col-md-12">
                                            <label for="description_<?php echo attr($key) + 1; ?>" class="h5"><?php echo xlt('Description'); ?>:</label>
                                            <textarea name="description[]" id="description_<?php echo attr($key) + 1; ?>" class="form-control description" rows="6"><?php echo text($obj["description"]); ?></textarea>
                                        </div>
                                    </div>
                                    <div class="form-row w-100 mt-2 text-center">
                                        <div class="forms col-md-12">
                                            <?php include("templates/careplan_actions.php"); ?>
                                        </div>
                                        <input type="hidden" name="count[]" id="count_<?php echo attr($key) + 1; ?>" class="count" value="<?php echo attr($key) + 1; ?>" />
                                    </div>
                                    <?php include "templates/careplan_reason_row.php"; ?>
                                    <hr />
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="tb_row" id="tb_row_1">
                                <div class="form-row">
                                    <div class="forms col-md-4">
                                        <label for="code_1" class="h5"><?php echo xlt('Code'); ?>:</label>
                                        <input type="text" id="code_1" name="code[]" class="form-control code" value="<?php echo attr($obj["code"] ?? ''); ?>" onclick='sel_code(<?php echo attr_js($GLOBALS['webroot']) ?>, this.closest(".tb_row").id);'>
                                        <input type="hidden" id="user_1" name="user[]" class="user" value="<?php echo attr($obj["user"] ?? $_SESSION["authUser"]); ?>" />
                                        <span id="displaytext_1" class="displaytext help-block"></span>
                                        <input type="hidden" id="codetext_1" name="codetext[]" class="codetext" value="<?php echo attr($obj["codetext"] ?? ''); ?>">
                                    </div>
                                    <div class="forms col-md-4">
                                        <label for="code_date_1" class="h5"><?php echo xlt('Date'); ?>:</label>
                                        <input type='text' id="code_date_1" name='code_date[]' class="form-control code_date datepicker" value='<?php echo attr($obj["date"] ?? ''); ?>' title='<?php echo xla('yyyy-mm-dd Date of service'); ?>' />
                                    </div>
                                    <div class="forms col-md-4">
                                        <label for="care_plan_type_1" class="h5" style="display:none;"><?php echo xlt('Type'); ?>:</label>
                                        <select name="care_plan_type[]" id="care_plan_type_1" class="form-control care_plan_type" style="display:none;">
                                            <option value=""></option>
                                            <?php foreach ($care_plan_type as $value): ?>
                                                <option value="<?php echo attr($value['value']); ?>" <?php echo ($value['value'] == ($obj["care_plan_type"] ?? '')) ? 'selected' : ''; ?>><?php echo text($value['title']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="forms col-md-4">
                                        <label class="h5"><?php echo xlt("Cara:"); ?></label>
                                        <select name="cara[]" class="form-control">
                                            <option value="O"><?php echo xlt("O"); ?></option>
                                            <option value="M"><?php echo xlt("M"); ?></option>
                                            <option value="D"><?php echo xlt("D"); ?></option>
                                            <option value="V"><?php echo xlt("V"); ?></option>
                                            <option value="L"><?php echo xlt("L"); ?></option>
                                        </select>
                                    </div>
                                    <div class="forms col-md-4">
                                        <label class="h5"><?php echo xlt("Diente o pieza:"); ?></label>
                                        <input type="text" name="diente[]" class="form-control" value="" placeholder="<?php echo xlt('Número'); ?>" />
                                    </div>
                                    <div class="forms col-md-12">
                                        <label for="description_1" class="h5"><?php echo xlt('Description'); ?>:</label>
                                        <textarea name="description[]" id="description_1" class="form-control description" rows="6"><?php echo text($obj["description"] ?? ''); ?></textarea>
                                    </div>
                                    <div class="form-row w-100 mt-2 text-center">
                                        <div class="forms col-md-12">
                                            <?php include("templates/careplan_actions.php"); ?>
                                        </div>
                                        <input type="hidden" name="count[]" id="count_1" class="count" value="1" />
                                    </div>
                                    <hr />
                                </div>
                                <?php include "templates/careplan_reason_row.php"; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </fieldset>
                <div class="form-group">
                    <div class="col-sm-12 position-override">
                        <div class="btn-group" role="group">
                            <button type="submit" onclick="top.restoreSession()" class="btn btn-primary btn-save"><?php echo xlt('Save'); ?></button>
                            <button type="button" class="btn btn-secondary btn-cancel" onclick="top.restoreSession(); parent.closeTab(window.name, false);"><?php echo xlt('Cancel'); ?></button>
                        </div>
                        <input type="hidden" id="clickId" value="" />
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
