<?php
$config = array (
    // 沙箱模式
    'debug'       => false,
    // 签名类型（RSA|RSA2）
    'sign_type'   => "RSA2",
    // 应用ID
    'appid'       => '2021003143648112',
    // 支付宝公钥文字内容 (1行填写，特别注意：这里是支付宝公钥，不是应用公钥，最好从开发者中心的网页上去复制)
    'public_key'  => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAmyuQs9OBd+K78471goHZ1A5DV/MsKFGyUCvFTEyVnOxKlLmKgEjv3WnoWSIwxZJzfklaGFLpexob9ueMH3/R2y1QnGD18QowJ3CA58pFfd91JYFDNYojn6b373T7Quq6t/cjhFNnYEX9KgJ/FSGh0kz1Tn6LkLCSsuHm7k5/r502SfNpa2M929xY7HcXYrr0nZ77SgY/kL3y5yZwxNIY7u8IclGlmjZYH08QNKyp5MzhJzwM3J2ah2vGec3qEuuPn4hsAOlUzuLN+8TSkwuFgj5fc9Ct/SX05Df+IXrHMKuxwvVWPGC3VzHVkoYyIgFm5lNB8tmgNtPs5JR+7NcgwQIDAQAB',
    // 支付宝私钥文字内容 (1行填写)
    'private_key' => 'MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCnt7TmF/wbNSQt10zWLn09HpsxubJUpcRnFQYM5Qk7TiQ2w9eaZyO0r8bamTKEF53A+PMeCEmN/oIrP0E85MSgjgKrdwgkZRgXbLke6m8HQIIKxXcXiKpcMLbkLc0ep2z1fmEEILIeg9UWfw0U6PCvEdRT7fCfpAo3oGhEfmzdXqm35fs7YUqifqOUFdgEsGi+Q4OL4HNQiK8ihphJlQeY3EMoEV1U0VT/neAgAfu2NoeIHOse1xttoh9Q5rbZnWrxZCsgH/IZpUPCRvI+WYfN0txthYlW5/IyJ4vmJUELMjIAtkBemm7z3/DhvLWXPhHtFFfdUFk5tYPjAZr6a8tvAgMBAAECggEAI4C20XJIUrYYF4AY6snihvqgnZESl+qTPcLsifQ91QkAj3s7e5rTqx7+eC2vzNh2829/f17/uwgiI+d69tnyaY5GMGe9GH8x71t7gHjd0eos3PzZ7ehnL6a8QGcVoaSNYxZCkS4epmj/xYhVi5SXxmd+y9l1c0W1R/sztzQP9XgIkF5Ii/bzdTHVRyLAkHoOWMuHRbk3o7dYGb4HlgJp1QsScwlUfYWYJ2+a5grEDxKyvVaSz1LZJ84lovGcV6G0VS8ZqmlEz0IPkTjlMvpBF5T0qOpp8u1JRcZAtVz8B+aVPFr6+jVHjmyYmcuK9KCwgFZvn4+GRUMQzJYSs3FXMQKBgQDzwZahkFUYWjZui5udy0Fe5lwUeAGgNDg/IlUZzHLy1BPIFsiH691fz4YjgON13ib0FuORbQxB5AkwpUsXB5HV3Bvqt9KQ/o91g57tybhs1K7YTerX9nenY0JMwlvYhs+MepmYkk44mwRFkkHP8Lk9PlSztP5DWp3IzNWuFIB/yQKBgQCwJFpxMPicIG+5mdBHorKBm1feEYSQVHxb+j/Alk2dbIrIa8OhHdjknc0ITeUTkRz1OYIgb7fVjtnXmi8BBumcX8mz84z29CuCfVCF/67/0DE+Fki6BmIUY4tHYIDOdEhsFJpVXVDioXyZKfGcS9QQ6b6NoFZCSsKpLNDfWBG9dwKBgQDrjwgnR9jEOOHjQGz5N2QL9qfDbBl+HRjCAkalMMtg2Qvo0AOoPopnPaAOjU7DKBUSy0/NyMkQn/M1nHcNYVZQim48DLqjPi2azcB3YPolyf7Rr7KkU11cWHLIxQaHH/hQdGYcaD7JOx0MsoOBFMueYK7wH5ebtWVHmJNisBNh6QKBgHEaRsDb8yc9ZMBG1gjJndm/SAKeOQL8XQYqgHlHifsF0W+0Ut/H7JeQBDHK4sdXrMKB9N6jHeYRXiwlIv2D1MnGcBwOzXtdefrGQMhqT5KPuq7lXDmnE5+H880XWF45KK/N4vPhgaikjP9EgZIc1sUtcmA6jmju3GQJFO30/R8tAoGAGUi39VwsoJ6P3NZQOEnFYUWYxRTZDQCM/ama9deaKgYLHVKWZ237ghTpHwmeX5eYQ1NiU8ijooswLtRuc0N6CLgel8hO7DoRrMmvmHDlWShjx8MzCmINiQUrjc4ixuQBQ/qmdJ2eURRwb3ocTQran1Sjp/62FRxtTBQMfuVjgJI=',
    // 应用公钥证书完整内容（新版资金类接口转 app_cert_sn）
    'app_cert'    => '',
    // 支付宝根证书完整内容（新版资金类接口转 alipay_root_cert_sn）
    'root_cert'   => '',
    // 支付成功通知地址
    'notify_url'  => 'http://www.xiaojushiyan.com/index/flow/alipay_return',
    // 网页支付回跳地址
    'return_url'  => 'http://www.xiaojushiyan.com/index/flow/alipay_return',
);