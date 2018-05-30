<?php
/**
*	MD5验签加密
*/
function encrypt_md5($param="", $key='')
{
    #对数组进行排序拼接
    if(is_array($param)){
        ksort($param);
        $arg = '';
        foreach ($param as $key => $value) {
            if ($value != "") {
                $arg .= "{$key}={$value}&";
            }
        }
        $md5Str = rtrim($arg, '&');
    }
    else{
        $md5Str = $param;
    }
    $md5 = md5($md5Str);
    return '' === $param ? 'false' : $md5;
}

/**
 * 检查是否是白名单
 * @return [type] [description]
 */
function checkWhite()
{
    return in_array($_SERVER['REMOTE_ADDR'], config('whiteList.ip_list'));
}

function getSession($uid = 'test123', $session = '12312312312')
{
    $md5 = str_split(substr(md5($uid . $session), 0, 22), 2);
    $uid = str_split(str_pad($uid, 10, 0), 1);
    foreach ($md5 as $key => $val) {
        $md5[$key] .= array_shift($uid);
    }
    return implode('', $md5);
}
