<?php

class PaymentConsitution extends Model
{
    const TYPE_ONLINE_PAID = 0;
    const TYPE_BALANCE = 1;
    const TYPE_DIRECT = 2;
    const TYPE_ECREDIT = 3;
    const TYPE_HONGBAO = 4;

    private static $typeName = array(
        0 => '在线支付',
        1 => '余额支付',
        2 => '第三方支付',
        3 => '在线支付优惠',
        4 => '红包抵扣'
    );

    protected $service = 'eus';

    public static function getPreferentialConstitutionMap(array $orderIds)
    {
        $tOrderPaymentConstitution = self::mget($orderIds);
        $constitutionMaps = array();
        foreach ($orderIds as $orderId) {
            $constitutionMaps[$orderId] = array('amount' => 0, 'records' => array());
        }
        $preferentialType = array(self::TYPE_ECREDIT, self::TYPE_HONGBAO);
        foreach ($tOrderPaymentConstitution as $orderId => $constitutions) {
            foreach ($constitutions as $constitution) {
                if (isset($constitution->pay_type) && in_array($constitution->pay_type, $preferentialType)) {
                    $constitutionMaps[$orderId]['amount'] += $constitution->amount;
                    $constitutionMaps[$orderId]['records'][] = array(
                        'amount' => $constitution->amount,
                        'name' => self::$typeName[$constitution->pay_type]
                    );
                }
            }
        }
        return $constitutionMaps;
    }

    public static function mget(array $orderIds)
    {
        return self::factory()->call('get_order_payment_constitution_map')->with($orderIds)->result();
    }
}
