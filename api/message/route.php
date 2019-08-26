<?php

use think\Route;

Route::resource('message/categories', 'message/Categories');
Route::get('message/categories/subCategories', 'message/Categories/subCategories');
Route::resource('message/articles', 'message/Articles');
Route::resource('message/pages', 'message/Pages');
Route::resource('message/userArticles', 'message/UserArticles');

Route::get('message/search', 'message/Articles/search');
Route::get('message/articles/my', 'message/Articles/my');
Route::get('message/articles/relatedArticles', 'message/Articles/relatedArticles');
Route::post('message/articles/doLike', 'message/Articles/doLike');
Route::post('message/articles/cancelLike', 'message/Articles/cancelLike');
Route::post('message/articles/doFavorite', 'message/Articles/doFavorite');
Route::post('message/articles/cancelFavorite', 'message/Articles/cancelFavorite');
Route::get('message/tags/:id/articles', 'message/Tags/articles');
Route::get('message/tags', 'message/Tags/index');
Route::get('message/tags/hotTags', 'message/Tags/hotTags');

Route::post('message/userArticles/deletes', 'message/UserArticles/deletes');