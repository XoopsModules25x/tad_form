<?php
use XoopsModules\Tadtools\CkEditor;
use XoopsModules\Tadtools\Utility;
/*-----------引入檔案區--------------*/
$xoopsOption['template_main'] = 'tad_form_adm_mail.tpl';
require_once __DIR__ . '/header.php';
require_once dirname(__DIR__) . '/function.php';

/*-----------function區--------------*/
//列出所有tad_form_main資料
function mail_form_main($ofsn = '')
{
    global $xoopsDB, $xoopsConfig, $xoopsUser, $xoopsTpl;

    $form = get_tad_form_main($ofsn);

    $tag = '{name}<br>';
    $sql = 'select csn,title,kind from ' . $xoopsDB->prefix('tad_form_col') . " where ofsn='$ofsn'";
    $result = $xoopsDB->query($sql) or Utility::web_error($sql, __FILE__, __LINE__);
    while (list($csn, $title, $kind) = $xoopsDB->fetchRow($result)) {
        if ('show' === $kind) {
            continue;
        }

        $tag .= "{{$title}}<br>";
    }

    $CkEditor = new CkEditor('tad_form', 'content', $content);
    $CkEditor->setHeight(400);
    $editor = $CkEditor->render();

    $sql = 'select man_name,email from ' . $xoopsDB->prefix('tad_form_fill') . " where ofsn='{$ofsn}'";
    $result = $xoopsDB->query($sql) or Utility::web_error($sql, __FILE__, __LINE__);

    $i = 0;
    while (list($man_name, $email) = $xoopsDB->fetchRow($result)) {
        $data[$i]['man_name'] = $man_name;
        $data[$i]['email'] = $email;
        $i++;
    }

    $xoopsTpl->assign('data', $data);
    $xoopsTpl->assign('editor', $editor);
    $xoopsTpl->assign('ofsn', $ofsn);
    $xoopsTpl->assign('tag', $tag);
    $xoopsTpl->assign('title', sprintf(_MA_TADFORM_MAIL_TITLE_VAL, $form['title']));
}

//立即寄出
function send_all($ofsn)
{
    global $xoopsConfig, $xoopsDB, $xoopsTpl;
    $xoopsMailer = &getMailer();
    $xoopsMailer->multimailer->ContentType = 'text/html';
    $xoopsMailer->addHeaders('MIME-Version: 1.0');

    $sql = 'select csn,title from ' . $xoopsDB->prefix('tad_form_col') . " where ofsn='$ofsn'";
    $result = $xoopsDB->query($sql) or Utility::web_error($sql, __FILE__, __LINE__);
    while (list($csn, $title) = $xoopsDB->fetchRow($result)) {
        $tag[$csn] = "{{$title}}";
    }

    $i = 0;
    foreach ($_POST['email'] as $man_name => $mail) {
        $content = $_POST['content'];
        $ans = [];
        $sql = 'select a.csn,a.val from ' . $xoopsDB->prefix('tad_form_value') . ' as a left join ' . $xoopsDB->prefix('tad_form_fill') . " as b on a.ssn=b.ssn where b.man_name='$man_name' and b.`ofsn`='{$ofsn}'";
        $result = $xoopsDB->query($sql) or Utility::web_error($sql, __FILE__, __LINE__);
        while (list($csn, $val) = $xoopsDB->fetchRow($result)) {
            $ans[$csn] = $val;
        }

        $content = str_replace('{name}', $man_name, $content);
        foreach ($tag as $csn => $title) {
            $content = str_replace($title, $ans[$csn], $content);
        }

        if ('1' == $_POST['test']) {
            $main[$i]['mail'] = $mail;
            $main[$i]['title'] = $_POST['title'];
            $main[$i]['content'] = $content;
            $i++;
        } else {
            if ($xoopsMailer->sendMail($mail, $_POST['title'], $content, $headers)) {
                $main .= "{$mail} " . _MA_TADFORM_SEND_OK . '<br>';
            } else {
                $main .= "{$mail} " . _MA_TADFORM_SEND_ERROR . '<br>';
            }
        }
    }

    if ('1' == $_POST['test']) {
        $xoopsTpl->assign('main', $main);
        $xoopsTpl->assign('op', 'send');
    }

    $sql = 'select a.`ofsn`,a.`man_name`,a.`email`, a.`fill_time`,b.`title`,b.`adm_email`  from ' . $xoopsDB->prefix('tad_form_fill') . ' as a left join ' . $xoopsDB->prefix('tad_form_main') . " as b on a.ofsn=b.ofsn where a.ssn='$ssn'";
    $result = $xoopsDB->query($sql) or Utility::web_error($sql, __FILE__, __LINE__);
    list($ofsn, $man_name, $email, $fill_time, $title, $adm_email) = $xoopsDB->fetchRow($result);

    $email_arr = explode(';', $adm_email);
    foreach ($email_arr as $email) {
        //$email=trim($email);
        if (!empty($email)) {
            $xoopsMailer->sendMail($email, sprintf(_MD_TADFORM_MAIL_TITLE, $title, $man_name, $fill_time), $content, $headers);
        }
    }
}
/*-----------執行動作判斷區----------*/
require_once $GLOBALS['xoops']->path('/modules/system/include/functions.php');
$op = system_CleanVars($_REQUEST, 'op', '', 'string');
$ofsn = system_CleanVars($_REQUEST, 'ofsn', 0, 'int');

switch ($op) {
    case 'send':
        send_all($ofsn);
        break;
    //預設動作
    default:
        mail_form_main($ofsn);
        break;
}

/*-----------秀出結果區--------------*/
require_once __DIR__ . '/footer.php';
