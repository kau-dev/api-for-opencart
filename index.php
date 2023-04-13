<?php
include 'api_db.php';
$dbname = $GLOBALS['dbname'];
$link = $GLOBALS['link'];
$dataR = json_decode(file_get_contents("php://input"));
if (isset($dataR->{'username'})) {
    $userName = $dataR->{'username'};
    $key = $dataR->{'key'};
    if (apiUserDB($userName, $key)) {

        $str_query = "";
        $str_condition = "";

        if (isset($dataR->{'tableName'})) {
            $tableName = strip_tags(addslashes(($dataR->{'tableName'})));
            if (tableIsPresent($tableName) == true) {
                $arr = coluns_t_ba($dbname, $tableName);

                foreach ($arr as &$value) {
                    $dopStr = "";
                    if (str_contains($value[1] . "", "varchar") or str_contains($value[1] . "", "datetime")) {
                        $dopStr = "\"";
                        //  echo ' '.$value[0].'_';
                    }
                    if (isset($dataR->{'condition_' . $value[0]})) {
                        $condition_value = formatdata_text(strip_tags(addslashes(trim($dataR->{'condition_' . $value[0]}))));
                        $str_condition = $str_condition . $value[0] . '=' . $dopStr . $condition_value . $dopStr . ' AND ';
                    }
                }

                $str_query = " * ";
                $str_condition = substr($str_condition, 0, strlen($str_condition) - 4);
                if (strlen($str_query) > 0) {
                    // header('Content-Type:application/json; charset=UTF-8');
                    $str_cond_ = "";
                    if (strlen($str_condition) > 0) {
                        $str_cond_ = " WHERE " . $str_condition;
                    }

                    try {
                        header('Content-Type:application/json; charset=UTF-8');

                        $reqest_data = "";
                        $sqlusrs = $link->query("SELECT * FROM " . $tableName . $str_cond_);
                        while ($resusrs = $sqlusrs->fetch(PDO::FETCH_ASSOC)) {
                            $reqest_data = $reqest_data . "
                  {
                    ";
                            foreach ($arr as &$value) {
                                $reqest_data = $reqest_data . '"' . $value[0] . '":' . formatdata_text($resusrs[$value[0]]) . ',
                    ';
                            }
                            $reqest_data = substr($reqest_data, 0, strrpos($reqest_data, ","));
                            $reqest_data = $reqest_data . "
                },";
                        }
                        $reqest_data = substr($reqest_data, 0, strlen($reqest_data) - 1);

                        // if(strlen($reqest_data)>3){
                        echo ('{"data":[');
                        echo ($reqest_data);
                        echo (']}');
                        //}
                    } catch (PDOException $e) {
                        echo $e->getMessage();
                        // echo "Error";
                    }
                } else {
                    Printf("Укажите параметры!");
                    help_print($tableName);
                }
            } else {
                Printf("tableName - указано не верно!");
                help_print();
            }
        } else {
            Printf("Укажите tableName!");
            help_print("");
        }
        //     } else {
        //         Printf("Укажите key!");
        //         help_print();
        //     }
    } else {
        Printf("Укажите username и key не верны!");
        help_print();
    }

} else {
    Printf("Укажите username и key!");
    help_print();
}

function help_print($tableName = "")
{

    $dbname = $GLOBALS['dbname'];
    header('Content-Type:text/html; charset=UTF-8');
    $data_set = "";
    if ($tableName != "") {
        $arr = coluns_t_ba($dbname, $tableName);
        if (tableIsPresent($tableName) == true) {
            Printf("<p> При указании условия прибавляем префикс condition_ <p>Например condition_id для поля с именем id <p>");
            //  Printf("<p> При указании значения прибавляем префикс data_<p>Например data_name для поля с именем name <p>");
            $data_set = "Доступные поля:";
            foreach ($arr as &$value) {
                $data_set = $data_set . "<p>" . $value[0];
            }
        }
    }
    $help_print = "<p>Заголовок запроса:<p>	\"application/json; charset=UTF-8\"<p> <p>Пример передаваемыех данных в теле POST  запроса:<p>{
\"username\":\"shtormD\",
\"key\":\"long key\",
\"tableName\":\"users\"
} <p> данное обращение выдаст записи в таблице users, со значеним role=1 и activation=1";
    Printf("<p>Результат:Json. Структура с полем data - массив структур <p>");
    Printf("<p> <p>Описание обращения к API: <p>");
    Printf("<p>Используем POST запрос, данные передаем в теле запроса как текст <p>");
    Printf($help_print);
    if ($data_set != "") {
        Printf("<p> <p> Список доступных полей:<p>");
        Printf($data_set);
    } else {
        Printf("<p> <p>Доступны следующие таблицы:<p>");
        $myTables = arrayTablesNames();
        foreach ($myTables as &$value) {
            Printf($value . "<p>");
        }
    }
}
/////////////////////////////////////////////////////////////s
function arrayTablesNames()
{
    $link = $GLOBALS['link'];
    $dbname = $GLOBALS['dbname'];
    $result_ = array();
    $sqltables = $link->query('SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA="' . $dbname . '"');
    $i = 0;
    while ($restable = $sqltables->fetch(PDO::FETCH_ASSOC)) {

        $result_[$i] = $restable['TABLE_NAME'];
        $i++;
    }
    return $result_;
}
function tableIsPresent($tableName)
{
    $link = $GLOBALS['link'];
    $dbname = $GLOBALS['dbname'];
    $result_ = false;
    $sqltables = $link->query('SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA="' . $dbname . '"');
    while ($restable = $sqltables->fetch(PDO::FETCH_ASSOC)) {
        if ($restable['TABLE_NAME'] == $tableName) {
            $result_ = true;
        }
    }

    return $result_;
}
function coluns_t_ba($base_name, $table_name)
{
    $link = $GLOBALS['link'];

    $sqlcoluns = $link->query('SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA="' . $base_name . '" and TABLE_NAME="' . $table_name . '" ');

    $menu_array = array(array(), array());
    $i = 0;

    while ($rescoluns = $sqlcoluns->fetch(PDO::FETCH_ASSOC)) {
        $menu_array[$i][0] = $rescoluns['COLUMN_NAME'];
        $menu_array[$i][1] = $rescoluns['COLUMN_TYPE'];
        $i++;
    }
    return $menu_array;
}
function formatdata_text($mText)
{
    $mText_ = json_encode($mText, JSON_HEX_TAG | JSON_HEX_APOS | JSON_ERROR_SYNTAX | JSON_HEX_AMP);

    return $mText_;
}

function apiUserDB($userName, $key)
{
    $link = $GLOBALS['link'];
    $dbname = $GLOBALS['dbname'];
    $result_ = array();

    $sqltables = $link->query("SELECT * FROM `" . $GLOBALS['prefix'] . "api` WHERE username ='" . $userName . "' AND `key` = '" . $key . "'");
    $i = 0;
    while ($restable = $sqltables->fetch(PDO::FETCH_ASSOC)) {
        $result_[$i] = $restable['username'];
        $i++;
    }

    return $i > 0;
}
