<?php

namespace XoopsModules\Tad_form;

use XoopsModules\Tadtools\Utility;

/*
Update Class Definition

You may not change or alter any portion of this comment or credits of
supporting developers from this source code or any supporting source code
which is considered copyrighted (c) material of the original comment or credit
authors.

This program is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

/**
 * @license      http://www.fsf.org/copyleft/gpl.html GNU public license
 * @copyright    https://xoops.org 2001-2017 &copy; XOOPS Project
 * @author       Mamba <mambax7@gmail.com>
 */

/**
 * Class Update
 */
class Update
{

    public static function chk_add_files_center()
    {
        global $xoopsDB;
        $sql = "SELECT table_name FROM information_schema.tables WHERE table_schema = '" . XOOPS_DB_NAME . "' AND table_name = '" . $xoopsDB->prefix('tad_form_files_center') . "'";
        $result = $xoopsDB->query($sql);
        list($table_name) = $xoopsDB->fetchRow($result);
        if (empty($table_name)) {
            $sql = "CREATE TABLE `" . $xoopsDB->prefix('tad_form_files_center') . "` (
                `files_sn` smallint(5) unsigned NOT NULL AUTO_INCREMENT COMMENT '檔案流水號',
                `col_name` varchar(255) NOT NULL DEFAULT '' COMMENT '欄位名稱',
                `col_sn` varchar(255) NOT NULL DEFAULT '' COMMENT '欄位編號',
                `sort` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
                `kind` enum('img','file') NOT NULL DEFAULT 'img' COMMENT '檔案種類',
                `file_name` varchar(255) NOT NULL DEFAULT '' COMMENT '檔案名稱',
                `file_type` varchar(255) NOT NULL DEFAULT '' COMMENT '檔案類型',
                `file_size` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '檔案大小',
                `description` text NOT NULL COMMENT '檔案說明',
                `counter` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '下載人次',
                `original_filename` varchar(255) NOT NULL DEFAULT '' COMMENT '檔案名稱',
                `hash_filename` varchar(255) NOT NULL DEFAULT '' COMMENT '加密檔案名稱',
                `sub_dir` varchar(255) NOT NULL DEFAULT '' COMMENT '檔案子路徑',
                `upload_date` datetime NOT NULL COMMENT '上傳時間',
                `uid` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '上傳者',
                `tag` varchar(255) NOT NULL DEFAULT '' COMMENT '註記',
                PRIMARY KEY (`files_sn`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
            $xoopsDB->queryF($sql);
        }
    }

    public static function chk_add_data_center()
    {
        global $xoopsDB;
        $sql = "SELECT table_name FROM information_schema.tables WHERE table_schema = '" . XOOPS_DB_NAME . "' AND table_name = '" . $xoopsDB->prefix('tad_form_data_center') . "'";
        $result = $xoopsDB->query($sql);
        list($table_name) = $xoopsDB->fetchRow($result);
        if (empty($table_name)) {
            $sql = "CREATE TABLE `" . $xoopsDB->prefix('tad_form_data_center') . "` (
                `mid` mediumint(9) unsigned NOT NULL AUTO_INCREMENT COMMENT '模組編號',
                `col_name` varchar(100) NOT NULL DEFAULT '' COMMENT '欄位名稱',
                `col_sn` mediumint(9) unsigned NOT NULL DEFAULT '0' COMMENT '欄位編號',
                `data_name` varchar(100) NOT NULL DEFAULT '' COMMENT '資料名稱',
                `data_value` text NOT NULL COMMENT '儲存值',
                `data_sort` mediumint(9) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
                `col_id` varchar(100) NOT NULL COMMENT '辨識字串',
                `sort` mediumint(9) unsigned DEFAULT NULL COMMENT '排序',
                `update_time` datetime NOT NULL COMMENT '更新時間',
                PRIMARY KEY (`mid`, `col_name`, `col_sn`, `data_name`, `data_sort`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
            $xoopsDB->queryF($sql);
        }
    }

    //刪除錯誤的重複欄位及樣板檔
    public static function chk_tad_form_block()
    {
        global $xoopsDB;
        //die(var_export($xoopsConfig));
        require XOOPS_ROOT_PATH . '/modules/tad_form/xoops_version.php';

        //先找出該有的區塊以及對應樣板
        foreach ($modversion['blocks'] as $i => $block) {
            $show_func = $block['show_func'];
            $tpl_file_arr[$show_func] = $block['template'];
            $tpl_desc_arr[$show_func] = $block['description'];
        }

        //找出目前所有的樣板檔
        $sql = 'SELECT bid,name,visible,show_func,template FROM `' . $xoopsDB->prefix('newblocks') . "`
        WHERE `dirname` = 'tad_form' ORDER BY `func_num`";
        $result = $xoopsDB->query($sql);
        while (list($bid, $name, $visible, $show_func, $template) = $xoopsDB->fetchRow($result)) {
            //假如現有的區塊和樣板對不上就刪掉
            if ($template != $tpl_file_arr[$show_func]) {
                $sql = 'delete from ' . $xoopsDB->prefix('newblocks') . " where bid='{$bid}'";
                $xoopsDB->queryF($sql);

                //連同樣板以及樣板實體檔案也要刪掉
                $sql = 'delete from ' . $xoopsDB->prefix('tplfile') . ' as a
                left join ' . $xoopsDB->prefix('tplsource') . "  as b on a.tpl_id=b.tpl_id
                where a.tpl_refid='$bid' and a.tpl_module='tad_form' and a.tpl_type='block'";
                $xoopsDB->queryF($sql);
            } else {
                $sql = 'update ' . $xoopsDB->prefix('tplfile') . "
                set tpl_file='{$template}' , tpl_desc='{$tpl_desc_arr[$show_func]}'
                where tpl_refid='{$bid}'";
                $xoopsDB->queryF($sql);
            }
        }
    }

    public static function go_update_size()
    {
        global $xoopsDB;

        // 檢查欄位類型
        $sql = "SELECT COLUMN_TYPE
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE  TABLE_NAME = '" . $xoopsDB->prefix('tad_form_col') . "'
        AND COLUMN_NAME = 'size'";

        $result = $xoopsDB->query($sql);

        if ($xoopsDB->getRowsNum($result) == 0) {
            throw new Exception("欄位 size 不存在於資料表 " . $xoopsDB->prefix('tad_form_col') . "");
        }

        $row = $xoopsDB->fetchArray($result);

        // 檢查是否需要修改欄位類型
        if (strtolower($row['COLUMN_TYPE']) !== 'varchar(1000)') {
            // 修改欄位類型
            $sql = "ALTER TABLE " . $xoopsDB->prefix('tad_form_col') . " MODIFY COLUMN size VARCHAR(1000)";

            if (!$xoopsDB->queryF($sql)) {
                throw new Exception("修改欄位類型失敗：" . $mysqli->error);
            }
        }
    }

    public static function chk_chk1()
    {
        global $xoopsDB;
        $sql = 'SELECT count(`kind`) FROM ' . $xoopsDB->prefix('tad_form_main');
        $result = $xoopsDB->query($sql);
        if (empty($result)) {
            return false;
        }

        return true;
    }

    public static function go_update1()
    {
        global $xoopsDB;
        $sql = 'ALTER TABLE ' . $xoopsDB->prefix('tad_form_main') . ' ADD `kind` VARCHAR(255) NOT NULL';
        $xoopsDB->queryF($sql) or Utility::web_error($sql, __FILE__, __LINE__, true);

        return true;
    }

    public static function chk_chk2()
    {
        global $xoopsDB;
        $sql = 'SELECT count(`result_col`) FROM ' . $xoopsDB->prefix('tad_form_fill');
        $result = $xoopsDB->query($sql);
        if (empty($result)) {
            return false;
        }

        return true;
    }

    public static function go_update2()
    {
        global $xoopsDB;
        $sql = 'ALTER TABLE ' . $xoopsDB->prefix('tad_form_fill') . ' ADD `result_col` VARCHAR(255) NOT NULL';
        $xoopsDB->queryF($sql) or Utility::web_error($sql, __FILE__, __LINE__, true);

        return true;
    }

    public static function chk_chk3()
    {
        global $xoopsDB;
        $sql = 'SELECT count(`adm_email`) FROM ' . $xoopsDB->prefix('tad_form_main');
        $result = $xoopsDB->query($sql);
        if (empty($result)) {
            return false;
        }

        return true;
    }

    public static function go_update3()
    {
        global $xoopsDB;
        $sql = 'ALTER TABLE ' . $xoopsDB->prefix('tad_form_main') . ' ADD `adm_email` VARCHAR(255) NOT NULL';
        $xoopsDB->queryF($sql) or Utility::web_error($sql, __FILE__, __LINE__, true);

        return true;
    }

    public static function chk_chk4()
    {
        global $xoopsDB;
        $sql = 'SELECT count(`captcha`) FROM ' . $xoopsDB->prefix('tad_form_main');
        $result = $xoopsDB->query($sql);
        if (empty($result)) {
            return false;
        }

        return true;
    }

    public static function go_update4()
    {
        global $xoopsDB;
        $sql = 'ALTER TABLE ' . $xoopsDB->prefix('tad_form_main') . " ADD `captcha` ENUM('1','0') NOT NULL DEFAULT '1'";
        $xoopsDB->queryF($sql) or Utility::web_error($sql, __FILE__, __LINE__, true);

        return true;
    }

    public static function chk_chk5()
    {
        global $xoopsDB;
        $sql = 'SELECT count(`show_result`) FROM ' . $xoopsDB->prefix('tad_form_main');
        $result = $xoopsDB->query($sql);
        if (empty($result)) {
            return false;
        }

        return true;
    }

    public static function go_update5()
    {
        global $xoopsDB;
        $sql = 'ALTER TABLE ' . $xoopsDB->prefix('tad_form_main') . " ADD `show_result` ENUM('1','0') NOT NULL DEFAULT '1'";
        $xoopsDB->queryF($sql) or Utility::web_error($sql, __FILE__, __LINE__, true);

        return true;
    }

    public static function chk_chk6()
    {
        global $xoopsDB;
        $sql = 'SELECT count(`view_result_group`) FROM ' . $xoopsDB->prefix('tad_form_main');
        $result = $xoopsDB->query($sql);
        if (empty($result)) {
            return false;
        }

        return true;
    }

    public static function go_update6()
    {
        global $xoopsDB;
        $sql = 'ALTER TABLE ' . $xoopsDB->prefix('tad_form_main') . " ADD `view_result_group` VARCHAR(255) NOT NULL DEFAULT ''";
        $xoopsDB->queryF($sql) or Utility::web_error($sql, __FILE__, __LINE__, true);

        return true;
    }

    public static function chk_chk7()
    {
        global $xoopsDB;
        $sql = 'SELECT count(`multi_sign`) FROM ' . $xoopsDB->prefix('tad_form_main');
        $result = $xoopsDB->query($sql);
        if (empty($result)) {
            return false;
        }

        return true;
    }

    public static function go_update7()
    {
        global $xoopsDB;
        $sql = 'ALTER TABLE ' . $xoopsDB->prefix('tad_form_main') . " ADD `multi_sign` ENUM('0','1') NOT NULL DEFAULT '0'";
        $xoopsDB->queryF($sql) or Utility::web_error($sql, __FILE__, __LINE__, true);

        return true;
    }

    public static function chk_chk8()
    {
        global $xoopsDB;
        $sql = 'SELECT count(`public`) FROM ' . $xoopsDB->prefix('tad_form_col');
        $result = $xoopsDB->query($sql);
        if (empty($result)) {
            return false;
        }

        return true;
    }

    public static function go_update8()
    {
        global $xoopsDB;
        $sql = 'ALTER TABLE ' . $xoopsDB->prefix('tad_form_col') . " ADD `public`  ENUM('0','1') NOT NULL DEFAULT '0'";
        $xoopsDB->queryF($sql) or Utility::web_error($sql, __FILE__, __LINE__, true);

        return true;
    }

    //修正uid欄位
    public static function chk_uid()
    {
        global $xoopsDB;
        $sql = "SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = '" . $xoopsDB->prefix('tad_form_main') . "' AND COLUMN_NAME = 'uid'";
        $result = $xoopsDB->query($sql);
        list($type) = $xoopsDB->fetchRow($result);
        if ('smallint' === $type) {
            return true;
        }

        return false;
    }

    //執行更新
    public static function go_update_uid()
    {
        global $xoopsDB;
        $sql = 'ALTER TABLE `' . $xoopsDB->prefix('tad_form_main') . '` CHANGE `uid` `uid` MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT 0';
        $xoopsDB->queryF($sql) or Utility::web_error($sql, __FILE__, __LINE__, true);
        $sql = 'ALTER TABLE `' . $xoopsDB->prefix('tad_form_fill') . '` CHANGE `uid` `uid` MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT 0';
        $xoopsDB->queryF($sql) or Utility::web_error($sql, __FILE__, __LINE__, true);

        return true;
    }

    public static function chk_chk9()
    {
        global $xoopsDB;
        $sql = 'SELECT count(`code`) FROM ' . $xoopsDB->prefix('tad_form_fill');
        $result = $xoopsDB->query($sql);
        if (empty($result)) {
            return false;
        }

        return true;
    }

    public static function go_update9()
    {
        global $xoopsDB;
        $sql = 'ALTER TABLE ' . $xoopsDB->prefix('tad_form_fill') . ' ADD `code` VARCHAR(255) NOT NULL';
        $xoopsDB->queryF($sql) or Utility::web_error($sql, __FILE__, __LINE__, true);

        $sql = 'update ' . $xoopsDB->prefix('tad_form_fill') . ' set code=md5(CONCAT(`ofsn`, `uid`, `man_name`, `email`, `fill_time`)) ';
        $xoopsDB->queryF($sql) or Utility::web_error($sql, __FILE__, __LINE__, true);

        return true;
    }

    //修正區塊索引
    public static function chk_chk10()
    {
        global $xoopsDB;
        $sql = 'show keys from ' . $xoopsDB->prefix('tad_form_fill') . " where Key_name='ofsn_code'";
        $result = $xoopsDB->query($sql);
        if (empty($result)) {
            return true;
        }

        return false;
    }

    public static function go_update10()
    {
        global $xoopsDB;
        $sql = 'ALTER TABLE `' . $xoopsDB->prefix('tad_form_fill') . '` ADD UNIQUE `ofsn_code` (`ofsn`, `code`);';
        $xoopsDB->queryF($sql) or Utility::web_error($sql);

        return true;
    }
}
