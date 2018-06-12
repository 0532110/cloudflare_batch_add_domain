<?php

function post_data($url, $post=null, $header=array(), $timeout=8, $https=0)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_HEADER, 0);

    if ($https) // https
    {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);  // 从证书中检查SSL加密算法是否存在
    }

    if ($header)
    {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    }

    if ($post)
    {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($post) ? http_build_query($post) : $post);
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $content = curl_exec($ch);
    curl_close($ch);

    return $content;
}

// header 共用
$header = array(
    "X-Auth-Email:xxx@qq.com",
    "X-Auth-Key:xxx",
    "Content-Type:application/json"
);

$domain = file_get_contents('./domain.txt');
$domain = explode("\r\n", $domain);

$record = file_get_contents('./record.txt');
$record = explode("\r\n", $record);

foreach ($domain as $v_domain)
{
    // 添加域名
    $url = "https://api.cloudflare.com/client/v4/zones";
    $post = array(
        "name" => $v_domain,
        "jump_start" => true
    );

    $post = json_encode($post);
    $rs = post_data($url, $post, $header, 8, 1);
    $rs = json_decode($rs, true);

    if ($rs['success'] == false)
    {
        echo '添加失败，错误原因：' . $rs['errors'][0]['message'] . "\n";
        continue;
    }
    else
    {
        echo '添加域名成功' . "\n";
        echo '域名id：'     . $rs['result']['id'] . "\n";
        echo '域名：'       . $rs['result']['name'] . "\n";
        echo '域名状态：'   . $rs['result']['status'] . "\n";
        echo '开始添加解析' . "\n";
        $zoneid = $rs['result']['id'];
    }


    foreach ($record as $v_record)
    {
        // 添加解析
        $url_add_records = "https://api.cloudflare.com/client/v4/zones/$zoneid/dns_records";

        $record_detail = explode(',', $v_record);
        $name = strtolower($record_detail[0]);
        $type = strtoupper($record_detail[1]);
        $ip   = $record_detail[2];
        $post = array(
            "type"     => $type,
            "name"     => $name,
            "content"  => $ip,
            "ttl"      => 120, // 1 为自动
            "priority" => 10,
            "proxied"  => false
        );

        $post = json_encode($post);
        $add_records_rs = post_data($url_add_records, $post, $header, 8, 1);
        $rs = json_decode($add_records_rs, true);
        if ($rs['success'] == false)
        {
            echo '记录添加失败，错误原因：' . $rs['errors'][0]['message'] . "\n";
        }
        else
        {
            echo '记录添加成功' . "\n";
        }
    }

}
