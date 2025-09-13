<?php
declare (strict_types=1);

namespace app\api\controller;

use Exception;
use utils\EmailUtil;
use utils\SecretUtil;


class Test
{
    public function index()
    {

    }

    public function testAes(): string
    {
//        参数信息
        $params = request()->param();
        $params['IP'] = request()->ip();
        $params['role_id'] = request()->method();
        $params['nonce'] = SecretUtil::generateRandomString(5);
//        打印原始参数
        dump($params);
//        排序参数
        ksort($params);
//        构建成字符串
        $paramsString = http_build_query($params);
//        获取token
        $accessToken = SecretUtil::genAccessToken($params);
        dump('sign ==  ' . $accessToken);
//      解密token

        $deData = SecretUtil::parseAccessToken($accessToken);
        dump('deData ==  ' . $deData);
        return '123';
    }

    /**
     * @throws Exception
     */
    public function testParamsSort(): void
    {
        $data = request()->param();
//
//        ksort($data);
//        dump(http_build_query($data));
        dump(SecretUtil::genAccessToken($data));
//        mkdir("D:/keys/RSA/o.pem");
        SecretUtil::generateRSAKeys();
    }

    /**
     * @throws Exception
     */
    public function testRSA()
    {
        $RSAEncryptByPublicKey = SecretUtil::RSAEncryptByPublicKey("hello TP8");
        dump($RSAEncryptByPublicKey);
        dump(SecretUtil::RSADecryptByPrivateKey($RSAEncryptByPublicKey));

        return 'success';

    }

    /**
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function testSendMail(): void
    {
        $sendTxtMail = EmailUtil::sendMail("ibitle@163.com", "测试标题", "这是一个测试标题的内容");
        dump($sendTxtMail);
    }

    public function testECB(): void
    {

        dump(SecretUtil::aesEncryptECB('68f3868072bf68f3fafb788e2f85e18a2bb88c9c622e1573887275f6c5d1566b'));
    }

}
