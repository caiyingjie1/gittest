<?php

class PayRecord extends Model
{
    const PAY_COMPANY_ID_ALIPAY_WEB = 1;
    const PAY_COMPANY_ID_ALIPAY_APP = 2;
    const PAY_COMPANY_ID_TENPAY_WEB = 3;
    const PAY_COMPANY_ID_TENPAY_APP = 4;
    const PAY_COMPANY_ID_ALIPAY_BANK = 5;
    const PAY_COMPANY_ID_SHENGPAY_BANK = 6;
    const PAY_COMPANY_ID_SHENGPAY_CARD = 7;
    const PAY_COMPANY_ID_ONEPAY = 8;
    const PAY_COMPANY_ID_MOBILE_CARD = 9;
    const PAY_COMPANY_ID_QQPAY = 10;

    const COME_FROM_WEB = 1;
    const COME_FROM_WEB_MOBILE = 2;
    const COME_FROM_APP_IOS = 3;
    const COME_FROM_APP_ANDROID = 4;

    protected $service = "eus";

    public static function payRecordMakeNew($userId, $companyId, $comeFrom, $payBank, $totalFee)
    {
        return self::factory()
            ->call('pay_record_make_new')
            ->with($userId, $companyId, $comeFrom, $payBank, $totalFee)
            ->run();
    }
}
