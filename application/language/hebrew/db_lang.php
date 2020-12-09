<?php
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2014 - 2017, British Columbia Institute of Technology
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package    CodeIgniter
 * @author    EllisLab Dev Team
 * @copyright    Copyright (c) 2008 - 2014, EllisLab, Inc. (https://ellislab.com/)
 * @copyright    Copyright (c) 2014 - 2017, British Columbia Institute of Technology (http://bcit.ca/)
 * @license    http://opensource.org/licenses/MIT	MIT License
 * @link    https://codeigniter.com
 * @since    Version 1.0.0
 * @filesource
 */
defined('BASEPATH') or exit('No direct script access allowed');

$lang['db_invalid_connection_str'] = 'לא ניתן לקבוע את הגדרות מסד הנתונים בהתבסס על מחרוזת החיבור שהגשת.';
$lang['db_unable_to_connect'] = 'לא ניתן להתחבר לשרת מסד הנתונים שלך באמצעות ההגדרות שסופקו.';
$lang['db_unable_to_select'] = 'לא ניתן לבחור את מסד הנתונים שצוין: %s';
$lang['db_unable_to_create'] = 'לא ניתן ליצור את מסד הנתונים שצוין: %s';
$lang['db_invalid_query'] = 'השאילתה ששלחת אינה חוקית.';
$lang['db_must_set_table'] = 'עליך להגדיר את טבלת מסד הנתונים שתשתמש בה עם השאילתה שלך.';
$lang['db_must_use_set'] = 'עליך להשתמש ב "set" כדי לעדכן ערך.';
$lang['db_must_use_index'] = 'עליך לציין אינדקס להתאמה לעדכונים כוללים.';
$lang['db_batch_missing_index'] = 'בשורה אחת או יותר שנשלחו לעדכונים כוללים חסר האינדקס שצוין.';
$lang['db_must_use_where'] = 'העדכונים אינם מורשים אלא אם כן הם מכילים סעיף "where".';
$lang['db_del_must_use_where'] = 'מחיקות אינן מורשות אלא אם כן הן מכילות סעיף "where" או "like".';
$lang['db_field_param_missing'] = 'כדי לשלוף שדות נדרשים יש לתת שם לטבלה כפרמטר.';
$lang['db_unsupported_function'] = 'תכונה זו אינה זמינה עבור מסד הנתונים שבו אתה משתמש.';
$lang['db_transaction_failure'] = 'הפעולה כשלה: בוצע שחזור.';
$lang['db_unable_to_drop'] = 'לא ניתן להסיר את מסד הנתונים שצוין.';
$lang['db_unsupported_feature'] = 'תכונה לא נתמכת בפלטפורמת מסד הנתונים בה אתה משתמש.';
$lang['db_unsupported_compression'] = 'פורמט דחיסת הקבצים שבחרת אינו נתמך על ידי השרת שלך.';
$lang['db_filepath_error'] = 'לא ניתן לכתוב נתונים לנתיב המבוקש.';
$lang['db_invalid_cache_path'] = 'נתיב זיכרון המטמון ששלחת אינו תקין או שאינו ניתן לכתיבה.';
$lang['db_table_name_required'] = 'נדרש שם לטבלה בכדי לבצע פעולה זו.';
$lang['db_column_name_required'] = 'נדרש לתת שם לעמודה בכדי לבצע פעולה זו.';
$lang['db_column_definition_required'] = 'יש צורך להגדיר את העמודה בכדי לבצע פעולה זו.';
$lang['db_unable_to_set_charset'] = 'לא ניתן לבצע חיבור לקוח עבור סט תווים זה: %s';
$lang['db_error_heading'] = 'שגיאת מסד נתונים';
