<?php
//區塊主函式 (列指定的調查表)
function tad_one_form($options)
{
    global $xoopsDB, $xoTheme;
    require_once XOOPS_ROOT_PATH . "/modules/tad_form/function_block.php";

    $today = date('Y-m-d H:i:s', xoops_getUserTimestamp(time()));

    $sql = 'select count(*) from ' . $xoopsDB->prefix('tad_form_fill') . " where ofsn='{$options[0]}'";
    //die($sql);
    $result = $xoopsDB->query($sql);
    list($counter) = $xoopsDB->fetchRow($result);

    $sql = 'select * from ' . $xoopsDB->prefix('tad_form_main') . " where ofsn='{$options[0]}'";
    $result = $xoopsDB->query($sql);

    list($ofsn, $title, $start_date, $end_date, $content, $uid, $post_date, $enable) = $xoopsDB->fetchRow($result);

    $start_date = date('Y-m-d', xoops_getUserTimestamp(strtotime($start_date)));
    $end_date = date('Y-m-d', xoops_getUserTimestamp(strtotime($end_date)));

    if (date('Y-m-d', xoops_getUserTimestamp(time())) > $end_date) {
        return '';
    }

    if ($options[1] == '1') {
        $block['sign_form'] = sign_form($ofsn, $ssn, 'return');
    }

    $block['show_form'] = $options[1];
    $block['ofsn'] = $ofsn;
    $block['title'] = $title;
    $block['start_date'] = $start_date;
    $block['end_date'] = $end_date;
    $block['content'] = $content;
    $block['post_date'] = $post_date;
    $block['sign_now'] = sprintf(_MB_TADFORM_SIGN_NOW, $title, $counter);
    $block['date'] = sprintf(_MB_TADFORM_SIGN_DATE, $start_date, $end_date);

    return $block;
}

//區塊編輯函式
function tad_one_form_edit($options)
{
    global $xoopsDB;
    $today = date('Y-m-d H:i:s', xoops_getUserTimestamp(time()));
    $sql = 'select * from ' . $xoopsDB->prefix('tad_form_main') . " where enable='1' and start_date < '{$today}' and end_date > '{$today}'";
    $result = $xoopsDB->query($sql);

    $opt = '';
    while (list($ofsn, $title, $start_date, $end_date, $content, $uid, $post_date, $enable) = $xoopsDB->fetchRow($result)) {
        $selected = ($ofsn == $options[0]) ? 'selected' : '';
        $opt .= "<option value='{$ofsn}' $selected>$title</option>\n";
    }
    $opt .= '';

    $opt1_1 = 1 == $options[1] ? 'checked' : '';
    $opt1_0 = 1 != $options[1] ? 'checked' : '';

    $form = "
    <ol class='my-form'>
        <li class='my-row'>
            <lable class='my-label'>" . _MB_TADFORM_ONE_FORM_T1 . "</lable>
            <div class='my-content'>
                <select name='options[0]' class='my-input' size=5>
                {$opt}
                </select>
            </div>
        </li>
        <li class='my-row'>
            <lable class='my-label'>" . _MB_TADFORM_ONE_FORM_T2 . "</lable>
            <div class='my-content'>
                <input type='radio' name='options[1]' id='opt1_1' value='1' $opt1_1>" . _YES . "
                <input type='radio' name='options[1]' id='opt1_0' value='0' $opt1_0>" . _NO . '
            </div>
        </li>
    </ol>
    ';

    return $form;
}
