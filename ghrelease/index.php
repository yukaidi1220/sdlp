<?php
// 获取传入的参数
$repo = $_GET['repo'];
$tag = $_GET['tag'] ?? ''; // 如果未指定 tag，则则默认为空
$search = $_GET['search'] ?? ''; // 如果未指定 search，则默认为空
$mirror_name = $_GET['mirror'] ?? ''; // 如果未指定 mirror，则默认为空

// 检查参数
if (empty($repo)) {
    echo '未定义必需参数 repo !';
}
if (!empty($tag)) {
    // 参考：https://docs.github.com/zh/rest/releases/releases#get-a-release-by-tag-name
    $tags = 'tags/' . $tag;
} else {
    $tags = 'latest';
}

// 预先定义 mirror 数组
$mirrors = [
    'ghproxy' => 'https://mirror.ghproxy.com/',
    'pig' => 'https://dl.ghpig.top/',
    'ddlc' => 'https://dgh.ddlc.top/',
    'slink' => 'https://dgh.ddlc.top/',
    'con' => 'https://gh.con.sh/',
    // 添加其他 mirror 名称和对应的域名
];

// 根据 mirror 名称获取对应的域名
$mirror = $mirrors[$mirror_name] ?? '';

// 构建 GitHub API URL
// 注意这里有请求限制，如需正式大量使用请使用缓存或本地代理
$api_url = "https://api.github.com/repos/{$repo}/releases/{$tags}";
// $api_url = "http://localhost:12345/repos/{$repo}/releases/{$tags}";

// 初始化 cURL
$ch = curl_init($api_url);

// 设置 cURL 选项
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'User-Agent: MyGitHubAPI', // 设置 User-Agent
    'Accept: application/vnd.github.v3+json',
]);

// 发起请求
$response = curl_exec($ch);

// 检查是否有错误
if (curl_errno($ch)) {
    echo 'cURL错误: ' . curl_error($ch);
    exit;
}

// 关闭 cURL
curl_close($ch);

// 解析 JSON 响应
$data = json_decode($response, true);
// 查找匹配的 release 文件
foreach ($data['assets'] as $asset) {
    if (empty($search) || strpos($asset['name'], $search) !== false) {
        $matching_assets = $asset['browser_download_url'];
    }
}

// 输出匹配的文件链接
if (!empty($matching_assets)) {
    // 如果指定了 mirror，则附加到链接前面
    if (!empty($mirror)) {
        $url = $mirror . $matching_assets;
    } else {
        $url = $matching_assets;
    }
    header("Location: $url");
} else {
    echo "未找到匹配的 release 文件。\n";
    exit;
}
?>