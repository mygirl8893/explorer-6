<?php

Route::group(['middleware' => 'xdag.state'], function () {
	Route::get('status', 'Network\StatusController@show');
	Route::get('last-blocks', 'Network\LastBlockController@show');
	Route::get('block/{hash}', 'Block\BlockController@show')->name('block')->where('hash', '(.*)');
	Route::get('balance/{address}', 'Wallet\BalanceCheckerController@getBalance')->where('address', '(.*)');
});
