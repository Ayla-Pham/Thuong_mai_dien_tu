<?php
/**
 * Khai báo các route: $router->get('/duong-dan', 'TenController@phuongThuc');
 * Tham số động dùng {ten}, ví dụ '/san-pham/{slug}'.
 */
$router->get('/', 'HomeController@index');
$router->get('/san-pham', 'ShopController@index');
$router->get('/san-pham/{slug}', 'ShopController@show');

// --- Giỏ hàng, thanh toán, đơn hàng ---
$router->get('/gio-hang', 'CartController@index');
$router->post('/gio-hang/them', 'CartController@add');
$router->post('/gio-hang/cap-nhat', 'CartController@update');
$router->post('/gio-hang/xoa', 'CartController@remove');
$router->post('/gio-hang/ma-giam-gia', 'CartController@coupon');
$router->get('/thanh-toan', 'CheckoutController@index');
$router->post('/thanh-toan', 'CheckoutController@place');
$router->get('/thanh-toan/vnpay-return', 'VnpayController@return');
$router->get('/thanh-toan/vnpay-ipn', 'VnpayController@ipn');
$router->post('/don-hang/{code}/thanh-toan-lai', 'VnpayController@retry');
$router->get('/don-hang', 'OrderController@index');
$router->get('/don-hang/{code}', 'OrderController@show');
$router->post('/don-hang/{code}/huy', 'OrderController@cancel');

// --- Trang quản trị (AdminController::__construct kiểm tra quyền admin) ---
$router->get('/admin', 'AdminDashboardController@index');
$router->get('/admin/san-pham', 'AdminProductController@index');
$router->get('/admin/san-pham/them', 'AdminProductController@create');
$router->post('/admin/san-pham', 'AdminProductController@store');
$router->get('/admin/san-pham/{id}/sua', 'AdminProductController@edit');
$router->post('/admin/san-pham/{id}', 'AdminProductController@update');
$router->post('/admin/san-pham/{id}/xoa', 'AdminProductController@delete');
$router->get('/admin/danh-muc', 'AdminCategoryController@index');
$router->get('/admin/danh-muc/them', 'AdminCategoryController@create');
$router->post('/admin/danh-muc', 'AdminCategoryController@store');
$router->get('/admin/danh-muc/{id}/sua', 'AdminCategoryController@edit');
$router->post('/admin/danh-muc/{id}', 'AdminCategoryController@update');
$router->post('/admin/danh-muc/{id}/xoa', 'AdminCategoryController@delete');
$router->get('/admin/don-hang', 'AdminOrderController@index');
$router->get('/admin/don-hang/{id}', 'AdminOrderController@show');
$router->post('/admin/don-hang/{id}/trang-thai', 'AdminOrderController@updateStatus');
$router->post('/admin/don-hang/{id}/thanh-toan', 'AdminOrderController@updatePayment');
$router->get('/admin/khach-hang', 'AdminUserController@index');
$router->post('/admin/khach-hang/{id}/khoa', 'AdminUserController@toggle');
$router->get('/admin/ma-giam-gia', 'AdminCouponController@index');
$router->get('/admin/ma-giam-gia/them', 'AdminCouponController@create');
$router->post('/admin/ma-giam-gia', 'AdminCouponController@store');
$router->get('/admin/ma-giam-gia/{id}/sua', 'AdminCouponController@edit');
$router->post('/admin/ma-giam-gia/{id}', 'AdminCouponController@update');
$router->post('/admin/ma-giam-gia/{id}/xoa', 'AdminCouponController@delete');

// --- Tài khoản ---
$router->get('/dang-ky', 'AuthController@showRegister');
$router->post('/dang-ky', 'AuthController@register');
$router->get('/dang-nhap', 'AuthController@showLogin');
$router->post('/dang-nhap', 'AuthController@login');
$router->post('/dang-xuat', 'AuthController@logout');
$router->get('/tai-khoan', 'AccountController@profile');
$router->post('/tai-khoan', 'AccountController@updateProfile');
$router->post('/tai-khoan/doi-mat-khau', 'AccountController@changePassword');
