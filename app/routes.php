<?php
Route::pattern('user_id', '\d+');

Route::group(array('prefix' => '/v1'), function () {
    Route::group(array('prefix' => '/user'), function () {
        Route::get('/', 'UserProfileController@getPresent');
        Route::put('/password', 'UserProfileController@changePassword');
        Route::put('/address', 'UserProfileController@setDefaultAddress');
        Route::put('/mobile', 'UserProfileController@updateMobile');
        Route::put('/quota', 'UserProfileController@updateQuota');
        Route::post('/balance/charge', 'UserProfileController@charge');
        Route::get('/balance/withdraw/check', 'UserProfileController@isWithdrawValid');
        Route::post('/balance/withdraw', 'UserProfileController@withdrawApply');
        Route::post('/avatar', 'UserProfileController@setAvatar');
    });

    Route::group(array('prefix' => '/users/{user_id}', 'before' => 'auth1'), function () {
        Route::resource('/messages', 'UserMessageController', array('only' => array('index')));
        Route::patch('/messages/{messageId}', 'UserMessageController@markAsRead');
        Route::patch('/messages', 'UserMessageController@markAllAsRead');
        Route::get('/messages/count', 'UserMessageController@countByUserId');
        Route::get('/trades/count', 'UserTradeController@count');
        Route::resource('/trades', 'UserTradeController', array('only' => array('index')));
        Route::post('/favor/foods/{food_id}', 'UserFavorFoodController@store');
        Route::resource('/favor/foods', 'UserFavorFoodController', array('only' => array('index', 'destroy')));
        Route::post('/favor/restaurants/{restaurant_id}', 'UserFavorRestaurantController@store');
        Route::resource('/favor/restaurants', 'UserFavorRestaurantController', array('only' => array('index', 'destroy')));
        Route::get('/hongbao/count', 'UserHongbaoController@count');
        Route::post('/hongbao/exchange', 'UserHongbaoController@exchange');
        Route::resource('/hongbao', 'UserHongbaoController', array('only' => array('index')));
        Route::resource('/invitations', 'UserInvitationController', array('only' => array('index')));
        Route::resource('/addresses', 'UserAddressController', array('only' => array('index', 'show', 'store', 'update', 'destroy')));
        Route::get('/orders/count', 'UserOrderController@count');
        Route::post('/orders/{order_id}/complaint', 'UserOrderController@addComplaint');
        Route::resource('/orders', 'UserOrderController', array('only' => array('index', 'show')));
        Route::resource('/orders/{order_id}/refunding', 'UserOrderRefundingController', array('only' => array('index', 'store')));
        Route::post('/orders/{order_id}/rating', 'UserOrderController@rate');
        Route::post('/orders/{order_id}/items/{item_id}/rating', 'UserOrderController@rateItem');
        Route::get('/gifts', 'GiftController@getUserGifts');
        Route::post('/gifts/{giftId}/exchange', 'GiftController@exchange');
        Route::resource('/point_change_records', 'UserPointChangeRecordController', array('only' => array('index')));
        Route::resource('/balance_records', 'UserBalanceChangeRecordController', array('only' => array('index')));
        Route::get('/feedbacks/count', 'UserFeedbackController@count');
        Route::resource('/feedbacks', 'UserFeedbackController', array('only' => array('index', 'store')));
    });

    Route::resource('/restaurants', 'RestaurantController', array('only' => array('show', 'index')));
    Route::resource('/cities', 'CityController', array('only' => array('show', 'index')));
    Route::resource('/banners', 'BannerController', array('only' => array('index')));
    Route::resource('/foods', 'FoodController', array('only' => array('index')));
    Route::resource('/gifts', 'GiftController', array('only' => array('index')));
    Route::post('/mobile/verify_code/send', 'MobileController@sendVerifyCode');
    Route::post('/mobile/verify_code/validate', 'MobileController@validateVerifyCode');
});

Route::get('/ping', function () { return '1'; });
